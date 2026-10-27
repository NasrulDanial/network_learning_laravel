@extends('layouts.app')

@section('content')
<div style="max-width: 980px; margin: 0 auto; padding-bottom: 60px;">
    <!-- Top Navigation Breadcrumb -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <a href="{{ route('lecturer.missions.show', $mission->mission_id) }}" style="font-size: 0.85rem; color: var(--nl-cyan, #22d3ee); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Mission: {{ $mission->title }}
            </a>
            <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Add Puzzle Activity</h1>
            <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.6); margin-top: 4px; margin-bottom: 0;">
                Author an interactive puzzle challenge using code-free visual builders. No JSON or scripting required.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; font-size: 0.9rem;">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Step 1: Puzzle Type Selector (macOS Segmented Control) -->
    <div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 18px 22px; margin-bottom: 24px;">
        <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--nl-cyan, #22d3ee); margin-bottom: 12px;">
            Step 1: Select Puzzle Archetype
        </label>

        @php
            $types = [
                'identification' => ['name' => 'Identification', 'icon' => 'fa-eye', 'desc' => 'Hardware / Concept Recognition'],
                'matching'       => ['name' => 'Matching', 'icon' => 'fa-arrows-split-up-and-left', 'desc' => 'Term / Protocol Association'],
                'sequencing'     => ['name' => 'Sequencing', 'icon' => 'fa-arrow-down-1-9', 'desc' => 'Handshake & Flow Ordering'],
                'classification' => ['name' => 'Classification', 'icon' => 'fa-folder-tree', 'desc' => 'Multi-Bin Categorization'],
                'drag_and_drop'  => ['name' => 'Drag & Drop', 'icon' => 'fa-hand-pointer', 'desc' => 'Spatial Device Placement'],
                'scenario'       => ['name' => 'Scenario Decision', 'icon' => 'fa-code-branch', 'desc' => 'Situational Troubleshooting'],
            ];
        @endphp

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
            @foreach ($types as $key => $tInfo)
                <a
                    href="{{ route('lecturer.missions.puzzles.create', ['mission' => $mission->mission_id, 'type' => $key]) }}"
                    style="padding: 12px 14px; border-radius: 12px; text-decoration: none; text-align: center; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
                           {{ $selectedType === $key
                              ? 'background: linear-gradient(135deg, rgba(34, 211, 238, 0.25), rgba(168, 85, 247, 0.25)); border: 1px solid rgba(34, 211, 238, 0.6); box-shadow: 0 0 15px rgba(34, 211, 238, 0.25); color: #FFFFFF;'
                              : 'background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.7);' }}"
                >
                    <div style="font-size: 1.25rem; margin-bottom: 6px; color: {{ $selectedType === $key ? 'var(--nl-cyan, #22d3ee)' : 'inherit' }};">
                        <i class="fa-solid {{ $tInfo['icon'] }}"></i>
                    </div>
                    <div style="font-size: 0.85rem; font-weight: 700;">{{ $tInfo['name'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Main Authoring Form -->
    <form method="POST" action="{{ route('lecturer.missions.puzzles.store', $mission->mission_id) }}" id="puzzleAuthorForm">
        @csrf
        <input type="hidden" name="puzzle_type" value="{{ $selectedType }}">

        <!-- Step 2: Base Metadata Card -->
        <div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 28px; margin-bottom: 24px;">
            <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--nl-cyan, #22d3ee); margin-bottom: 18px;">
                Step 2: Activity Title & Instructions
            </label>

            <!-- Title -->
            <div style="margin-bottom: 20px;">
                <label for="question_text" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Activity Title / Primary Challenge <span style="color: #f87171;">*</span>
                </label>
                <input
                    type="text"
                    id="question_text"
                    name="question_text"
                    value="{{ old('question_text') }}"
                    placeholder="e.g. Identify the Physical Layer Component"
                    required
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                />
            </div>

            <!-- Instruction / Student Prompt -->
            <div style="margin-bottom: 20px;">
                <label for="instruction" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                    Student Instructions / Challenge Prompt <span style="color: #f87171;">*</span>
                </label>
                <textarea
                    id="instruction"
                    name="instruction"
                    rows="2"
                    required
                    placeholder="Provide clear guidance for what the student must perform in this puzzle..."
                    style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem; font-family: inherit; resize: vertical;"
                >{{ old('instruction') }}</textarea>
            </div>

            <!-- Grid: Points, Difficulty, Order, Status, Max Attempts -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div>
                    <label for="points" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        XP Points (25 - 500) <span style="color: #f87171;">*</span>
                    </label>
                    <input
                        type="number"
                        id="points"
                        name="points"
                        min="25"
                        max="500"
                        step="25"
                        value="{{ old('points', 100) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>

                <div>
                    <label for="difficulty" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Difficulty <span style="color: #f87171;">*</span>
                    </label>
                    <select
                        id="difficulty"
                        name="difficulty"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    >
                        <option value="beginner" {{ old('difficulty') === 'beginner' ? 'selected' : '' }}>Beginner</option>
                        <option value="intermediate" {{ old('difficulty', 'intermediate') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="advanced" {{ old('difficulty') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                    </select>
                </div>

                <div>
                    <label for="max_attempts" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Max Attempts (0 = Unlimited)
                    </label>
                    <input
                        type="number"
                        id="max_attempts"
                        name="max_attempts"
                        min="0"
                        max="100"
                        value="{{ old('max_attempts', 0) }}"
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>

                <div>
                    <label for="activity_order" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Activity Order <span style="color: #f87171;">*</span>
                    </label>
                    <input
                        type="number"
                        id="activity_order"
                        name="activity_order"
                        min="1"
                        value="{{ old('activity_order', $nextOrder) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    />
                </div>

                <div>
                    <label for="status" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                        Status <span style="color: #f87171;">*</span>
                    </label>
                    <select
                        id="status"
                        name="status"
                        required
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                    >
                        <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published (Active)</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                    </select>
                </div>
            </div>

            <!-- Optional Explanation, Feedback & Hint -->
            <div style="margin-bottom: 16px;">
                <label for="explanation" style="display: block; font-size: 0.82rem; font-weight: 600; color: rgba(255, 255, 255, 0.7); margin-bottom: 6px;">
                    Pedagogical Explanation / Educational Debrief (optional)
                </label>
                <textarea
                    id="explanation"
                    name="explanation"
                    rows="2"
                    placeholder="Technical rationale explained to students following puzzle evaluation..."
                    style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem; font-family: inherit; resize: vertical;"
                >{{ old('explanation') }}</textarea>
            </div>

            <!-- Optional Feedback & Hint -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <div>
                    <label for="correct_feedback" style="display: block; font-size: 0.82rem; font-weight: 600; color: rgba(255, 255, 255, 0.7); margin-bottom: 6px;">
                        Success Feedback Message
                    </label>
                    <input
                        type="text"
                        id="correct_feedback"
                        name="correct_feedback"
                        value="{{ old('correct_feedback', 'Excellent work! Challenge completed.') }}"
                        style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                    />
                </div>

                <div>
                    <label for="incorrect_feedback" style="display: block; font-size: 0.82rem; font-weight: 600; color: rgba(255, 255, 255, 0.7); margin-bottom: 6px;">
                        Failure Guidance Message
                    </label>
                    <input
                        type="text"
                        id="incorrect_feedback"
                        name="incorrect_feedback"
                        value="{{ old('incorrect_feedback', 'Not quite. Check your networking principles and try again.') }}"
                        style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                    />
                </div>

                <div>
                    <label for="hint" style="display: block; font-size: 0.82rem; font-weight: 600; color: rgba(255, 255, 255, 0.7); margin-bottom: 6px;">
                        Optional Hint
                    </label>
                    <input
                        type="text"
                        id="hint"
                        name="hint"
                        value="{{ old('hint') }}"
                        placeholder="e.g. Remember OSI Layer 1 only transmits raw bits"
                        style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                    />
                </div>
            </div>
        </div>

        <!-- Step 3: Type-Specific Dynamic Builder -->
        <div style="background: rgba(13, 33, 56, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 28px; margin-bottom: 24px;">
            <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--nl-cyan, #22d3ee); margin-bottom: 18px;">
                Step 3: Visual Logic Builder ({{ $types[$selectedType]['name'] }})
            </label>

            <!-- Archetype 1: Identification -->
            @if ($selectedType === 'identification')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Add multiple choice options. Mark the radio button of the <strong>single correct option</strong>. The student will be asked to identify the correct component.
                    </p>

                    <div id="identOptionsContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        @php
                            $defaultOpts = ['Router', 'Switch', 'Repeater / Hub', 'Firewall'];
                        @endphp
                        @foreach ($defaultOpts as $i => $dOpt)
                            <div class="option-row" style="display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                <input
                                    type="radio"
                                    name="correct_option_index"
                                    value="{{ $i }}"
                                    {{ $i === 0 ? 'checked' : '' }}
                                    style="accent-color: var(--nl-cyan, #22d3ee); width: 18px; height: 18px; cursor: pointer;"
                                    title="Mark as correct answer"
                                />
                                <input
                                    type="text"
                                    name="options[]"
                                    value="{{ $dOpt }}"
                                    required
                                    placeholder="Option label"
                                    style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                />
                                <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addIdentOption()" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem; border-radius: 10px; cursor: pointer;">
                        <i class="fa-solid fa-plus"></i> Add Another Option
                    </button>
                </div>
            @endif

            <!-- Archetype 2: Matching -->
            @if ($selectedType === 'matching')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Add matching pairs (Left Concept &bull; Right Target). Students will draw interactive connections between them in the canvas.
                    </p>

                    <div id="matchingPairsContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        @php
                            $defaultPairs = [
                                ['HTTP', 'Port 80 / Hypertext Web Traffic'],
                                ['HTTPS', 'Port 443 / Encrypted Web Traffic'],
                                ['DNS', 'Port 53 / Domain Name Resolution'],
                                ['SSH', 'Port 22 / Secure Encrypted Shell'],
                            ];
                        @endphp
                        @foreach ($defaultPairs as $p)
                            <div class="pair-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                <input
                                    type="text"
                                    name="left_items[]"
                                    value="{{ $p[0] }}"
                                    required
                                    placeholder="Left Item / Term"
                                    style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                />
                                <input
                                    type="text"
                                    name="right_items[]"
                                    value="{{ $p[1] }}"
                                    required
                                    placeholder="Right Item / Definition"
                                    style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                />
                                <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addMatchingPair()" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem; border-radius: 10px; cursor: pointer;">
                        <i class="fa-solid fa-plus"></i> Add Matching Pair
                    </button>
                </div>
            @endif

            <!-- Archetype 3: Sequencing -->
            @if ($selectedType === 'sequencing')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Enter steps in their <strong>correct chronological order</strong> (Top to Bottom). The platform will automatically scramble these steps for students to reconstruct.
                    </p>

                    <div id="sequenceItemsContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        @php
                            $defaultSteps = [
                                'Client sends TCP SYN packet with initial sequence number',
                                'Server responds with TCP SYN-ACK acknowledging receipt',
                                'Client returns TCP ACK packet establishing full-duplex session',
                            ];
                        @endphp
                        @foreach ($defaultSteps as $idx => $step)
                            <div class="seq-row" style="display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                <span class="step-num" style="font-weight: 800; color: #facc15; min-width: 28px; text-align: center;">{{ $idx + 1 }}</span>
                                <input
                                    type="text"
                                    name="sequence_items[]"
                                    value="{{ $step }}"
                                    required
                                    placeholder="Step description"
                                    style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                />
                                <button type="button" onclick="removeRow(this); updateSeqNumbers();" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addSequenceItem()" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem; border-radius: 10px; cursor: pointer;">
                        <i class="fa-solid fa-plus"></i> Add Next Sequence Step
                    </button>
                </div>
            @endif

            <!-- Archetype 4: Classification -->
            @if ($selectedType === 'classification')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Configure categories (target buckets) and assign each item to its correct category. Students will drag items into the proper bin.
                    </p>

                    <!-- Categories Sub-Section -->
                    <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #34d399; margin-bottom: 10px;">
                            Category Bins (Minimum 2)
                        </label>
                        <div id="classCategoriesContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                            @php
                                $defaultCats = ['Layer 2 - Data Link', 'Layer 3 - Network', 'Layer 4 - Transport'];
                            @endphp
                            @foreach ($defaultCats as $cIdx => $cat)
                                <div class="cat-row" style="display: flex; align-items: center; gap: 12px;">
                                    <input
                                        type="text"
                                        name="categories[]"
                                        value="{{ $cat }}"
                                        required
                                        placeholder="Category name"
                                        style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                    />
                                    <button type="button" onclick="removeRow(this); refreshCatDropdowns();" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addClassCategory()" class="button secondary" style="padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; cursor: pointer;">
                            <i class="fa-solid fa-plus"></i> Add Category Bin
                        </button>
                    </div>

                    <!-- Items Sub-Section -->
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #34d399; margin-bottom: 10px;">
                            Classifiable Items & Correct Bucket Assignment
                        </label>
                        <div id="classItemsContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                            @php
                                $defaultItems = [
                                    ['Switch (MAC Addressing)', 0],
                                    ['Router (IP Routing)', 1],
                                    ['TCP (Transmission Control Protocol)', 2],
                                    ['UDP (User Datagram Protocol)', 2],
                                ];
                            @endphp
                            @foreach ($defaultItems as $iIdx => $item)
                                <div class="item-row" style="display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <input
                                        type="text"
                                        name="class_items[]"
                                        value="{{ $item[0] }}"
                                        required
                                        placeholder="Item to classify"
                                        style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                    />
                                    <select
                                        name="item_categories[]"
                                        class="cat-select"
                                        style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                    >
                                        @foreach ($defaultCats as $cIdx => $cat)
                                            <option value="{{ $cIdx }}" {{ $item[1] === $cIdx ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addClassItem()" class="button secondary" style="padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; cursor: pointer;">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
            @endif

            <!-- Archetype 5: Predefined Drag & Drop -->
            @if ($selectedType === 'drag_and_drop')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Configure draggable networking hardware nodes and their designated target drop zones on the canvas.
                    </p>

                    <div style="margin-bottom: 20px;">
                        <label for="template_key" style="display: block; font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;">
                            Drag & Drop Template Layout
                        </label>
                        <select
                            id="template_key"
                            name="template_key"
                            style="width: 100%; padding: 12px 16px; border-radius: 12px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.15); color: #FFFFFF; font-size: 0.92rem;"
                        >
                            <option value="device_to_osi_layer">OSI Layer Architecture (Device to Layer Placement)</option>
                            <option value="topology_placement">Enterprise Star/Mesh Topology Placement</option>
                        </select>
                    </div>

                    <div id="dragDropContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        @php
                            $defaultDrags = [
                                ['Hub / Repeater', 'Physical Layer (Layer 1)'],
                                ['Layer 2 Switch', 'Data Link Layer (Layer 2)'],
                                ['Core Router', 'Network Layer (Layer 3)'],
                            ];
                        @endphp
                        @foreach ($defaultDrags as $d)
                            <div class="drag-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); margin-bottom: 4px;">Draggable Device</label>
                                    <input
                                        type="text"
                                        name="draggables[]"
                                        value="{{ $d[0] }}"
                                        required
                                        placeholder="e.g. Core Router"
                                        style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                    />
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); margin-bottom: 4px;">Target Drop Zone</label>
                                    <input
                                        type="text"
                                        name="drop_zones[]"
                                        value="{{ $d[1] }}"
                                        required
                                        placeholder="e.g. Layer 3 Dropzone"
                                        style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                    />
                                </div>
                                <div style="padding-top: 18px;">
                                    <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addDragDropRow()" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem; border-radius: 10px; cursor: pointer;">
                        <i class="fa-solid fa-plus"></i> Add Draggable & Target Drop Zone
                    </button>
                </div>
            @endif

            <!-- Archetype 6: Scenario Decision -->
            @if ($selectedType === 'scenario')
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 16px;">
                        Provide decision options for the student engineer to pick from. Mark the radio button of the <strong>optimal diagnostic / resolution action</strong>.
                    </p>

                    <div id="scenarioChoicesContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        @php
                            $defaultChoices = [
                                'Verify Physical Link LED and replace damaged UTP Cat6 patch cable',
                                'Re-flash router firmware without backing up running-config',
                                'Assign conflicting static IP address on the default gateway interface',
                            ];
                        @endphp
                        @foreach ($defaultChoices as $sIdx => $choice)
                            <div class="choice-row" style="display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);">
                                <input
                                    type="radio"
                                    name="correct_choice_index"
                                    value="{{ $sIdx }}"
                                    {{ $sIdx === 0 ? 'checked' : '' }}
                                    style="accent-color: #fb923c; width: 18px; height: 18px; cursor: pointer;"
                                    title="Mark as correct resolution"
                                />
                                <input
                                    type="text"
                                    name="scenario_choices[]"
                                    value="{{ $choice }}"
                                    required
                                    placeholder="Action / Decision Choice"
                                    style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;"
                                />
                                <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addScenarioChoice()" class="button secondary" style="padding: 9px 16px; font-size: 0.85rem; border-radius: 10px; cursor: pointer;">
                        <i class="fa-solid fa-plus"></i> Add Another Decision Choice
                    </button>
                </div>
            @endif
        </div>

        <!-- Submit & Actions Footer -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 20px 0;">
            <a
                href="{{ route('lecturer.missions.show', $mission->mission_id) }}"
                class="button secondary"
                style="padding: 12px 22px; font-size: 0.9rem; border-radius: 12px; text-decoration: none;"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="button"
                style="padding: 12px 28px; font-size: 0.9rem; border-radius: 12px; box-shadow: 0 0 15px rgba(34, 211, 238, 0.25);"
            >
                <i class="fa-solid fa-check"></i>
                <span>Save Puzzle Activity</span>
            </button>
        </div>
    </form>
</div>

<!-- Dynamic DOM helper script -->
<script>
function removeRow(btn) {
    const row = btn.closest('.option-row, .pair-row, .seq-row, .cat-row, .item-row, .drag-row, .choice-row');
    if (row) {
        row.remove();
    }
    reindexCorrectRadios();
}

// Radio values must match each row's position in the submitted options[] / scenario_choices[]
// arrays; re-number them after a row is removed so the marked answer stays correct.
function reindexCorrectRadios() {
    ['correct_option_index', 'correct_choice_index'].forEach(name => {
        document.querySelectorAll(`input[type="radio"][name="${name}"]`).forEach((radio, idx) => {
            radio.value = idx;
        });
    });
}

function addIdentOption() {
    const container = document.getElementById('identOptionsContainer');
    const count = container.querySelectorAll('.option-row').length;
    const div = document.createElement('div');
    div.className = 'option-row';
    div.style = 'display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <input type="radio" name="correct_option_index" value="${count}" style="accent-color: var(--nl-cyan, #22d3ee); width: 18px; height: 18px; cursor: pointer;" title="Mark as correct answer" />
        <input type="text" name="options[]" required placeholder="Option label" style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function addMatchingPair() {
    const container = document.getElementById('matchingPairsContainer');
    const div = document.createElement('div');
    div.className = 'pair-row';
    div.style = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <input type="text" name="left_items[]" required placeholder="Left Item / Term" style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <input type="text" name="right_items[]" required placeholder="Right Item / Definition" style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function updateSeqNumbers() {
    const rows = document.querySelectorAll('#sequenceItemsContainer .seq-row');
    rows.forEach((r, idx) => {
        const num = r.querySelector('.step-num');
        if (num) num.textContent = idx + 1;
    });
}

function addSequenceItem() {
    const container = document.getElementById('sequenceItemsContainer');
    const count = container.querySelectorAll('.seq-row').length + 1;
    const div = document.createElement('div');
    div.className = 'seq-row';
    div.style = 'display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <span class="step-num" style="font-weight: 800; color: #facc15; min-width: 28px; text-align: center;">${count}</span>
        <input type="text" name="sequence_items[]" required placeholder="Step description" style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <button type="button" onclick="removeRow(this); updateSeqNumbers();" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function getCategoriesList() {
    const catInputs = document.querySelectorAll('input[name="categories[]"]');
    const cats = [];
    catInputs.forEach((inp, idx) => {
        cats.push({ idx: idx, label: inp.value.trim() || `Category ${idx + 1}` });
    });
    return cats;
}

function refreshCatDropdowns() {
    const cats = getCategoriesList();
    const selects = document.querySelectorAll('.cat-select');
    selects.forEach(sel => {
        const currentVal = sel.value;
        sel.innerHTML = '';
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.idx;
            opt.textContent = c.label;
            if (String(currentVal) === String(c.idx)) opt.selected = true;
            sel.appendChild(opt);
        });
    });
}

function addClassCategory() {
    const container = document.getElementById('classCategoriesContainer');
    const div = document.createElement('div');
    div.className = 'cat-row';
    div.style = 'display: flex; align-items: center; gap: 12px;';
    div.innerHTML = `
        <input type="text" name="categories[]" required placeholder="Category name" oninput="refreshCatDropdowns()" style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <button type="button" onclick="removeRow(this); refreshCatDropdowns();" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
    refreshCatDropdowns();
}

function addClassItem() {
    const container = document.getElementById('classItemsContainer');
    const cats = getCategoriesList();
    let optionsHtml = '';
    cats.forEach(c => {
        optionsHtml += `<option value="${c.idx}">${c.label}</option>`;
    });

    const div = document.createElement('div');
    div.className = 'item-row';
    div.style = 'display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <input type="text" name="class_items[]" required placeholder="Item to classify" style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <select name="item_categories[]" class="cat-select" style="padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;">
            ${optionsHtml}
        </select>
        <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function addDragDropRow() {
    const container = document.getElementById('dragDropContainer');
    const div = document.createElement('div');
    div.className = 'drag-row';
    div.style = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <div>
            <label style="display: block; font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); margin-bottom: 4px;">Draggable Device</label>
            <input type="text" name="draggables[]" required placeholder="e.g. Host PC" style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; color: rgba(255, 255, 255, 0.5); margin-bottom: 4px;">Target Drop Zone</label>
            <input type="text" name="drop_zones[]" required placeholder="e.g. End Host Zone" style="width: 100%; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        </div>
        <div style="padding-top: 18px;">
            <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}

function addScenarioChoice() {
    const container = document.getElementById('scenarioChoicesContainer');
    const count = container.querySelectorAll('.choice-row').length;
    const div = document.createElement('div');
    div.className = 'choice-row';
    div.style = 'display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.03); padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08);';
    div.innerHTML = `
        <input type="radio" name="correct_choice_index" value="${count}" style="accent-color: #fb923c; width: 18px; height: 18px; cursor: pointer;" title="Mark as correct resolution" />
        <input type="text" name="scenario_choices[]" required placeholder="Action / Decision Choice" style="flex: 1; padding: 10px 14px; border-radius: 10px; background: rgba(7, 20, 38, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF; font-size: 0.88rem;" />
        <button type="button" onclick="removeRow(this)" class="button danger" style="padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}
</script>
@endsection
