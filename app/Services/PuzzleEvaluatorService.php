<?php

namespace App\Services;

use App\Models\Puzzle;

class PuzzleEvaluatorService
{
    /**
     * Authoritative server-side evaluation of student submission.
     * Evaluates item IDs, mappings, and order against the private answer key.
     */
    public function evaluate(Puzzle $puzzle, array $submission): array
    {
        $answerKey = $puzzle->correct_answer ?? [];
        $type = $puzzle->puzzle_type;
        $maxPoints = (int) ($puzzle->points ?: 100);

        $isCorrect = false;
        $score = 0;
        $feedback = '';

        switch ($type) {
            case 'identification':
                $selected = $submission['selected_option'] ?? ($submission['choice_id'] ?? null);
                $expected = is_array($answerKey)
                    ? ($answerKey['correct_option'] ?? ($answerKey['correct_choice'] ?? ($answerKey[0] ?? null)))
                    : $answerKey;
                $isCorrect = ($selected !== null && (string)$selected === (string)$expected);
                $score = $isCorrect ? $maxPoints : 0;
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Correct identification! Device classification verified.')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Incorrect selection. Review device characteristics.'));
                break;

            case 'matching':
                $pairs = $submission['pairs'] ?? [];
                $expectedPairs = is_array($answerKey) ? ($answerKey['pairs'] ?? $answerKey) : [];
                $totalPairs = count($expectedPairs);
                $matchedCount = 0;

                if ($totalPairs > 0) {
                    foreach ($expectedPairs as $lId => $rId) {
                        if (isset($pairs[$lId]) && (string)$pairs[$lId] === (string)$rId) {
                            $matchedCount++;
                        }
                    }
                    $isCorrect = ($matchedCount === $totalPairs);
                    $score = (int) round(($matchedCount / $totalPairs) * $maxPoints);
                }
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'All protocol pairings mapped successfully!')
                    : "Matched {$matchedCount} of {$totalPairs} items correctly. " . ($puzzle->incorrect_feedback ?: 'Review OSI architectural relationships.');
                break;

            case 'sequencing':
                $submittedOrder = $submission['ordered_ids'] ?? ($submission['correct_order'] ?? []);
                $expectedOrder = is_array($answerKey) ? ($answerKey['order'] ?? ($answerKey['correct_order'] ?? $answerKey)) : [];
                $totalItems = count($expectedOrder);

                if ($totalItems > 0 && count($submittedOrder) === $totalItems) {
                    $correctPositions = 0;
                    foreach ($expectedOrder as $index => $expectedId) {
                        if (isset($submittedOrder[$index]) && (string)$submittedOrder[$index] === (string)$expectedId) {
                            $correctPositions++;
                        }
                    }
                    $isCorrect = ($correctPositions === $totalItems);
                    $score = (int) round(($correctPositions / $totalItems) * $maxPoints);
                }
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'PDU encapsulation sequence constructed perfectly!')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Sequence error detected in transmission order.'));
                break;

            case 'classification':
                $placements = $submission['placements'] ?? ($submission['mapping'] ?? []);
                $expectedMapping = is_array($answerKey) ? ($answerKey['mapping'] ?? $answerKey) : [];
                $totalItems = count($expectedMapping);
                $correctCount = 0;

                if ($totalItems > 0) {
                    foreach ($expectedMapping as $itemId => $catId) {
                        if (isset($placements[$itemId]) && (string)$placements[$itemId] === (string)$catId) {
                            $correctCount++;
                        }
                    }
                    $isCorrect = ($correctCount === $totalItems);
                    $score = (int) round(($correctCount / $totalItems) * $maxPoints);
                }
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'All items classified into their correct operational domains!')
                    : "Correctly classified {$correctCount} of {$totalItems} items. " . ($puzzle->incorrect_feedback ?: 'Inspect domain boundaries.');
                break;

            case 'drag_and_drop':
                $placements = $submission['placements'] ?? ($submission['draggables'] ?? []);
                $expected = is_array($answerKey) ? ($answerKey['placements'] ?? $answerKey) : [];

                if (isset($expected['nodes']) && is_array($expected['nodes'])) {
                    // Dynamic topology validation
                    $nodeMatch = 0;
                    $expectedNodes = $expected['nodes'];
                    $submittedNodes = $submission['nodes'] ?? [];
                    foreach ($expectedNodes as $expNode) {
                        foreach ($submittedNodes as $subNode) {
                            if (($expNode['id'] ?? null) === ($subNode['id'] ?? null) &&
                                ($expNode['expected_ip'] ?? null) === ($subNode['ip'] ?? ($subNode['expected_ip'] ?? null))) {
                                $nodeMatch++;
                            }
                        }
                    }
                    $isCorrect = (count($expectedNodes) > 0 && $nodeMatch === count($expectedNodes));
                    $score = $isCorrect ? $maxPoints : (int) round(($nodeMatch / max(1, count($expectedNodes))) * $maxPoints);
                } else {
                    $total = count($expected);
                    $matches = 0;
                    foreach ($expected as $dragId => $zoneId) {
                        if (isset($placements[$dragId]) && (string)$placements[$dragId] === (string)$zoneId) {
                            $matches++;
                        }
                    }
                    $isCorrect = ($total > 0 && $matches === $total);
                    $score = $total > 0 ? (int) round(($matches / $total) * $maxPoints) : 0;
                }
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Hardware and topology mapping verified on all layers!')
                    : ($puzzle->incorrect_feedback ?: 'Infrastructure deployment error. Review device layer specs.');
                break;

            case 'scenario':
                $choice = $submission['choice_id'] ?? ($submission['selected_option'] ?? null);
                $expected = is_array($answerKey) ? ($answerKey['correct_choice'] ?? ($answerKey[0] ?? null)) : $answerKey;
                $isCorrect = ($choice !== null && (string)$choice === (string)$expected);
                $score = $isCorrect ? $maxPoints : 0;
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Optimal architectural decision verified!')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Suboptimal decision. Review collision domain rules.'));
                break;
        }

        $percentage = (int) round(($score / max(1, $maxPoints)) * 100);

        return [
            'is_correct' => $isCorrect,
            'score'      => $score,
            'max_score'  => $maxPoints,
            'percentage' => $percentage,
            'feedback'   => $feedback,
            'hint'       => $isCorrect ? null : ($puzzle->hint ?? 'Review PDU flow and layer boundaries.'),
        ];
    }
}
