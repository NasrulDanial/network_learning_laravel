<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use Database\Seeders\CurriculumSeeder;
use Tests\TestCase;

/** The seeder tops up stages that already exist (as in the development database) instead of duplicating them. */
class CurriculumTopUpTest extends TestCase
{
    public function test_an_existing_stage_is_topped_up_not_duplicated()
    {
        $author = \App\Models\User::create([
            'name' => 'Author', 'email' => 'author_' . uniqid() . '@netlearn.test',
            'password' => \Illuminate\Support\Facades\Hash::make('x'), 'role' => 'lecturer',
        ]);
        // A same-titled stage may already be in the shared test database: only ours is checked
        Topic::where('title', 'DNS and Web Communication')->update(['title' => 'Renamed for the test']);
        $existing = Topic::create([
            'created_by' => $author->user_id,
            'title' => 'DNS and Web Communication', 'topic_order' => 77, 'status' => 'published',
            'description' => 'Original description', 'content' => "DNS stands for Domain Name System.\n\nOriginal notes.",
        ]);
        $quiz = Quiz::create(['topic_id' => $existing->topic_id, 'title' => 'Original DNS Quiz', 'passing_mark' => 70]);
        foreach (range(1, 5) as $i) {
            Question::create([
                'quiz_id' => $quiz->quiz_id, 'question_text' => "Original question {$i}",
                'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'A',
            ]);
        }
        Mission::create([
            'topic_id' => $existing->topic_id, 'title' => 'Original core lab', 'difficulty' => 'beginner',
            'mission_order' => 1, 'pass_mark' => 70, 'xp_reward' => 100, 'status' => 'published',
        ]);

        $this->seed(CurriculumSeeder::class);
        $this->seed(CurriculumSeeder::class); // and again: nothing more happens

        $this->assertSame(1, Topic::where('title', 'DNS and Web Communication')->count());
        $existing->refresh();
        $this->assertSame('Original description', $existing->description);
        $this->assertNotEmpty($existing->objectives);

        // Quiz holds exactly 15: the 5 originals plus 10 new ones, still a single quiz
        $this->assertSame(1, Quiz::where('topic_id', $existing->topic_id)->count());
        $this->assertSame(15, Question::where('quiz_id', $quiz->quiz_id)->count());
        $this->assertSame(5, Question::where('quiz_id', $quiz->quiz_id)->where('question_text', 'like', 'Original question%')->count());

        // The original mission stays and three new ones are added
        $this->assertSame(4, Mission::where('topic_id', $existing->topic_id)->count());

        // Existing notes stay first, the new sections follow and split into headings
        $this->assertStringStartsWith('DNS stands for Domain Name System.', $existing->content);
        $titles = array_column($existing->noteSections(), 'title');
        $this->assertContains('HOW A LOOKUP WORKS', $titles);
        $this->assertSame(1, substr_count($existing->content, 'HOW A LOOKUP WORKS'));
    }

    public function test_untouched_first_draft_notes_are_upgraded_and_other_notes_are_kept_as_the_introduction()
    {
        $this->seed(CurriculumSeeder::class);

        $spec = collect(\Database\Seeders\Support\CurriculumData::all())->keyBy('title');
        $draft = Topic::where('title', 'Network Devices')->firstOrFail();
        $edited = Topic::where('title', 'IP Addressing')->firstOrFail();
        $deep = $draft->content;

        // Put the first-draft text back on one stage and a lecturer's own text on another
        $draft->update(['content' => $spec['Network Devices']['previous_content']]);
        $edited->update(['content' => "A lecturer rewrote these notes.\n\nSUBNET CHEAT SHEET\nMine."]);

        $this->seed(CurriculumSeeder::class);

        $this->assertSame($deep, $draft->fresh()->content);
        // The lecturer's text stays first; the in-depth sections follow it exactly once
        $this->assertStringStartsWith('A lecturer rewrote', $edited->fresh()->content);
        $this->assertStringContainsString('SUBNET CHEAT SHEET', $edited->fresh()->content);
        $this->assertSame(1, substr_count($edited->fresh()->content, 'WHAT AN IP ADDRESS IS'));
    }

    public function test_a_stage_whose_notes_were_extended_by_the_first_seed_is_upgraded()
    {
        $this->seed(CurriculumSeeder::class);
        $spec = collect(\Database\Seeders\Support\CurriculumData::all())->keyBy('title');
        $dns = Topic::where('title', 'DNS and Web Communication')->firstOrFail();
        $deep = $dns->content;

        // What the earlier seed left behind: the original text followed by the first-draft sections
        $dns->update(['content' => "DNS stands for Domain Name System.\n\n" . $spec['DNS and Web Communication']['previous_content']]);
        $this->seed(CurriculumSeeder::class);

        $this->assertSame($deep, $dns->fresh()->content);
    }
}
