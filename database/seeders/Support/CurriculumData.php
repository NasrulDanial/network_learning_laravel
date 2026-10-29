<?php

namespace Database\Seeders\Support;

/**
 * The full curriculum: the five original stages plus the stages added later, with every
 * quiz brought up to QUESTIONS_PER_QUIZ questions. Keeping the assembly in one place lets the
 * seeder and the tests agree on what "the curriculum" is.
 */
class CurriculumData
{
    public const QUESTIONS_PER_QUIZ = 15;

    public static function all(): array
    {
        $dir = dirname(__DIR__) . '/data/';

        $base = require $dir . 'netlearn_curriculum.php';
        $extraQuestions = require $dir . 'netlearn_expansion_extra_questions.php';
        $stagesA = require $dir . 'netlearn_expansion_stages_a.php';
        $stagesB = require $dir . 'netlearn_expansion_stages_b.php';
        $deepNotes = array_merge(
            require $dir . 'netlearn_deep_notes_a.php',
            require $dir . 'netlearn_deep_notes_b.php'
        );

        foreach ($base as &$topic) {
            $topic['quiz']['questions'] = array_merge(
                $topic['quiz']['questions'],
                $extraQuestions[$topic['title']] ?? []
            );
        }
        unset($topic);

        $all = array_merge($base, $stagesA, $stagesB);
        foreach ($all as &$topic) {
            $topic['quiz']['target'] = self::QUESTIONS_PER_QUIZ;

            // A stage that already exists keeps its own notes (they become the Introduction) and
            // gains the in-depth sections after them, once.
            $topic['append_notes'] = true;

            // In-depth notes replace the first-draft notes. The first draft is remembered so the
            // seeder can upgrade notes nobody has edited and leave edited ones alone.
            if (isset($deepNotes[$topic['title']])) {
                $topic['previous_content'] = $topic['content'];
                $topic['content'] = $deepNotes[$topic['title']];
            }
        }
        unset($topic);

        return $all;
    }
}
