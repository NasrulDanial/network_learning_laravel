<?php

namespace Database\Seeders;

use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\Support\CurriculumData;
use InvalidArgumentException;

/**
 * Seeds the networking curriculum assembled by Support\CurriculumData (data/netlearn_*.php):
 * 10 stages, each with a 15-question quiz and 3 missions of 3 puzzle activities.
 *
 * Safe on a database that already has content:
 *  - Records are matched by a stable identity (topic title, quiz in topic, question text,
 *    mission title in topic, puzzle title in mission) and only created when missing.
 *  - Existing records are never overwritten, so lecturer edits and student history survive.
 *  - Empty text fields on an existing topic (description/objectives/content) are filled in.
 *  - A quiz is topped up to its target (15) with questions it does not already have, never beyond it.
 *  - Stages flagged append_notes keep their existing notes and gain the new sections after them.
 *  - Notes that still equal the first-draft seeded text (previous_content) are upgraded to the in-depth
 *    notes; notes a lecturer has edited are never touched.
 *  - Orders are kept unique: a wanted position that is taken goes to the end instead.
 */
class CurriculumSeeder extends Seeder
{
    private const ANSWER_KEYS = ['A', 'B', 'C', 'D'];

    private array $created = ['topics' => 0, 'quizzes' => 0, 'questions' => 0, 'missions' => 0, 'puzzles' => 0];

    public function run(): void
    {
        $curriculum = CurriculumData::all();
        $authorId = User::where('role', 'lecturer')->orderBy('user_id')->value('user_id');

        DB::transaction(function () use ($curriculum, $authorId) {
            foreach ($curriculum as $topicSpec) {
                $topic = $this->seedTopic($topicSpec, $authorId);
                $this->seedQuiz($topic, $topicSpec['quiz']);

                foreach ($topicSpec['missions'] as $missionIndex => $missionSpec) {
                    $mission = $this->seedMission($topic, $missionSpec, $missionIndex + 1);

                    foreach ($missionSpec['puzzles'] as $puzzleIndex => $puzzleSpec) {
                        $this->seedPuzzle($mission, $puzzleSpec, $puzzleIndex + 1);
                    }
                }
            }
        });

        if ($this->command) {
            $this->command->info(sprintf(
                '   Curriculum: +%d topics, +%d quizzes, +%d questions, +%d missions, +%d puzzles (existing records left unchanged)',
                $this->created['topics'], $this->created['quizzes'], $this->created['questions'],
                $this->created['missions'], $this->created['puzzles']
            ));
        }
    }

    private function seedTopic(array $spec, ?int $authorId): Topic
    {
        $objectives = implode("\n", $spec['objectives']);
        $topic = Topic::where('title', $spec['title'])->first();

        if ($topic) {
            // Fill only blanks; never overwrite text a lecturer may have written
            $fill = [];
            foreach (['description' => $spec['description'], 'objectives' => $objectives, 'content' => $spec['content']] as $field => $value) {
                if (blank($topic->{$field})) {
                    $fill[$field] = $value;
                }
            }
            $current = rtrim((string) $topic->content);
            $previous = rtrim((string) ($spec['previous_content'] ?? ''));
            $untouchedDraft = $previous !== '' && $current !== '' && ($current === $previous || str_ends_with($current, $previous));
            $firstLine = trim((string) strtok($spec['content'], "
"));

            if ($untouchedDraft && $current !== rtrim($spec['content'])) {
                // Still the seeded first draft (possibly after the original text of a flagged stage)
                $fill['content'] = $spec['content'];
            } elseif (! empty($spec['append_notes']) && $current !== '' && $firstLine !== '' && ! str_contains($current, $firstLine)) {
                // Add the new note sections after notes that already exist (only for flagged stages)
                $fill['content'] = $current . "

" . $spec['content'];
            }
            if ($fill) {
                $topic->update($fill);
            }

            return $topic;
        }

        $this->created['topics']++;

        return Topic::create([
            'title' => $spec['title'],
            'description' => $spec['description'],
            'objectives' => $objectives,
            'content' => $spec['content'],
            'topic_order' => $this->freePosition(Topic::query(), 'topic_order', $spec['order']),
            'difficulty' => $spec['difficulty'],
            'status' => 'published',
            'estimated_time_minutes' => $spec['minutes'],
            'created_by' => $authorId,
        ]);
    }

    private function seedQuiz(Topic $topic, array $spec): void
    {
        // A topic's assessment is its first quiz; reuse it so the topic never gains a second one
        $quiz = Quiz::where('topic_id', $topic->topic_id)->orderBy('quiz_id')->first();

        if (! $quiz) {
            $quiz = Quiz::create([
                'topic_id' => $topic->topic_id,
                'title' => $spec['title'],
                'passing_mark' => $spec['passing_mark'],
            ]);
            $this->created['quizzes']++;
        }

        $target = (int) ($spec['target'] ?? 0);
        $count = Question::where('quiz_id', $quiz->quiz_id)->count();

        foreach ($spec['questions'] as $q) {
            [$text, $a, $b, $c, $d, $correct] = $q;
            if (! in_array($correct, self::ANSWER_KEYS, true)) {
                throw new InvalidArgumentException("Quiz question \"{$text}\" has invalid answer key \"{$correct}\".");
            }

            if (Question::where('quiz_id', $quiz->quiz_id)->where('question_text', $text)->exists()) {
                continue;
            }
            if ($target > 0 && $count >= $target) {
                continue; // a quiz is topped up to its target, never past it
            }

            Question::create([
                'quiz_id' => $quiz->quiz_id, 'question_text' => $text,
                'option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d, 'correct_answer' => $correct,
            ]);
            $count++;
            $this->created['questions']++;
        }
    }

    private function seedMission(Topic $topic, array $spec, int $wantedOrder): Mission
    {
        $mission = Mission::where('topic_id', $topic->topic_id)->where('title', $spec['title'])->first();
        if ($mission) {
            return $mission;
        }

        $this->created['missions']++;

        return Mission::create([
            'topic_id' => $topic->topic_id,
            'title' => $spec['title'],
            'description' => $spec['description'],
            'objective' => $spec['objective'],
            'difficulty' => $spec['difficulty'],
            'mission_order' => $this->freePosition(Mission::where('topic_id', $topic->topic_id), 'mission_order', $wantedOrder),
            'pass_mark' => $spec['pass_mark'],
            'xp_reward' => $spec['xp_reward'],
            'status' => 'published',
        ]);
    }

    private function seedPuzzle(Mission $mission, array $spec, int $wantedOrder): void
    {
        if (Puzzle::where('mission_id', $mission->mission_id)->where('question_text', $spec['title'])->exists()) {
            return;
        }

        [$activityData, $correctAnswer] = $this->buildActivity($spec);

        Puzzle::create([
            'mission_id' => $mission->mission_id,
            'topic_id' => $mission->topic_id,
            'puzzle_type' => $spec['type'],
            'question_text' => $spec['title'],
            'instruction' => $spec['instruction'],
            'activity_data' => $activityData,
            'correct_answer' => $correctAnswer,
            'hint' => $spec['hint'] ?? null,
            // The feedback panel already shows "Correct!" / "Not quite", so these hold only the substance
            'correct_feedback' => $spec['correct_feedback'] ?? ($spec['explanation'] ?? 'Well done.'),
            'incorrect_feedback' => $spec['incorrect_feedback'] ?? 'Review your answer using the hint, then try again.',
            'explanation' => $spec['explanation'] ?? null,
            'points' => $spec['points'] ?? 100,
            'activity_order' => $this->freePosition(Puzzle::where('mission_id', $mission->mission_id), 'activity_order', $wantedOrder),
            'difficulty' => $spec['difficulty'] ?? $mission->difficulty,
            'max_attempts' => 0,
            'is_required' => true,
            'status' => 'published',
        ]);
        $this->created['puzzles']++;
    }

    /**
     * Build activity_data (student-visible) and correct_answer (server-only) with stable IDs,
     * in exactly the shapes MissionController::processActivityPayload produces.
     */
    private function buildActivity(array $spec): array
    {
        switch ($spec['type']) {
            case 'identification':
                $this->assertCount($spec, 'options', 2, 4);
                $options = $this->labelled($spec['options'], 'opt_');
                return [
                    ['prompt' => $spec['title'], 'image_url' => null, 'options' => $options],
                    ['correct_option' => $options[$this->index($spec, 'correct', count($options))]['id']],
                ];

            case 'scenario':
                $this->assertCount($spec, 'choices', 2, 4);
                $choices = $this->labelled($spec['choices'], 'choice_');
                return [
                    ['scenario_text' => $spec['instruction'], 'choices' => $choices],
                    ['correct_choice' => $choices[$this->index($spec, 'correct', count($choices))]['id']],
                ];

            case 'matching':
                $this->assertCount($spec, 'pairs', 2, 4);
                $left = $right = $pairs = [];
                foreach (array_values($spec['pairs']) as $i => [$l, $r]) {
                    $left[] = ['id' => 'L_' . ($i + 1), 'label' => $l];
                    $right[] = ['id' => 'R_' . ($i + 1), 'label' => $r];
                    $pairs['L_' . ($i + 1)] = 'R_' . ($i + 1);
                }
                return [['left_items' => $left, 'right_items' => $right], ['pairs' => $pairs]];

            case 'sequencing':
                $this->assertCount($spec, 'steps', 3, 7);
                $items = $this->labelled($spec['steps'], 'seq_');
                return [
                    ['prompt' => $spec['instruction'], 'items' => $items],
                    ['order' => array_column($items, 'id')],
                ];

            case 'classification':
                $this->assertCount($spec, 'categories', 2, 4);
                $this->assertCount($spec, 'items', 2, 8);
                $categories = $this->labelled($spec['categories'], 'cat_');
                $items = $mapping = [];
                foreach (array_values($spec['items']) as $j => [$label, $catIndex]) {
                    if (! isset($categories[$catIndex])) {
                        throw new InvalidArgumentException("Puzzle \"{$spec['title']}\": item \"{$label}\" points to a missing category.");
                    }
                    $items[] = ['id' => 'item_' . ($j + 1), 'label' => $label];
                    $mapping['item_' . ($j + 1)] = $categories[$catIndex]['id'];
                }
                return [['categories' => $categories, 'items' => $items], ['mapping' => $mapping]];

            case 'drag_and_drop':
                $this->assertCount($spec, 'pairs', 2, 4);
                $draggables = $zones = $placements = [];
                foreach (array_values($spec['pairs']) as $k => [$drag, $zone]) {
                    $draggables[] = ['id' => 'drag_' . ($k + 1), 'label' => $drag];
                    $zones[] = ['id' => 'zone_' . ($k + 1), 'label' => $zone];
                    $placements['drag_' . ($k + 1)] = 'zone_' . ($k + 1);
                }
                return [
                    ['template_key' => $spec['template_key'] ?? 'device_to_osi_layer', 'draggables' => $draggables, 'drop_zones' => $zones],
                    ['placements' => $placements],
                ];
        }

        throw new InvalidArgumentException("Unknown puzzle type \"{$spec['type']}\" for \"{$spec['title']}\".");
    }

    private function labelled(array $labels, string $prefix): array
    {
        return array_map(
            fn ($label, $i) => ['id' => $prefix . ($i + 1), 'label' => $label],
            array_values($labels),
            array_keys(array_values($labels))
        );
    }

    private function index(array $spec, string $key, int $count): int
    {
        $index = $spec[$key] ?? null;
        if (! is_int($index) || $index < 0 || $index >= $count) {
            throw new InvalidArgumentException("Puzzle \"{$spec['title']}\": \"{$key}\" must point to one of its {$count} options.");
        }

        return $index;
    }

    /** Keep content within what the 800x500 Phaser canvas can lay out. */
    private function assertCount(array $spec, string $key, int $min, int $max): void
    {
        $count = count($spec[$key] ?? []);
        if ($count < $min || $count > $max) {
            throw new InvalidArgumentException("Puzzle \"{$spec['title']}\": \"{$key}\" needs {$min}–{$max} entries, got {$count}.");
        }
    }

    private function freePosition($query, string $column, int $wanted): int
    {
        if (! (clone $query)->where($column, $wanted)->exists()) {
            return $wanted;
        }

        return (int) (clone $query)->max($column) + 1;
    }
}
