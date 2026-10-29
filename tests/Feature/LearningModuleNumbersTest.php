<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsLearningContent;
use Tests\TestCase;

/** The learning module shows what the student actually got in the run, quiz and debrief. */
class LearningModuleNumbersTest extends TestCase
{
    use BuildsLearningContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStudent();
    }

    public function test_activity_rows_show_xp_earned_the_attempt_it_was_solved_on_and_hints()
    {
        $topic = $this->makeTopic();
        $mission = $this->makeMission($topic, 1, 150);
        $puzzle = $this->makeIdentification($mission, 1);
        $this->makeIdentification($mission, 2);

        $this->actingAs($this->student)->postJson(route('puzzles.hint', $puzzle->puzzle_id))->assertJson(['charged' => true]);
        $this->answerWrong($puzzle);
        $this->answerWrong($puzzle);
        $this->solve($puzzle);
        $this->solve($puzzle); // a later replay must not change what was shown for the first solve

        $page = $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk();
        $page->assertSee('90/100 XP earned')
            ->assertSee('Solved on attempt 3')
            ->assertSee('4 in total, counting replays')
            ->assertSee('Hint used (−10 XP)')
            ->assertSee('100 XP available')          // the other activity, not played yet
            ->assertDontSee('Best:');
    }

    public function test_mission_xp_matches_the_debrief_and_the_run_health_is_shown_mid_run()
    {
        $topic = $this->makeTopic();
        $mission = $this->makeMission($topic, 1, 150);
        $a = $this->makeIdentification($mission, 1);
        $b = $this->makeIdentification($mission, 2);

        $this->answerWrong($a);
        $this->solve($a);

        $page = $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk();
        $page->assertSee('100 / 350 XP')             // 100 earned of 2 x 100 + 150
            ->assertSee('Run health 76/100');

        $this->solve($b);
        $done = $this->actingAs($this->student)->get(route('student.topic', $topic))->assertOk();
        $done->assertSee('350 / 350 XP')->assertDontSee('Run health');

        $summary = $this->actingAs($this->student)->getJson(route('student.mission.summary', $mission))->json();
        $this->assertSame(350, $summary['totalXpThisMission']);
        $this->assertSame(['runHealth' => 76, 'runStars' => 3, 'stars' => 3], ['runHealth' => $summary['runHealth'], 'runStars' => $summary['runStars'], 'stars' => $summary['stars']]);
    }

    public function test_quiz_numbers_show_attempts_best_and_last_result()
    {
        $topic = $this->makeTopic();
        $quiz = $this->makeQuiz($topic, 2, 100);
        $qids = $quiz->questions()->pluck('question_id');

        // First attempt 100%, second attempt 50%
        foreach ([['B', 'B'], ['B', 'A']] as $answers) {
            foreach ($answers as $i => $answer) {
                $this->actingAs($this->student)->postJson(route('student.quiz.check', $quiz), ['question_id' => $qids[$i], 'answer' => $answer]);
            }
            $this->actingAs($this->student)->post(route('student.quiz.submit'), ['quiz_id' => $quiz->quiz_id]);
        }

        $this->actingAs($this->student)->get(route('student.topic', $topic))
            ->assertSee('2 attempts')->assertSee('best')->assertSee('100%')->assertSee('last')->assertSee('50%');
        $this->actingAs($this->student)->get(route('student.quiz'))->assertSee('Attempts')->assertSee('Best');
    }
}
