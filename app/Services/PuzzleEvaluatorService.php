<?php

namespace App\Services;

use App\Models\Puzzle;

class PuzzleEvaluatorService
{
    /**
     * Authoritative server-side evaluation of a student submission against the private answer key.
     *
     * Besides the verdict it returns `diagnostics` so the client can animate where the answer
     * failed: only the position/item that is wrong is disclosed, never what the right answer is.
     */
    public function evaluate(Puzzle $puzzle, array $submission): array
    {
        $answerKey = $puzzle->correct_answer ?? [];
        $data = $puzzle->activity_data ?? [];
        $type = $puzzle->puzzle_type;
        $maxPoints = (int) ($puzzle->points ?: 100);

        $isCorrect = false;
        $score = 0;
        $feedback = '';
        $consequence = null;
        $diagnostics = ['kind' => 'choice', 'first_error' => null, 'correct_count' => 0, 'total' => 1];

        switch ($type) {
            case 'identification':
                $selected = $submission['selected_option'] ?? ($submission['choice_id'] ?? null);
                $expected = is_array($answerKey)
                    ? ($answerKey['correct_option'] ?? ($answerKey['correct_choice'] ?? ($answerKey[0] ?? null)))
                    : $answerKey;
                $isCorrect = ($selected !== null && (string) $selected === (string) $expected);
                $score = $isCorrect ? $maxPoints : 0;
                $diagnostics['correct_count'] = $isCorrect ? 1 : 0;
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Correct identification! Device classification verified.')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Incorrect selection. Review device characteristics.'));
                break;

            case 'matching':
                $pairs = $submission['pairs'] ?? [];
                $expectedPairs = is_array($answerKey) ? ($answerKey['pairs'] ?? $answerKey) : [];
                $totalPairs = count($expectedPairs);
                $matchedCount = 0;
                $firstError = null;

                foreach ($expectedPairs as $lId => $rId) {
                    if (isset($pairs[$lId]) && (string) $pairs[$lId] === (string) $rId) {
                        $matchedCount++;
                    } elseif ($firstError === null) {
                        $firstError = (string) $lId;
                    }
                }
                if ($totalPairs > 0) {
                    $isCorrect = ($matchedCount === $totalPairs);
                    $score = (int) round(($matchedCount / $totalPairs) * $maxPoints);
                }
                $diagnostics = ['kind' => 'pairs', 'first_error' => $isCorrect ? null : $firstError, 'correct_count' => $matchedCount, 'total' => $totalPairs];
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'All protocol pairings mapped successfully!')
                    : "Matched {$matchedCount} of {$totalPairs} items correctly. " . ($puzzle->incorrect_feedback ?: 'Review OSI architectural relationships.');
                break;

            case 'sequencing':
                $submittedOrder = array_values($submission['ordered_ids'] ?? ($submission['correct_order'] ?? []));
                $expectedOrder = array_values(is_array($answerKey) ? ($answerKey['order'] ?? ($answerKey['correct_order'] ?? $answerKey)) : []);
                $totalItems = count($expectedOrder);
                $correctPositions = 0;
                $firstError = null;

                foreach ($expectedOrder as $index => $expectedId) {
                    if (isset($submittedOrder[$index]) && (string) $submittedOrder[$index] === (string) $expectedId) {
                        $correctPositions++;
                    } elseif ($firstError === null) {
                        $firstError = $index;
                    }
                }
                if ($totalItems > 0 && count($submittedOrder) === $totalItems) {
                    $isCorrect = ($correctPositions === $totalItems);
                    $score = (int) round(($correctPositions / $totalItems) * $maxPoints);
                }
                $diagnostics = ['kind' => 'sequence', 'first_error' => $isCorrect ? null : ($firstError ?? 0), 'correct_count' => $correctPositions, 'total' => $totalItems];
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'PDU encapsulation sequence constructed perfectly!')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Sequence error detected in transmission order.'));
                break;

            case 'classification':
                $placements = $submission['placements'] ?? ($submission['mapping'] ?? []);
                $expectedMapping = is_array($answerKey) ? ($answerKey['mapping'] ?? $answerKey) : [];
                $totalItems = count($expectedMapping);
                $correctCount = 0;
                $firstError = null;

                // Walk items in display order so the "first" error is stable for the animation
                $itemOrder = array_column($data['items'] ?? [], 'id') ?: array_keys($expectedMapping);
                foreach ($itemOrder as $itemId) {
                    if (! array_key_exists($itemId, $expectedMapping)) {
                        continue;
                    }
                    if (isset($placements[$itemId]) && (string) $placements[$itemId] === (string) $expectedMapping[$itemId]) {
                        $correctCount++;
                    } elseif ($firstError === null) {
                        $firstError = (string) $itemId;
                    }
                }
                if ($totalItems > 0) {
                    $isCorrect = ($correctCount === $totalItems);
                    $score = (int) round(($correctCount / $totalItems) * $maxPoints);
                }
                $diagnostics = ['kind' => 'placement', 'first_error' => $isCorrect ? null : $firstError, 'correct_count' => $correctCount, 'total' => $totalItems];
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'All items classified into their correct operational domains!')
                    : "Correctly classified {$correctCount} of {$totalItems} items. " . ($puzzle->incorrect_feedback ?: 'Inspect domain boundaries.');
                break;

            case 'drag_and_drop':
                $placements = $submission['placements'] ?? ($submission['draggables'] ?? []);
                $expected = is_array($answerKey) ? ($answerKey['placements'] ?? $answerKey) : [];

                if (isset($expected['nodes']) && is_array($expected['nodes'])) {
                    // Topology nodes: match by node id + expected IP
                    $nodeMatch = 0;
                    $firstError = null;
                    $submittedNodes = collect($submission['nodes'] ?? [])->keyBy('id');
                    foreach ($expected['nodes'] as $expNode) {
                        $sub = $submittedNodes->get($expNode['id'] ?? null);
                        $ok = $sub && ($expNode['expected_ip'] ?? null) === ($sub['ip'] ?? ($sub['expected_ip'] ?? null));
                        $ok ? $nodeMatch++ : ($firstError ??= (string) ($expNode['id'] ?? ''));
                    }
                    $totalNodes = count($expected['nodes']);
                    $isCorrect = ($totalNodes > 0 && $nodeMatch === $totalNodes);
                    $score = $isCorrect ? $maxPoints : (int) round(($nodeMatch / max(1, $totalNodes)) * $maxPoints);
                    $diagnostics = ['kind' => 'placement', 'first_error' => $isCorrect ? null : $firstError, 'correct_count' => $nodeMatch, 'total' => $totalNodes];
                } else {
                    $total = count($expected);
                    $matches = 0;
                    foreach ($expected as $dragId => $zoneId) {
                        if (isset($placements[$dragId]) && (string) $placements[$dragId] === (string) $zoneId) {
                            $matches++;
                        }
                    }
                    $isCorrect = ($total > 0 && $matches === $total);
                    $score = $total > 0 ? (int) round(($matches / $total) * $maxPoints) : 0;

                    // Zone-based: walk zones in the order the packet travels; report the first zone
                    // holding the wrong device (or none). Says where, never which device belongs there.
                    $firstError = null;
                    if (! $isCorrect) {
                        $zoneOrder = array_column($data['drop_zones'] ?? [], 'id') ?: array_values(array_unique($expected));
                        foreach ($zoneOrder as $zoneId) {
                            $placedHere = array_keys(array_filter($placements, fn ($z) => (string) $z === (string) $zoneId));
                            $belongsHere = array_keys(array_filter($expected, fn ($z) => (string) $z === (string) $zoneId));
                            sort($placedHere);
                            sort($belongsHere);
                            if (array_map('strval', $placedHere) !== array_map('strval', $belongsHere)) {
                                $firstError = (string) $zoneId;
                                break;
                            }
                        }
                    }
                    $diagnostics = ['kind' => 'placement', 'first_error' => $firstError, 'correct_count' => $matches, 'total' => $total];
                }
                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Hardware and topology mapping verified on all layers!')
                    : ($puzzle->incorrect_feedback ?: 'Infrastructure deployment error. Review device layer specs.');
                break;

            case 'scenario':
                $choice = $submission['choice_id'] ?? ($submission['selected_option'] ?? null);
                $expected = is_array($answerKey) ? ($answerKey['correct_choice'] ?? ($answerKey[0] ?? null)) : $answerKey;
                $isCorrect = ($choice !== null && (string) $choice === (string) $expected);
                $score = $isCorrect ? $maxPoints : 0;
                $diagnostics['correct_count'] = $isCorrect ? 1 : 0;

                // Lecturer-authored consequence of the choice the student actually made (revealed after submit)
                foreach ($data['choices'] ?? [] as $c) {
                    if ($choice !== null && (string) ($c['id'] ?? '') === (string) $choice) {
                        $consequence = filled($c['consequence'] ?? null) ? (string) $c['consequence'] : null;
                        break;
                    }
                }

                $feedback = $isCorrect
                    ? ($puzzle->correct_feedback ?: 'Optimal architectural decision verified!')
                    : ($puzzle->incorrect_feedback ?: ($puzzle->explanation ?: 'Suboptimal decision. Review collision domain rules.'));
                break;
        }

        if (config('netlearn.diagnostics_level') === 'none') {
            $diagnostics['first_error'] = null;
        }

        $percentage = (int) round(($score / max(1, $maxPoints)) * 100);

        return [
            'is_correct' => $isCorrect,
            'score' => $score,
            'max_score' => $maxPoints,
            'percentage' => $percentage,
            'feedback' => $feedback,
            'hint' => $isCorrect ? null : ($puzzle->hint ?? 'Review PDU flow and layer boundaries.'),
            'diagnostics' => $diagnostics,
            'consequence' => $consequence,
        ];
    }
}
