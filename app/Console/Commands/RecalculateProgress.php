<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\Progress;
use App\Models\Puzzle;
use App\Models\Result;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMissionProgress;
use App\Models\UserPuzzleProgress;
use App\Services\TopicProgressService;
use App\Services\XpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-applies the current progression rules to existing data:
 * puzzle XP only for solved puzzles (minus a bought hint), missions cleared only when every
 * required puzzle is solved, topic XP rebuilt from those, quiz XP capped per quiz, and topic
 * progress derived from facts. --dry-run runs the same code and rolls it back.
 */
class RecalculateProgress extends Command
{
    protected $signature = 'netlearn:recalculate {--user= : Only this user_id} {--dry-run : Show the changes without saving them}';

    protected $description = 'Recalculate XP, mission completion and topic progress with the current rules';

    public function handle(TopicProgressService $topicProgress): int
    {
        $students = User::where('role', 'student')
            ->when($this->option('user'), fn ($q, $id) => $q->where('user_id', $id))
            ->orderBy('user_id')
            ->get();

        if ($students->isEmpty()) {
            $this->warn('No matching students.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $penalty = (int) config('netlearn.hint_xp_penalty');
        $quizCap = (int) config('netlearn.quiz_pass_xp');
        $rows = [];

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $userId = $student->user_id;
                $before = XpService::total($userId);
                $changes = ['puzzles' => 0, 'missions' => 0, 'quiz rows' => 0];

                // 1. Puzzle XP: only solved puzzles pay, minus the hint penalty
                foreach (UserPuzzleProgress::with('puzzle')->where('user_id', $userId)->get() as $pp) {
                    $points = (int) ($pp->puzzle->points ?? 100) ?: 100;
                    $target = $pp->is_completed ? max(0, $points - ((int) $pp->hints_used > 0 ? $penalty : 0)) : 0;
                    if ((int) $pp->xp_awarded !== $target) {
                        $pp->xp_awarded = $target;
                        $pp->save();
                        $changes['puzzles']++;
                    }
                }

                // 2. Missions: cleared only when every required puzzle is solved
                $missionIds = UserMissionProgress::where('user_id', $userId)->pluck('mission_id');
                foreach (Mission::whereIn('mission_id', $missionIds)->get() as $mission) {
                    $mp = UserMissionProgress::where('user_id', $userId)->where('mission_id', $mission->mission_id)->first();
                    $required = $mission->puzzles()->where('status', 'published')->where('is_required', true)->pluck('puzzle_id');
                    if ($required->isEmpty()) {
                        $required = $mission->puzzles()->where('status', 'published')->pluck('puzzle_id');
                    }
                    $solved = $required->isEmpty() ? 0 : UserPuzzleProgress::where('user_id', $userId)
                        ->whereIn('puzzle_id', $required)->where('is_completed', true)->count();
                    $complete = $required->isNotEmpty() && $solved === $required->count();

                    if ((bool) $mp->is_completed !== $complete || (bool) $mp->xp_reward_granted !== $complete) {
                        $mp->is_completed = $complete;
                        $mp->xp_reward_granted = $complete;
                        $mp->completed_at = $complete ? ($mp->completed_at ?? now()) : null;
                        $changes['missions']++;
                    }
                    if ($complete) {
                        $stars = UserPuzzleProgress::where('user_id', $userId)->whereIn('puzzle_id', $required)->pluck('best_stars');
                        $mp->best_stars = (int) max(1, min(3, round($stars->sum() / max(1, $required->count()))));
                    } else {
                        $mp->best_stars = 0;
                    }
                    $mp->save();
                }

                // 3. Topic XP rebuilt from solved puzzles and cleared missions
                foreach (Progress::where('user_id', $userId)->get() as $progress) {
                    $topicPuzzleIds = Puzzle::where(function ($q) use ($progress) {
                        $q->whereHas('mission', fn ($m) => $m->where('topic_id', $progress->topic_id))
                            ->orWhere(fn ($s) => $s->whereNull('mission_id')->where('topic_id', $progress->topic_id));
                    })->pluck('puzzle_id');
                    $puzzleXp = (int) UserPuzzleProgress::where('user_id', $userId)->whereIn('puzzle_id', $topicPuzzleIds)->sum('xp_awarded');

                    $missionXp = UserMissionProgress::where('user_id', $userId)
                        ->where('is_completed', true)
                        ->whereHas('mission', fn ($m) => $m->where('topic_id', $progress->topic_id))
                        ->with('mission')
                        ->get()
                        ->sum(fn ($mp) => (int) ($mp->mission->xp_reward ?: 150));

                    $progress->total_xp = $puzzleXp + $missionXp;
                    $progress->save();
                }

                // 4. Quiz XP capped per quiz, trimming the latest rows first
                foreach (Result::where('user_id', $userId)->get()->groupBy('quiz_id') as $results) {
                    $excess = (int) $results->sum('points_earned') - $quizCap;
                    foreach ($results->sortByDesc('result_id') as $result) {
                        if ($excess <= 0) {
                            break;
                        }
                        $cut = min($excess, (int) $result->points_earned);
                        if ($cut > 0) {
                            $result->points_earned = (int) $result->points_earned - $cut;
                            $result->save();
                            $excess -= $cut;
                            $changes['quiz rows']++;
                        }
                    }
                }

                // 5. Topic progress from facts, for topics the student has started
                $topicIds = Progress::where('user_id', $userId)->pluck('topic_id');
                foreach (Topic::whereIn('topic_id', $topicIds)->get() as $topic) {
                    $topicProgress->recalculate($userId, $topic);
                }

                $after = XpService::total($userId);
                $rows[] = [
                    $userId, $student->name, $before, $after, $after - $before,
                    $changes['puzzles'], $changes['missions'], $changes['quiz rows'],
                ];
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['user', 'name', 'XP before', 'XP after', 'change', 'puzzles fixed', 'missions fixed', 'quiz rows capped'], $rows);
        $this->info($dryRun ? 'Dry run: nothing was saved.' : 'Saved.');

        return self::SUCCESS;
    }
}
