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

        foreach ($base as &$topic) {
            $topic['quiz']['questions'] = array_merge(
                $topic['quiz']['questions'],
                $extraQuestions[$topic['title']] ?? []
            );
        }
        unset($topic);

        // Stages that may already exist (titles match) keep their own notes and gain the new sections
        foreach ($stagesA as &$topic) {
            $topic['append_notes'] = true;
        }
        unset($topic);

        $all = array_merge($base, $stagesA, $stagesB);
        foreach ($all as &$topic) {
            $topic['quiz']['target'] = self::QUESTIONS_PER_QUIZ;
        }
        unset($topic);

        return $all;
    }
}
