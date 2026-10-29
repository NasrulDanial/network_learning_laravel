<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Mission;
use App\Models\Puzzle;
use App\Models\PuzzleAttempt;
use App\Models\UserMissionProgress;
use App\Services\PuzzleEvaluatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MissionController extends Controller
{
    // Duplicate positions make the student sequence and unlock order ambiguous
    private const ORDER_MESSAGES = [
        'mission_order.unique'  => 'Another mission in this topic already uses this order number. Choose a free position.',
        'activity_order.unique' => 'Another activity in this mission already uses this order number. Choose a free position.',
    ];

    /**
     * Display a listing of missions for a given topic or all missions.
     */
    public function index(Request $request, ?Topic $topic = null)
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();
        $selectedTopic = $topic ?? ($request->filled('topic_id') ? Topic::find($request->topic_id) : $topics->first());

        $missions = $selectedTopic
            ? Mission::where('topic_id', $selectedTopic->topic_id)
                ->withCount('puzzles')
                ->orderBy('mission_order', 'asc')
                ->get()
            : collect();

        return view('lecturer.missions.index', [
            'pageTitle'     => 'Manage Missions & Puzzle Activities',
            'activePage'    => 'missions',
            'topics'        => $topics,
            'selectedTopic' => $selectedTopic,
            'missions'      => $missions,
        ]);
    }

    /**
     * Show the form for creating a new mission.
     */
    public function create(Request $request, ?Topic $topic = null)
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();
        $selectedTopic = $topic ?? ($request->filled('topic_id') ? Topic::find($request->topic_id) : $topics->first());

        $nextOrder = $selectedTopic
            ? (Mission::where('topic_id', $selectedTopic->topic_id)->max('mission_order') + 1)
            : 1;

        return view('lecturer.missions.create', [
            'pageTitle'     => 'Create Learning Mission',
            'activePage'    => 'missions',
            'topics'        => $topics,
            'selectedTopic' => $selectedTopic,
            'nextOrder'     => $nextOrder,
        ]);
    }

    /**
     * Store a newly created mission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic_id'      => 'required|integer|exists:topics,topic_id',
            'title'         => 'required|string|max:150',
            'description'   => 'nullable|string',
            'objective'     => 'nullable|string',
            'difficulty'    => 'required|in:beginner,intermediate,advanced',
            'mission_order' => ['required', 'integer', 'min:1',
                Rule::unique('missions', 'mission_order')->where('topic_id', $request->input('topic_id'))],
            // Missions are cleared by solving every activity; pass_mark is kept only for old data
            'pass_mark'     => 'nullable|integer|min:0|max:100',
            'xp_reward'     => 'required|integer|min:25|max:1000',
            'status'        => 'required|in:draft,published',
            'ticket_code'   => 'nullable|string|max:20',
            'briefing_text' => 'nullable|string|max:500',
            'debrief_text'  => 'nullable|string|max:300',
            'narrator'      => 'nullable|string|max:60',
        ], self::ORDER_MESSAGES);

        $validated['pass_mark'] = $validated['pass_mark'] ?? 70;
        $mission = Mission::create($validated);

        return redirect()
            ->route('lecturer.missions.show', $mission->mission_id)
            ->with('success', "Mission '{$mission->title}' created successfully. Now add interactive puzzle activities.");
    }

    /**
     * Display a mission and its ordered puzzle activities.
     */
    public function show(Mission $mission)
    {
        $mission->load(['topic', 'puzzles']);

        return view('lecturer.missions.show', [
            'pageTitle'  => 'Mission Details & Activities',
            'activePage' => 'missions',
            'mission'    => $mission,
            'puzzles'    => $mission->puzzles,
        ]);
    }

    /**
     * Show form to edit mission.
     */
    public function edit(Mission $mission)
    {
        $topics = Topic::orderBy('topic_order', 'asc')->get();

        return view('lecturer.missions.edit', [
            'pageTitle'  => 'Edit Mission',
            'activePage' => 'missions',
            'mission'    => $mission,
            'topics'     => $topics,
        ]);
    }

    /**
     * Update mission metadata.
     */
    public function update(Request $request, Mission $mission)
    {
        $validated = $request->validate([
            'topic_id'      => 'required|integer|exists:topics,topic_id',
            'title'         => 'required|string|max:150',
            'description'   => 'nullable|string',
            'objective'     => 'nullable|string',
            'difficulty'    => 'required|in:beginner,intermediate,advanced',
            'mission_order' => ['required', 'integer', 'min:1',
                Rule::unique('missions', 'mission_order')
                    ->where('topic_id', $request->input('topic_id'))
                    ->ignore($mission->mission_id, 'mission_id')],
            // Missions are cleared by solving every activity; pass_mark is kept only for old data
            'pass_mark'     => 'nullable|integer|min:0|max:100',
            'xp_reward'     => 'required|integer|min:25|max:1000',
            'status'        => 'required|in:draft,published',
            'ticket_code'   => 'nullable|string|max:20',
            'briefing_text' => 'nullable|string|max:500',
            'debrief_text'  => 'nullable|string|max:300',
            'narrator'      => 'nullable|string|max:60',
        ], self::ORDER_MESSAGES);

        $oldTopicId = $mission->topic_id;
        $topicChanged = (int) $oldTopicId !== (int) $validated['topic_id'];

        // Moving a mission with student history would split that history across topics
        if ($topicChanged) {
            $history = PuzzleAttempt::whereIn('puzzle_id', $mission->puzzles()->pluck('puzzle_id'))->count()
                + UserMissionProgress::where('mission_id', $mission->mission_id)->count();
            if ($history > 0) {
                return back()->withInput()->withErrors([
                    'topic_id' => 'This mission already has student attempts, so it cannot be moved to another topic. Create a new mission in that topic instead.',
                ]);
            }
        }

        unset($validated['pass_mark']); // not on the form any more: keep the stored value
        $mission->update($validated);

        // Keep child puzzles synchronized with mission topic
        if ($topicChanged) {
            $mission->puzzles()->update(['topic_id' => $validated['topic_id']]);
        }

        return redirect()
            ->route('lecturer.missions.show', $mission->mission_id)
            ->with('success', 'Mission updated successfully.');
    }

    /**
     * Delete a mission and its puzzles.
     */
    public function destroy(Mission $mission)
    {
        // Puzzles and attempts cascade from a mission; refuse rather than erase student history
        $attempts = PuzzleAttempt::whereIn('puzzle_id', $mission->puzzles()->pluck('puzzle_id'))->count()
            + UserMissionProgress::where('mission_id', $mission->mission_id)->count();
        if ($attempts > 0) {
            return redirect()
                ->route('lecturer.missions.show', $mission->mission_id)
                ->with('error', "\"{$mission->title}\" has {$attempts} student attempt/progress record(s) and cannot be deleted. Set it to Draft to hide it from students instead.");
        }

        $topicId = $mission->topic_id;
        $title = $mission->title;
        $mission->delete();

        return redirect()
            ->route('lecturer.missions.index', ['topic_id' => $topicId])
            ->with('success', "Mission '{$title}' removed.");
    }

    /**
     * Show the code-free form to add an activity inside a mission.
     */
    public function createPuzzle(Request $request, Mission $mission)
    {
        $type = $request->query('type', 'identification');
        $validTypes = ['identification', 'matching', 'sequencing', 'classification', 'drag_and_drop', 'scenario'];
        if (!in_array($type, $validTypes)) {
            $type = 'identification';
        }

        $nextOrder = $mission->puzzles()->max('activity_order') + 1;

        return view('lecturer.missions.create_puzzle', [
            'pageTitle'     => 'Add Puzzle Activity',
            'activePage'    => 'missions',
            'mission'       => $mission,
            'selectedType'  => $type,
            'nextOrder'     => $nextOrder,
        ]);
    }

    /**
     * Store newly authored puzzle activity.
     */
    public function storePuzzle(Request $request, Mission $mission)
    {
        // Common base validation
        $validated = $request->validate([
            'puzzle_type'         => 'required|in:identification,matching,sequencing,classification,drag_and_drop,scenario',
            'question_text'       => 'required|string|max:255',
            'instruction'         => 'required|string|max:500',
            'points'              => 'required|integer|min:25|max:500',
            'activity_order'      => ['required', 'integer', 'min:1',
                Rule::unique('puzzles', 'activity_order')->where('mission_id', $mission->mission_id)],
            'difficulty'          => 'required|in:beginner,intermediate,advanced',
            'max_attempts'        => 'nullable|integer|min:0|max:100',
            'hint'                => 'nullable|string|max:255',
            'explanation'         => 'nullable|string|max:1000',
            'correct_feedback'    => 'nullable|string|max:255',
            'incorrect_feedback'  => 'nullable|string|max:255',
            'status'              => 'required|in:draft,published',
        ], self::ORDER_MESSAGES);

        $puzzleType = $validated['puzzle_type'];

        // Process type-specific structured data and answer key
        $processed = $this->processActivityPayload($request, $puzzleType);
        if ($processed['error']) {
            return back()->withInput()->withErrors(['activity_data' => $processed['error']]);
        }

        $puzzle = Puzzle::create([
            'mission_id'          => $mission->mission_id,
            'topic_id'            => $mission->topic_id,
            'puzzle_type'         => $puzzleType,
            'question_text'       => $validated['question_text'],
            'instruction'         => $validated['instruction'],
            'points'              => $validated['points'],
            'activity_order'      => $validated['activity_order'],
            'difficulty'          => $validated['difficulty'],
            'max_attempts'        => $validated['max_attempts'] ?? 0,
            'hint'                => $validated['hint'] ?? null,
            'explanation'         => $validated['explanation'] ?? null,
            'correct_feedback'    => $validated['correct_feedback'] ?? null,
            'incorrect_feedback'  => $validated['incorrect_feedback'] ?? null,
            'activity_data'       => $processed['activity_data'],
            'correct_answer'      => $processed['correct_answer'],
            'status'              => $validated['status'],
        ]);

        return redirect()
            ->route('lecturer.missions.show', $mission->mission_id)
            ->with('success', "Activity '{$puzzle->question_text}' saved successfully.");
    }

    /**
     * Edit an activity.
     */
    public function editPuzzle(Puzzle $puzzle)
    {
        $puzzle->load('mission');

        return view('lecturer.missions.edit_puzzle', [
            'pageTitle'  => 'Edit Puzzle Activity',
            'activePage' => 'missions',
            'puzzle'     => $puzzle,
            'mission'    => $puzzle->mission,
        ]);
    }

    /**
     * Update an activity.
     */
    public function updatePuzzle(Request $request, Puzzle $puzzle)
    {
        $puzzleType = $puzzle->puzzle_type;

        $validated = $request->validate([
            'question_text'       => 'required|string|max:255',
            'instruction'         => 'required|string|max:500',
            'points'              => 'required|integer|min:25|max:500',
            'activity_order'      => ['required', 'integer', 'min:1',
                Rule::unique('puzzles', 'activity_order')
                    ->where('mission_id', $puzzle->mission_id)
                    ->ignore($puzzle->puzzle_id, 'puzzle_id')],
            'difficulty'          => 'required|in:beginner,intermediate,advanced',
            'max_attempts'        => 'nullable|integer|min:0|max:100',
            'hint'                => 'nullable|string|max:255',
            'explanation'         => 'nullable|string|max:1000',
            'correct_feedback'    => 'nullable|string|max:255',
            'incorrect_feedback'  => 'nullable|string|max:255',
            'status'              => 'required|in:draft,published',
        ], self::ORDER_MESSAGES);

        $processed = $this->processActivityPayload($request, $puzzleType);
        if ($processed['error']) {
            return back()->withInput()->withErrors(['activity_data' => $processed['error']]);
        }

        $puzzle->update([
            'question_text'       => $validated['question_text'],
            'instruction'         => $validated['instruction'],
            'points'              => $validated['points'],
            'activity_order'      => $validated['activity_order'],
            'difficulty'          => $validated['difficulty'],
            'max_attempts'        => $validated['max_attempts'] ?? 0,
            'hint'                => $validated['hint'] ?? null,
            'explanation'         => $validated['explanation'] ?? null,
            'correct_feedback'    => $validated['correct_feedback'] ?? null,
            'incorrect_feedback'  => $validated['incorrect_feedback'] ?? null,
            'activity_data'       => $processed['activity_data'],
            'correct_answer'      => $processed['correct_answer'],
            'status'              => $validated['status'],
        ]);

        return redirect()
            ->route('lecturer.missions.show', $puzzle->mission_id)
            ->with('success', 'Activity updated successfully.');
    }

    /**
     * Delete an activity.
     */
    public function destroyPuzzle(Puzzle $puzzle)
    {
        $attempts = $puzzle->attempts()->count();
        if ($attempts > 0) {
            return redirect()
                ->route('lecturer.missions.show', $puzzle->mission_id)
                ->with('error', "This activity has {$attempts} student attempt(s) and cannot be deleted. Set it to Draft to hide it from students instead.");
        }

        $missionId = $puzzle->mission_id;
        $puzzle->delete();

        return redirect()
            ->route('lecturer.missions.show', $missionId)
            ->with('success', 'Activity removed.');
    }

    /**
     * Toggle status between draft and published.
     */
    public function togglePuzzleStatus(Puzzle $puzzle)
    {
        $newStatus = $puzzle->status === 'published' ? 'draft' : 'published';
        $puzzle->update(['status' => $newStatus]);

        return back()->with('success', "Activity status changed to " . ucfirst($newStatus));
    }

    /**
     * Safe Lecturer Preview: Student visual template, ZERO XP, ZERO attempts logged.
     */
    public function previewPuzzle(Puzzle $puzzle)
    {
        $puzzle->load(['mission', 'topic']);

        return view('puzzles.show', [
            'puzzle'    => $puzzle,
            'isPreview' => true,
        ]);
    }

    /**
     * Lecturer Sandbox Preview Evaluation.
     * Evaluates response against real answer key and returns pedagogical feedback,
     * but records ZERO attempts and awards ZERO XP.
     */
    public function evaluatePreview(Request $request, Puzzle $puzzle, PuzzleEvaluatorService $evaluator)
    {
        $evaluation = $evaluator->evaluate($puzzle, $request->all());

        // Nothing is stored in a preview, so the page tells us its own health and miss count and
        // the same damage rules as for students are applied to them.
        $health = ['link_health' => 100, 'damage' => 0, 'damage_multiplier' => 1.0, 'safe_mode' => false];
        if (! $evaluation['is_correct']) {
            $misses = max(1, (int) $request->header('X-Preview-Misses', 1));
            $health = \App\Services\LinkHealthService::afterMiss(
                max(1, min(100, (int) $request->header('X-Preview-Health', 100))),
                $evaluation['diagnostics'] ?? null,
                $misses
            );
        }

        return response()->json(array_merge($health, [
            'status'           => $evaluation['is_correct'] ? 'completed' : 'failed',
            'is_correct'       => $evaluation['is_correct'],
            'score'            => $evaluation['score'],
            'max_score'        => $evaluation['max_score'],
            'percentage'       => $evaluation['percentage'],
            'earned_xp'        => 0,
            'total_xp'         => 0,
            'feedback'         => $evaluation['feedback'],
            'hint'             => $evaluation['hint'],
            // Same diagnostics as students get, so previews animate identically
            'diagnostics'      => $evaluation['diagnostics'] ?? null,
            'consequence'      => $evaluation['consequence'] ?? null,
            'attempts_count'   => 0,
            'max_attempts'     => (int) $puzzle->max_attempts,
            'stars'            => $evaluation['is_correct'] ? 3 : 0,
            'clearance_status' => 'Lecturer Sandbox Preview (Zero Attempts / Zero XP)',
        ]));
    }

    /**
     * Process and validate type-specific form data into safe activity_data and private correct_answer.
     */
    private function processActivityPayload(Request $request, string $type): array
    {
        $activityData = [];
        $correctAnswer = [];
        $error = null;

        switch ($type) {
            case 'identification':
                $labels = $this->filledInputs($request, 'options');
                $correctIndex = $request->input('correct_option_index');
                if (count($labels) < 2) {
                    return ['error' => 'Please provide at least 2 answer options for Identification.'];
                }
                $options = [];
                $correctOptionId = null;
                $position = 0;
                foreach ($labels as $formIndex => $lbl) {
                    $optId = 'opt_' . (++$position);
                    $options[] = ['id' => $optId, 'label' => $lbl];
                    if ((string)$formIndex === (string)$correctIndex) {
                        $correctOptionId = $optId;
                    }
                }
                if ($correctOptionId === null) {
                    return ['error' => 'Please select which option is the correct answer for Identification.'];
                }
                $activityData = [
                    'prompt' => $request->input('question_text'),
                    'image_url' => $request->input('image_url'),
                    'options' => $options,
                ];
                $correctAnswer = [
                    'correct_option' => $correctOptionId,
                ];
                break;

            case 'matching':
                $lefts = $this->filledInputs($request, 'left_items');
                $rights = $this->filledInputs($request, 'right_items');
                if (count($lefts) < 2 || array_keys($lefts) !== array_keys($rights)) {
                    return ['error' => 'Please provide at least 2 complete matching pairs (both Left and Right items).'];
                }
                $leftItems = [];
                $rightItems = [];
                $pairs = [];
                $position = 0;
                foreach ($lefts as $formIndex => $leftLabel) {
                    $position++;
                    $lId = 'L_' . $position;
                    $rId = 'R_' . $position;
                    $leftItems[] = ['id' => $lId, 'label' => $leftLabel];
                    $rightItems[] = ['id' => $rId, 'label' => $rights[$formIndex]];
                    $pairs[$lId] = $rId;
                }
                $activityData = [
                    'left_items' => $leftItems,
                    'right_items' => $rightItems,
                ];
                $correctAnswer = [
                    'pairs' => $pairs,
                ];
                break;

            case 'sequencing':
                $itemsInput = $this->filledInputs($request, 'sequence_items');
                if (count($itemsInput) < 3) {
                    return ['error' => 'Please provide at least 3 sequence steps in their correct order.'];
                }
                $items = [];
                $order = [];
                foreach (array_values($itemsInput) as $i => $itemLabel) {
                    $id = 'seq_' . ($i + 1);
                    $items[] = ['id' => $id, 'label' => $itemLabel];
                    $order[] = $id;
                }
                $activityData = [
                    'prompt' => $request->input('instruction'),
                    'items' => $items,
                ];
                $correctAnswer = [
                    'order' => $order,
                ];
                break;

            case 'classification':
                $catNames = $this->filledInputs($request, 'categories');
                if (count($catNames) < 2) {
                    return ['error' => 'Please provide at least 2 classification categories.'];
                }
                $categories = [];
                $catMap = [];
                $position = 0;
                foreach ($catNames as $formIndex => $cName) {
                    $catId = 'cat_' . (++$position);
                    $categories[] = ['id' => $catId, 'label' => $cName];
                    $catMap[$formIndex] = $catId;
                }

                $itemNames = $this->filledInputs($request, 'class_items');
                $itemCats = (array) $request->input('item_categories', []);
                if (count($itemNames) < 2) {
                    return ['error' => 'Please provide at least 2 items to classify.'];
                }
                $items = [];
                $mapping = [];
                $position = 0;
                foreach ($itemNames as $formIndex => $iName) {
                    $itemId = 'item_' . (++$position);
                    $items[] = ['id' => $itemId, 'label' => $iName];
                    $chosenCatIndex = $itemCats[$formIndex] ?? null;
                    if ($chosenCatIndex === null || !isset($catMap[$chosenCatIndex])) {
                        return ['error' => "Please assign a valid category for item '{$iName}'."];
                    }
                    $mapping[$itemId] = $catMap[$chosenCatIndex];
                }

                $activityData = [
                    'categories' => $categories,
                    'items' => $items,
                ];
                $correctAnswer = [
                    'mapping' => $mapping,
                ];
                break;

            case 'drag_and_drop':
                $dragLabels = $this->filledInputs($request, 'draggables');
                $zoneLabels = $this->filledInputs($request, 'drop_zones');
                if (count($dragLabels) < 2 || array_keys($dragLabels) !== array_keys($zoneLabels)) {
                    return ['error' => 'Please provide at least 2 draggable items, each with its matching drop zone.'];
                }
                $draggables = [];
                $dropZones = [];
                $placements = [];
                $position = 0;
                foreach ($dragLabels as $formIndex => $dLabel) {
                    $position++;
                    $dId = 'drag_' . $position;
                    $zId = 'zone_' . $position;
                    $draggables[] = ['id' => $dId, 'label' => $dLabel];
                    $dropZones[] = ['id' => $zId, 'label' => $zoneLabels[$formIndex]];
                    $placements[$dId] = $zId;
                }
                $activityData = [
                    'template_key' => $request->input('template_key', 'device_to_osi_layer'),
                    'draggables' => $draggables,
                    'drop_zones' => $dropZones,
                ];
                $correctAnswer = [
                    'placements' => $placements,
                ];
                break;

            case 'scenario':
                $choices = $this->filledInputs($request, 'scenario_choices');
                $consequences = (array) $request->input('scenario_consequences', []);
                $correctIndex = $request->input('correct_choice_index');
                if (count($choices) < 2) {
                    return ['error' => 'Please provide at least 2 decision choices for the scenario.'];
                }
                $choicesList = [];
                $correctChoiceId = null;
                $position = 0;
                foreach ($choices as $formIndex => $cLabel) {
                    $cId = 'choice_' . (++$position);
                    $entry = ['id' => $cId, 'label' => $cLabel];
                    // Optional lecturer-written consequence, revealed only after the student answers
                    $consequence = trim((string) ($consequences[$formIndex] ?? ''));
                    if ($consequence !== '') {
                        if (mb_strlen($consequence) > 300) {
                            return ['error' => 'Each consequence must be 300 characters or fewer.'];
                        }
                        $entry['consequence'] = $consequence;
                    }
                    $choicesList[] = $entry;
                    if ((string)$formIndex === (string)$correctIndex) {
                        $correctChoiceId = $cId;
                    }
                }
                if ($correctChoiceId === null) {
                    return ['error' => 'Please select the correct decision choice for the scenario.'];
                }
                $activityData = [
                    'scenario_text' => $request->input('instruction'),
                    'choices' => $choicesList,
                ];
                $correctAnswer = [
                    'correct_choice' => $correctChoiceId,
                ];
                break;
        }

        return [
            'error'          => null,
            'activity_data'  => $activityData,
            'correct_answer' => $correctAnswer,
        ];
    }

    /**
     * Trimmed, non-blank entries of an array input, keyed by their original form index
     * so that parallel inputs (radio indices, paired columns, category selects) stay aligned.
     */
    private function filledInputs(Request $request, string $key): array
    {
        $values = [];
        foreach ((array) $request->input($key, []) as $index => $value) {
            $value = is_string($value) ? trim($value) : '';
            if ($value !== '') {
                $values[$index] = $value;
            }
        }

        return $values;
    }
}
