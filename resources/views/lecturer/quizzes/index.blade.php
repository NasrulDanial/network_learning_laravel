@extends('layouts.app')

@section('content')
<!-- Page Banner Header -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Curriculum Lab Control</span>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Manage Quiz Challenges & Missions</h1>
        <p style="font-size: 0.88rem; color: var(--nl-text-secondary); margin-top: 4px; margin-bottom: 0;">
            Author interactive networking simulations, configure narrative win-conditions, and assign clearance rewards.
        </p>
    </div>

    <div>
        <button
            type="button"
            class="button"
            onclick="openNewQuizSettingsModal()"
            style="padding: 10px 20px; font-size: 0.88rem;"
            data-nl-tooltip="Create a new networking challenge mission"
        >
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>Add Quiz Challenge</span>
        </button>
    </div>
</div>

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #4ade80; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.88rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<!-- Master View: High-Level Data Table -->
<div style="background: var(--nl-glass-card); backdrop-filter: var(--nl-glass-blur); -webkit-backdrop-filter: var(--nl-glass-blur); border: 1px solid var(--nl-glass-border); border-radius: var(--nl-radius-panel); padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.3);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
        <div>
            <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 2px;">Curriculum Challenges</span>
            <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #FFFFFF;">Available Quiz Challenges</h2>
        </div>
        <span style="font-size: 0.8rem; color: var(--nl-text-tertiary);">
            Actions trigger focused overlay sheets and spatial modals without disruptive page reloads.
        </span>
    </div>

    @if ($quizzes->count() > 0)
        <table class="table-borderless-glass">
            <thead>
                <tr>
                    <th>Mission / Quiz</th>
                    <th>Curriculum Stage</th>
                    <th>Passing Mark</th>
                    <th>Simulation Engine</th>
                    <th>Mission Steps</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quizzes as $quiz)
                    @php
                        $puzzle = $quiz->puzzle;
                        $puzzleTypeMap = [
                            'drag_and_drop'  => ['topology', 'Topology Builder', 'fa-network-wired', 'tag-topology'],
                            'scenario'       => ['routing', 'Routing Maze', 'fa-route', 'tag-routing'],
                            'sequencing'     => ['protocol', 'Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                            'matching'       => ['protocol', 'Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                            'classification' => ['protocol', 'Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                            'identification' => ['subnet', 'Subnet Calculator', 'fa-calculator', 'tag-subnet'],
                        ];

                        $defaultCategories = [
                            1 => ['protocol', 'Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                            2 => ['topology', 'Topology Builder', 'fa-network-wired', 'tag-topology'],
                            3 => ['topology', 'Topology Builder', 'fa-network-wired', 'tag-topology'],
                            4 => ['subnet', 'Subnet Calculator', 'fa-calculator', 'tag-subnet'],
                            5 => ['topology', 'Topology Builder', 'fa-network-wired', 'tag-topology'],
                            6 => ['routing', 'Routing Maze', 'fa-route', 'tag-routing'],
                            7 => ['protocol', 'Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                        ];

                        $topicOrder = $quiz->topic->topic_order ?? ($quiz->quiz_id % 7 ?: 1);
                        $engineInfo = $puzzle && isset($puzzleTypeMap[$puzzle->puzzle_type])
                            ? $puzzleTypeMap[$puzzle->puzzle_type]
                            : ($defaultCategories[$topicOrder] ?? ['topology', 'Topology Builder', 'fa-network-wired', 'tag-topology']);
                        $categoryKey = $engineInfo[0];
                        $xpReward = $puzzle->points ?? 150;
                        $instruction = $puzzle->instruction ?? 'Configure lab simulation parameters according to curriculum objectives.';
                        $isReady = $puzzle && !empty($puzzle->correct_answer);
                    @endphp
                    <tr id="quiz-row-{{ $quiz->quiz_id }}">
                        <td>
                            <div style="font-weight: 700; color: #FFFFFF; font-size: 0.92rem;">
                                {{ $quiz->title }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--nl-text-tertiary); margin-top: 2px;">
                                Mission ID: #{{ $quiz->quiz_id }} // Evaluated in student challenge hub
                            </div>
                        </td>

                        <td>
                            <div style="font-weight: 600; font-size: 0.85rem; color: var(--nl-cyan);">
                                {{ $quiz->topic->title ?? 'Curriculum Stage' }}
                            </div>
                        </td>

                        <td>
                            <span class="status-pill pass" style="font-size: 0.78rem;">
                                {{ (int) $quiz->passing_mark }}% Pass
                            </span>
                        </td>

                        <!-- Simulation Engine with Live Status Indicator -->
                        <td>
                            <div id="status-container-{{ $quiz->quiz_id }}" style="display: inline-flex; align-items: center; gap: 8px;">
                                @if ($isReady)
                                    <span class="status-indicator-tag ready">
                                        <span class="status-dot ready"></span>
                                        <span>Ready</span>
                                    </span>
                                @else
                                    <span class="status-indicator-tag setup-required">
                                        <span class="status-dot setup-required"></span>
                                        <span>Setup Required</span>
                                    </span>
                                @endif

                                <span class="item-tag {{ $engineInfo[3] }}" id="puzzle-badge-{{ $quiz->quiz_id }}" style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid {{ $engineInfo[2] }}"></i>
                                    <span>{{ $engineInfo[1] }}</span>
                                </span>
                            </div>
                        </td>

                        <!-- Mission Sub-Tasks / Steps Count -->
                        <td>
                            <span style="font-weight: 700; font-size: 0.9rem; color: #FFFFFF;" id="table-step-count-{{ $quiz->quiz_id }}">
                                {{ (int) $quiz->total_questions }}
                            </span>
                            <span style="font-size: 0.75rem; color: var(--nl-text-tertiary);">steps</span>
                        </td>

                        <!-- Consistent Left-to-Right Actions Column -->
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">

                                <!-- 1. Mission Steps Button (Opens Right-Side Slide-Over) -->
                                <button
                                    type="button"
                                    class="button secondary"
                                    style="padding: 6px 12px; font-size: 0.78rem; border-radius: 8px;"
                                    onclick="openMissionStepsSlideOver({{ $quiz->quiz_id }}, '{{ addslashes($quiz->title) }}')"
                                    data-nl-tooltip="Manage the sequence of challenge sub-tasks and objectives"
                                >
                                    <i class="fa-solid fa-list-check"></i>
                                    <span id="btn-step-count-{{ $quiz->quiz_id }}">Steps ({{ (int) $quiz->total_questions }})</span>
                                </button>

                                <!-- 2. Settings Button (Opens Centered Modal for Gamification & Meta) -->
                                <button
                                    type="button"
                                    class="button secondary"
                                    style="padding: 6px 12px; font-size: 0.78rem; border-radius: 8px;"
                                    onclick="openQuizSettingsModal({{ $quiz->quiz_id }}, '{{ addslashes($quiz->title) }}', {{ $quiz->topic_id }}, {{ (int) $quiz->passing_mark }}, '{{ $categoryKey }}', '{{ addslashes($instruction) }}', {{ $xpReward }})"
                                    data-nl-tooltip="Configure game economy, XP rewards, and mission briefing"
                                >
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>Settings</span>
                                </button>

                                <!-- 3. Design Board Button (Primary Highlighted Action -> Full-Screen Modal) -->
                                <button
                                    type="button"
                                    class="button"
                                    style="padding: 6px 14px; font-size: 0.78rem; border-radius: 8px;"
                                    onclick="openPuzzleCanvasModal({{ $quiz->quiz_id }}, {{ $quiz->topic_id }}, '{{ addslashes($quiz->title) }}', '{{ $categoryKey }}', {{ $xpReward }})"
                                    data-nl-tooltip="Launch the visual canvas to map the correct topology or sequence"
                                >
                                    <i class="fa-solid fa-shapes"></i>
                                    <span>Design Board</span>
                                </button>

                                <!-- 4. Delete Action (Opens Red-Accented Warning Modal) -->
                                <button
                                    type="button"
                                    class="btn-remove-row"
                                    style="width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;"
                                    onclick="openDeleteConfirmModal({{ $quiz->quiz_id }}, '{{ addslashes($quiz->title) }}')"
                                    data-nl-tooltip="Permanently remove this challenge mission"
                                >
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 48px; color: var(--nl-text-tertiary);">
            <i class="fa-solid fa-puzzle-piece" style="font-size: 2.2rem; margin-bottom: 12px; display: block; opacity: 0.4;"></i>
            <h3 style="font-size: 1.1rem; color: #FFFFFF; margin-bottom: 4px;">No quiz challenges found</h3>
            <p style="font-size: 0.85rem; margin-bottom: 16px;">Create your first networking challenge mission to begin.</p>
            <button type="button" class="button" onclick="openNewQuizSettingsModal()">
                <i class="fa-solid fa-plus"></i> Add First Quiz
            </button>
        </div>
    @endif
</div>


<!-- =====================================================================
     LAYER 1: MISSION STEPS (RIGHT-SIDE SLIDE-OVER PANEL)
     Manages the sequence of sub-tasks/objectives without navigating away
     ===================================================================== -->
<div class="quiz-slideover-backdrop" id="stepsSlideoverBackdrop">
    <aside class="quiz-slideover-panel" id="stepsSlideoverPanel" role="dialog" aria-modal="true" style="width: 500px;">
        <div class="slideover-header">
            <div class="slideover-title-group">
                <span class="slideover-step-badge">
                    <i class="fa-solid fa-list-check"></i> MISSION ARCHITECTURE
                </span>
                <h3 class="slideover-title" id="stepsSlideoverTitle">Mission Steps</h3>
            </div>
            <button type="button" class="btn-close-slideover" onclick="closeMissionStepsSlideOver()" aria-label="Close steps panel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="slideover-body">
            <div style="background: rgba(34, 211, 238, 0.08); border: 1px solid rgba(34, 211, 238, 0.2); border-radius: 12px; padding: 12px 16px; font-size: 0.8rem; color: #FFFFFF; line-height: 1.45;">
                <i class="fa-solid fa-circle-info" style="color: var(--nl-cyan); margin-right: 6px;"></i>
                In this puzzle-driven framework, these sub-tasks define the progressive objectives students must complete to conquer this mission.
            </div>

            <!-- Add New Step Bar -->
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px;">
                <label style="font-size: 0.78rem; font-weight: 700; color: #FFFFFF; margin-bottom: 8px; display: block;">
                    Add New Sub-Task Step
                </label>
                <div style="display: flex; gap: 8px;">
                    <input
                        type="text"
                        id="newStepInput"
                        class="macos-input"
                        placeholder="e.g. 1. Connect Router to Switch, 2. Ping Test..."
                        style="flex: 1; font-size: 0.82rem;"
                        onkeydown="if(event.key === 'Enter') { event.preventDefault(); addNewMissionStep(); }"
                    >
                    <button type="button" class="button" onclick="addNewMissionStep()" style="padding: 7px 14px; font-size: 0.8rem;">
                        <i class="fa-solid fa-plus"></i> Add
                    </button>
                </div>
            </div>

            <!-- Steps List Container -->
            <div>
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 8px; display: block;">
                    Challenge Objective Sequence
                </span>
                <div id="stepsListContainer" class="step-cards-list">
                    <div style="text-align: center; padding: 24px; color: var(--nl-text-tertiary); font-size: 0.82rem;">
                        <i class="fa-solid fa-spinner fa-spin" style="margin-right: 8px;"></i> Loading mission steps...
                    </div>
                </div>
            </div>

            <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.08); text-align: center;">
                <a id="legacyQuestionsLink" href="#" style="color: var(--nl-text-tertiary); font-size: 0.76rem; text-decoration: none; transition: color 0.2s ease;">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="margin-right: 5px;"></i>
                    Need legacy multiple-choice MCQ editor? Open Detailed Editor
                </a>
            </div>
        </div>

        <div class="slideover-footer">
            <button type="button" class="button secondary" onclick="closeMissionStepsSlideOver()">
                Done
            </button>
            <button type="button" class="button" onclick="launchCanvasFromSteps()">
                <i class="fa-solid fa-shapes"></i>
                <span>Open Design Board</span>
            </button>
        </div>
    </aside>
</div>


<!-- =====================================================================
     LAYER 2: SETTINGS & GAMIFICATION (CENTERED MODAL)
     Isolates game economy, narrative scenario briefing, and meta settings
     ===================================================================== -->
<div class="nl-modal-backdrop" id="settingsModalBackdrop">
    <div class="nl-modal-dialog" role="dialog" aria-modal="true">
        <div class="nl-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(34, 211, 238, 0.15); border: 1px solid rgba(34, 211, 238, 0.3); color: var(--nl-cyan); display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #FFFFFF; margin: 0;" id="settingsModalTitle">
                        Challenge Settings & Gamification
                    </h3>
                    <p style="font-size: 0.74rem; color: var(--nl-text-tertiary); margin: 2px 0 0 0;">
                        Curriculum mapping, scenario narrative, and clearance economy
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close-slideover" onclick="closeQuizSettingsModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="settingsQuizForm" method="POST" action="{{ route('lecturer.quizzes.store') }}">
            @csrf
            <div id="settingsMethodContainer"></div>
            <input type="hidden" id="modal_quiz_id" name="quiz_id" value="">
            <input type="hidden" id="modal_puzzle_category" name="puzzle_category" value="topology">

            <div class="nl-modal-body">
                <!-- Challenge Title -->
                <div class="form-row">
                    <label for="modal_title">Challenge / Mission Title</label>
                    <input
                        type="text"
                        id="modal_title"
                        name="title"
                        class="macos-input"
                        placeholder="e.g. Gateway Convergence & Router Mapping"
                        required
                    >
                </div>

                <!-- Prerequisite Curriculum Stage -->
                <div class="form-row">
                    <label for="modal_topic_id">Prerequisite Curriculum Stage</label>
                    <select id="modal_topic_id" name="topic_id" class="macos-select" required>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->topic_id }}">
                                Stage {{ $topic->topic_order }}: {{ $topic->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Mission Briefing (Narrative Scenario) -->
                <div class="form-row">
                    <label for="modal_scenario">Mission Briefing (Narrative Scenario)</label>
                    <textarea
                        id="modal_scenario"
                        name="scenario"
                        rows="3"
                        class="macos-input"
                        style="resize: vertical; min-height: 75px; line-height: 1.5;"
                        placeholder="Branch office router rebooted with cleared NVRAM. Configure routing protocols and establish gateway convergence."
                    ></textarea>
                    <span class="field-hint">Narrative scenarios give students a clear operational mission objective.</span>
                </div>

                <!-- Interactive Simulation Engine Selection Cards -->
                <div class="form-row">
                    <label>Interactive Simulation Engine</label>
                    <div class="category-card-grid">
                        <div class="category-select-card selected" data-cat="topology" onclick="selectModalCategoryCard('topology')">
                            <i class="fa-solid fa-network-wired category-card-icon"></i>
                            <span class="category-card-name">Topology Builder</span>
                            <span class="category-card-desc">Drag nodes & configure IP addresses</span>
                        </div>
                        <div class="category-select-card" data-cat="routing" onclick="selectModalCategoryCard('routing')">
                            <i class="fa-solid fa-route category-card-icon" style="color: #fbbf24;"></i>
                            <span class="category-card-name">Routing Maze</span>
                            <span class="category-card-desc">Pathfinding around blocked ACLs</span>
                        </div>
                        <div class="category-select-card" data-cat="protocol" onclick="selectModalCategoryCard('protocol')">
                            <i class="fa-solid fa-layer-group category-card-icon" style="color: #c084fc;"></i>
                            <span class="category-card-name">Protocol Matcher</span>
                            <span class="category-card-desc">OSI 7-Layer PDU Classification</span>
                        </div>
                        <div class="category-select-card" data-cat="subnet" onclick="selectModalCategoryCard('subnet')">
                            <i class="fa-solid fa-calculator category-card-icon" style="color: #34d399;"></i>
                            <span class="category-card-name">Subnet Calculator</span>
                            <span class="category-card-desc">VLSM CIDR bitmask challenge</span>
                        </div>
                    </div>
                </div>

                <!-- Score & Rewards Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Passing Mark Slider -->
                    <div class="form-row" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label for="modal_passing_mark">Passing Threshold</label>
                            <span id="settingsPassingBadge" style="font-weight: 700; color: var(--nl-cyan); font-size: 0.85rem;">80%</span>
                        </div>
                        <input
                            type="range"
                            id="modal_passing_mark"
                            name="passing_mark"
                            class="macos-slider"
                            min="50"
                            max="100"
                            value="80"
                            step="5"
                            oninput="document.getElementById('settingsPassingBadge').textContent = this.value + '%'"
                        >
                    </div>

                    <!-- Network XP Award Slider -->
                    <div class="form-row" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label for="modal_xp_input">Network XP Reward</label>
                            <span class="xp-badge" id="settingsXpBadge">+150 XP</span>
                        </div>
                        <input
                            type="range"
                            id="modal_xp_input"
                            name="xp_points"
                            class="macos-slider"
                            min="50"
                            max="500"
                            step="25"
                            value="150"
                            oninput="document.getElementById('settingsXpBadge').textContent = '+' + this.value + ' XP'"
                        >
                    </div>
                </div>
            </div>

            <div class="nl-modal-footer">
                <button type="button" class="button secondary" onclick="closeQuizSettingsModal()">
                    Cancel
                </button>
                <button type="submit" class="button">
                    <i class="fa-solid fa-check"></i>
                    <span id="settingsSubmitBtnText">Save Settings</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- =====================================================================
     LAYER 3: DESIGN BOARD (FULL-SCREEN SPATIAL GLASSMORPHIC CANVAS)
     Interactive canvas with Vanilla JS State Management & Two-Way Binding
     ===================================================================== -->
<div class="puzzle-fullscreen-backdrop" id="puzzleCanvasBackdrop">
    <div class="puzzle-fullscreen-window" id="puzzleCanvasWindow" role="dialog" aria-modal="true">

        <!-- Top Command Bar -->
        <header class="puzzle-modal-topbar">
            <div class="puzzle-modal-topbar-left">
                <div class="window-traffic-lights">
                    <span class="traffic-light light-close" onclick="closePuzzleCanvasModal()" title="Close Canvas"></span>
                    <span class="traffic-light light-min"></span>
                    <span class="traffic-light light-max"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-shapes text-cyan"></i>
                    <span class="puzzle-modal-mission-title" id="modalMissionTitle">Configure Interactive Simulation Board</span>
                </div>
            </div>

            <!-- Segmented Category Switcher inside Modal -->
            <div class="segmented-control">
                <button type="button" class="segmented-btn active" data-modal-cat="topology" onclick="switchModalCategory('topology')">
                    <i class="fa-solid fa-network-wired"></i> Topology
                </button>
                <button type="button" class="segmented-btn" data-modal-cat="routing" onclick="switchModalCategory('routing')">
                    <i class="fa-solid fa-route"></i> Routing
                </button>
                <button type="button" class="segmented-btn" data-modal-cat="protocol" onclick="switchModalCategory('protocol')">
                    <i class="fa-solid fa-layer-group"></i> Protocol
                </button>
                <button type="button" class="segmented-btn" data-modal-cat="subnet" onclick="switchModalCategory('subnet')">
                    <i class="fa-solid fa-calculator"></i> Subnet
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <!-- Smart Defaults / Presets Menu -->
                <select class="presets-dropdown-select" id="presetTemplateSelect" onchange="applyPresetTemplate(this.value)">
                    <option value="">Presets Template ▾</option>
                    <option value="topo_wan">Topology: 2-Router WAN Link</option>
                    <option value="topo_switch_hosts">Topology: Local Switch & 3 PCs</option>
                    <option value="topo_dmz">Topology: NextGen DMZ Firewall</option>
                    <option value="routing_dijkstra">Routing: 3-Hop Redundant Ring</option>
                    <option value="proto_web">Protocol: Standard Web Stack (HTTP/TLS/TCP/IP)</option>
                    <option value="subnet_30">Subnet: Class C /27 (30 Hosts)</option>
                </select>

                <!-- Sandbox Button: "Test as Student" -->
                <button type="button" class="sandbox-mode-btn" id="btnSandboxToggle" onclick="toggleStudentSandbox()">
                    <i class="fa-solid fa-flask"></i>
                    <span id="sandboxBtnText">Test as Student</span>
                </button>

                <!-- Save & Lock Board Button -->
                <button type="button" class="button" id="btnSavePuzzleBoard" onclick="savePuzzleBoardConfiguration()" style="padding: 8px 18px; font-size: 0.8rem;">
                    <i class="fa-solid fa-lock"></i>
                    <span id="btnSavePuzzleBoardText">Save & Lock Board</span>
                </button>

                <!-- Close Modal -->
                <button type="button" class="btn-close-slideover" onclick="closePuzzleCanvasModal()" title="Return to Manage Quizzes">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </header>

        <!-- Real-Time Syntax Validation Banner -->
        <div class="syntax-warning-banner" id="syntaxWarningBanner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="syntaxWarningText">
                Syntax Check: All connected nodes resolve within the 192.168.1.0/24 subnet boundary. Ready for simulation.
            </span>
        </div>

        <!-- Split Canvas Workspace: Spatial Stage (Left) + Context Rules Inspector (Right) -->
        <div class="modal-canvas-split">

            <!-- Center Visual Spatial Canvas -->
            <div style="flex: 1; position: relative; overflow: hidden; background: #07101e; display: flex; flex-direction: column;">

                <!-- Sandbox Mode Simulation HUD (Shown when "Test as Student" is active) -->
                <div id="sandboxHud" style="display: none; position: absolute; top: 12px; left: 16px; right: 16px; z-index: 50; background: rgba(16, 185, 129, 0.95); backdrop-filter: blur(16px); padding: 10px 18px; border-radius: 12px; align-items: center; justify-content: space-between; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">
                    <div style="display: flex; align-items: center; gap: 10px; color: #071426; font-weight: 700; font-size: 0.85rem;">
                        <i class="fa-solid fa-gamepad" style="font-size: 1.1rem;"></i>
                        <span>STUDENT PLAYTEST SANDBOX ACTIVE // VERIFYING LINK REACHABILITY</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="button" style="background: #071426; color: #4ade80; padding: 6px 14px; font-size: 0.78rem;" onclick="simulateStudentPing()">
                            <i class="fa-solid fa-bolt"></i> Send Test Ping Packet
                        </button>
                        <button type="button" class="button secondary" style="background: rgba(7, 20, 38, 0.2); color: #071426; border-color: rgba(7, 20, 38, 0.4); padding: 6px 12px; font-size: 0.78rem;" onclick="toggleStudentSandbox()">
                            Exit Playtest
                        </button>
                    </div>
                </div>

                <!-- Canvas A: Topology Stage -->
                <div class="canvas-panel active" id="modal-canvas-topology" style="flex: 1; position: relative; min-height: 480px;" ondragover="handleCanvasDragOver(event)" ondrop="handleCanvasDrop(event)">
                    <div class="blueprint-grid"></div>

                    <!-- Dynamic SVG Cable Layer -->
                    <svg class="topology-wires-svg" id="topologyWiresSvg" viewBox="0 0 1000 600" style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5;">
                        <!-- Rendered dynamically by renderTopologyConnections() -->
                    </svg>

                    <!-- Draggable Node Stage Container -->
                    <div id="topologyNodesContainer" style="position: absolute; inset: 0; z-index: 10;">
                        <!-- Rendered dynamically by renderTopologyNodes() -->
                    </div>

                    <!-- Bottom Tool Palette -->
                    <div class="canvas-palette-dock" style="z-index: 25;">
                        <span class="dock-label">Palette (Drag onto canvas):</span>
                        <div class="palette-item" draggable="true" ondragstart="handlePaletteDragStart(event, 'router')">
                            <i class="fa-solid fa-network-wired"></i> Router
                        </div>
                        <div class="palette-item" draggable="true" ondragstart="handlePaletteDragStart(event, 'switch')">
                            <i class="fa-solid fa-diagram-project"></i> Switch
                        </div>
                        <div class="palette-item" draggable="true" ondragstart="handlePaletteDragStart(event, 'firewall')">
                            <i class="fa-solid fa-shield-halved"></i> Firewall
                        </div>
                        <div class="palette-item" draggable="true" ondragstart="handlePaletteDragStart(event, 'host')">
                            <i class="fa-solid fa-desktop"></i> Host PC
                        </div>
                        <button type="button" class="button secondary" style="padding: 4px 10px; font-size: 0.72rem; margin-left: 8px;" onclick="linkAllDefaultNodes()">
                            <i class="fa-solid fa-link"></i> Auto-Link
                        </button>
                    </div>
                </div>

                <!-- Canvas B: Routing Maze Stage -->
                <div class="canvas-panel" id="modal-canvas-routing" style="flex: 1; position: relative; min-height: 480px;">
                    <div class="maze-grid-mesh"></div>
                    <svg class="maze-wire-svg" viewBox="0 0 800 460">
                        <line x1="140" y1="230" x2="300" y2="130" class="maze-line line-active" />
                        <line x1="140" y1="230" x2="300" y2="330" class="maze-line line-blocked" />
                        <line x1="300" y1="130" x2="500" y2="130" class="maze-line line-active" />
                        <line x1="300" y1="330" x2="500" y2="330" class="maze-line" />
                        <line x1="500" y1="130" x2="660" y2="230" class="maze-line line-active" />
                        <line x1="500" y1="330" x2="660" y2="230" class="maze-line" />
                        <circle cx="400" cy="130" r="5" class="packet-particle packet-pulse-1" />
                    </svg>

                    <div class="maze-node maze-start" style="left: 90px; top: 180px;">
                        <i class="fa-solid fa-play"></i>
                        <span class="maze-label">INGRESS (SRC)</span>
                        <span class="maze-sub">10.0.0.1</span>
                    </div>

                    <div class="maze-node maze-hop active" style="left: 250px; top: 80px;">
                        <span class="hop-cost">Cost: 5</span>
                        <i class="fa-solid fa-server"></i>
                        <span class="maze-label">Hop Alpha</span>
                    </div>

                    <div class="maze-node maze-hop blocked" style="left: 250px; top: 280px;">
                        <span class="hop-badge-blocked"><i class="fa-solid fa-ban"></i> Port 80 Blocked</span>
                        <i class="fa-solid fa-server"></i>
                        <span class="maze-label">Hop Beta</span>
                    </div>

                    <div class="maze-node maze-hop active" style="left: 450px; top: 80px;">
                        <span class="hop-cost">Cost: 10</span>
                        <i class="fa-solid fa-server"></i>
                        <span class="maze-label">Hop Gamma</span>
                    </div>

                    <div class="maze-node maze-end" style="left: 610px; top: 180px;">
                        <i class="fa-solid fa-bullseye"></i>
                        <span class="maze-label">EGRESS (DEST)</span>
                        <span class="maze-sub">10.0.100.254</span>
                    </div>
                </div>

                <!-- Canvas C: Protocol Matcher Stage -->
                <div class="canvas-panel" id="modal-canvas-protocol" style="flex: 1; padding: 20px; overflow-y: auto; background: #07101e; min-height: 480px;">
                    <div class="osi-stack-container" id="osiStackContainer">
                        @foreach ([
                            [7, 'Application Layer', 'PDU: Data', ['HTTP', 'DNS']],
                            [6, 'Presentation Layer', 'PDU: Data Formatting', ['TLS / SSL']],
                            [5, 'Session Layer', 'PDU: Dialog Control', ['NetBIOS']],
                            [4, 'Transport Layer', 'PDU: Segments', ['TCP', 'UDP']],
                            [3, 'Network Layer', 'PDU: Packets', ['IPv4 / IPv6', 'ICMP']],
                            [2, 'Data Link Layer', 'PDU: Frames', ['Ethernet (802.3)']],
                            [1, 'Physical Layer', 'PDU: Bits', ['Cat6 / Fiber']],
                        ] as $layer)
                            <div class="osi-bucket" data-layer="{{ $layer[0] }}">
                                <span class="layer-num">L{{ $layer[0] }}</span>
                                <div class="layer-info">
                                    <span class="layer-name">{{ $layer[1] }}</span>
                                    <span class="layer-pdu">{{ $layer[2] }}</span>
                                </div>
                                <div class="bucket-drop-slot">
                                    @foreach ($layer[3] as $pill)
                                        <span class="slot-pill matched">{{ $pill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Canvas D: Subnet Terminal Stage -->
                <div class="canvas-panel" id="modal-canvas-subnet" style="flex: 1; padding: 24px; background: #060d17; display: flex; align-items: center; justify-content: center; min-height: 480px;">
                    <div class="terminal-window">
                        <div class="terminal-titlebar">
                            <div class="term-dots"><span></span><span></span><span></span></div>
                            <span class="term-title">netlearn-zsh — subnet-eval-worker — 80x24</span>
                        </div>
                        <div class="terminal-screen" id="modalTermScreen">
                            <p class="term-line"><span class="term-prompt">operative@netlearn:~$</span> <span class="term-cmd">subnet-probe --base <span id="mTermBase">192.168.10.0</span> --hosts <span id="mTermHosts">30</span></span></p>
                            <p class="term-line term-comment"># Live Bitwise calculation synced with lecturer inspector...</p>
                            <p class="term-line"><span class="term-cyan">[OK]</span> Calculated Subnet Mask: <strong id="mTermMask">255.255.255.224 (/27)</strong></p>
                            <p class="term-line"><span class="term-cyan">[OK]</span> Network Address: <strong id="mTermNet">192.168.10.0</strong></p>
                            <p class="term-line"><span class="term-cyan">[OK]</span> Usable Host Range: <strong id="mTermRange">192.168.10.1 - 192.168.10.30</strong></p>
                            <p class="term-line"><span class="term-cyan">[OK]</span> Broadcast Address: <strong id="mTermBcast">192.168.10.31</strong></p>
                            <p class="term-line term-success"><i class="fa-solid fa-circle-check"></i> Win-state convergence verified.</p>
                            <p class="term-line"><span class="term-prompt">operative@netlearn:~$</span> <span class="term-cursor">_</span></p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Rules Inspector Pane (Two-Way Binding) -->
            <div style="background: rgba(13, 33, 56, 0.85); border-left: 1px solid rgba(255, 255, 255, 0.1); padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px;">
                <span class="eyebrow" style="color: var(--nl-cyan); margin-bottom: 0;">Context Inspector & Rules</span>

                <!-- Form A: Topology Rules & Active Node Inspector -->
                <div class="inspector-form-group active" id="modal-inspector-topology">
                    <!-- Selected Active Node Properties Card -->
                    <div style="background: rgba(34, 211, 238, 0.05); border: 1px solid rgba(34, 211, 238, 0.25); border-radius: 12px; padding: 14px; margin-bottom: 12px;" id="activeNodePropsCard">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span class="eyebrow" style="font-size: 0.68rem; color: var(--nl-cyan); margin: 0;">Selected Node Config</span>
                            <span class="status-pill pass" id="activeNodeRolePill" style="font-size: 0.65rem; padding: 2px 6px;">ROUTER</span>
                        </div>

                        <div class="form-row">
                            <label for="inspector_node_name">Device Label</label>
                            <input type="text" id="inspector_node_name" class="macos-input" value="Core-R1" oninput="updateActiveNodeName(this.value)">
                        </div>

                        <div class="form-row">
                            <label for="inspector_node_ip">Expected Win-State IP</label>
                            <input type="text" id="inspector_node_ip" class="macos-input" value="192.168.1.1" oninput="updateActiveNodeIp(this.value)">
                            <span class="field-hint">Student input in Phaser.js must match this expected IP.</span>
                        </div>

                        <button type="button" class="btn-remove-row" style="width: 100%; height: 30px; font-size: 0.74rem; gap: 6px;" onclick="deleteActiveNode()">
                            <i class="fa-solid fa-trash-can"></i> Remove Selected Node
                        </button>
                    </div>

                    <!-- Global Board Win-Conditions -->
                    <div class="form-row">
                        <label for="m_topo_mask">Target Network Mask</label>
                        <input type="text" id="m_topo_mask" class="macos-input" value="255.255.255.0">
                    </div>

                    <div class="toggle-card">
                        <div class="toggle-info">
                            <span class="toggle-title">Enforce Ping Win-State</span>
                            <span class="toggle-desc">Students must successfully ping the gateway.</span>
                        </div>
                        <label class="macos-toggle">
                            <input type="checkbox" id="m_topo_ping" checked>
                            <span class="toggle-track"></span>
                        </label>
                    </div>
                </div>

                <!-- Form B: Routing Rules -->
                <div class="inspector-form-group" id="modal-inspector-routing">
                    <div class="form-row">
                        <label for="m_routing_blocked">Blocked Ports (ACLs)</label>
                        <input type="text" id="m_routing_blocked" class="macos-input" value="Port 80 (HTTP)">
                    </div>
                    <div class="form-row">
                        <label for="m_routing_cost">Max Allowed Path Cost</label>
                        <input type="number" id="m_routing_cost" class="macos-input" value="15">
                    </div>
                    <div class="form-row">
                        <label for="m_routing_limit">Packet Time Limit (seconds)</label>
                        <input type="number" id="m_routing_limit" class="macos-input" value="60">
                    </div>
                </div>

                <!-- Form C: Protocol Rules -->
                <div class="inspector-form-group" id="modal-inspector-protocol">
                    <span class="field-hint">Drag protocol cards into the 7 OSI buckets on canvas to assign answer keys.</span>
                    <button type="button" class="btn-add-protocol" onclick="applyPresetTemplate('proto_web')">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill Web Stack
                    </button>
                </div>

                <!-- Form D: Subnet Rules & Auto-Calculation -->
                <div class="inspector-form-group" id="modal-inspector-subnet">
                    <div class="form-row">
                        <label for="m_subnet_base">Base Network IP</label>
                        <input type="text" id="m_subnet_base" class="macos-input" value="192.168.10.0" oninput="modalCalcSubnet()">
                    </div>
                    <div class="form-row">
                        <label for="m_subnet_hosts">Required Hosts</label>
                        <input type="number" id="m_subnet_hosts" class="macos-input" value="30" oninput="modalCalcSubnet()">
                    </div>

                    <div class="auto-calc-notice">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Auto-calculated bitwise win-conditions ready for Phaser.js.</span>
                    </div>

                    <div class="calculated-blueprint-card" style="margin-top: 10px;">
                        <div class="blueprint-grid-specs">
                            <div class="spec-row">
                                <span class="spec-label">Subnet Mask:</span>
                                <strong class="spec-val text-cyan" id="mCalcMask">255.255.255.224</strong>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">CIDR Prefix:</span>
                                <strong class="spec-val" id="mCalcCidr">/27</strong>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">Usable Range:</span>
                                <strong class="spec-val text-amber" id="mCalcRange">192.168.10.1 - 192.168.10.30</strong>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">Broadcast:</span>
                                <strong class="spec-val" id="mCalcBcast">192.168.10.31</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Global Simulation XP Settings in Inspector -->
                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span class="spec-label" style="font-size: 0.72rem; color: #fbbf24; font-weight: 700;">XP Clear Award</span>
                        <span class="xp-badge" id="modalXpBadge">+150 XP</span>
                    </div>
                    <input
                        type="range"
                        id="modal_xp_slider"
                        class="macos-slider"
                        min="50"
                        max="500"
                        step="25"
                        value="150"
                        oninput="document.getElementById('modalXpBadge').textContent = '+' + this.value + ' XP'"
                    >
                </div>

            </div>
        </div>
    </div>
</div>


<!-- =====================================================================
     LAYER 4: WARNING DELETE CONFIRMATION MODAL
     Protects lecturers from accidental data loss of authored simulations
     ===================================================================== -->
<div class="nl-modal-backdrop" id="deleteModalBackdrop">
    <div class="delete-modal-dialog" role="dialog" aria-modal="true">
        <div style="padding: 24px; text-align: center; display: flex; flex-direction: column; align-items: center;">
            <div class="delete-warning-icon-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h3 style="font-size: 1.15rem; font-weight: 700; color: #FFFFFF; margin: 0 0 8px 0;">
                Delete Challenge Mission?
            </h3>

            <p style="font-size: 0.84rem; color: var(--nl-text-secondary); line-height: 1.5; margin: 0 0 16px 0;">
                Building an interactive simulation takes significant effort. Deleting <strong id="deleteModalQuizTitle" style="color: #f87171;">this quiz</strong> will permanently remove all associated mission sub-tasks, win conditions, and student progress telemetry.
            </p>

            <form id="deleteQuizForm" method="POST" action="" style="width: 100%; display: flex; gap: 10px; justify-content: center;">
                @csrf
                @method('DELETE')
                <button type="button" class="button secondary" onclick="closeDeleteConfirmModal()" style="flex: 1;">
                    Cancel
                </button>
                <button type="submit" class="button" style="background: rgba(239, 68, 68, 0.9); border-color: #ef4444; color: #FFFFFF; flex: 1;">
                    <i class="fa-solid fa-trash-can"></i> Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>


<!-- Sleek Glass Toast Notification -->
<div id="netlearnToast" style="display: none; position: fixed; bottom: 24px; right: 24px; z-index: 9999; background: rgba(13, 33, 56, 0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--nl-cyan); color: #FFFFFF; padding: 14px 20px; border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,0.6); font-size: 0.88rem; align-items: center; gap: 12px; transition: opacity 0.3s cubic-bezier(0.25, 1, 0.5, 1);">
    <i id="toastIcon" class="fa-solid fa-circle-check" style="color: var(--nl-cyan); font-size: 1.15rem;"></i>
    <span id="toastMsg" style="font-weight: 600;">Action completed successfully</span>
</div>
@endsection

@section('scripts')
<script>
/**
 * NetLearn Progressive Disclosure & 4-Layer Modal Management
 * Layer 1: Mission Steps (Right-Side Slide-Over)
 * Layer 2: Settings & Gamification (Centered Modal)
 * Layer 3: Canvas Design Board (Full-Screen Spatial Modal)
 * Layer 4: Warning Delete Confirmation Modal
 */

// Active State Tracker
let activeQuizId = null;
let activeTopicId = null;
let activeTitle = '';
let currentPuzzleCategory = 'topology';
let isSandboxActive = false;

// Global Vanilla JS State Objects
let puzzleState = {
    mission_id: null,
    topic_id: null,
    nodes: [],
    links: [],
    win_conditions: {}
};

let sequenceState = {
    mission_id: null,
    puzzle_type: 'sequencing',
    correct_order: ['Application', 'Presentation', 'Session', 'Transport', 'Network', 'Data Link', 'Physical'],
    win_conditions: { passing_mark: 100, xp_reward: 150 }
};

let subnetState = {
    mission_id: null,
    puzzle_type: 'subnetting',
    scenario_briefing: "",
    win_conditions: {
        base_ip: "192.168.10.0",
        required_hosts: 30,
        network_address: "192.168.10.0",
        broadcast_address: "192.168.10.31",
        subnet_mask: "255.255.255.224",
        cidr: "/27",
        first_usable: "192.168.10.1",
        last_usable: "192.168.10.30"
    }
};

let activeNodeId = null;


/* ==========================================================================
   LAYER 1: MISSION STEPS (RIGHT-SIDE SLIDE-OVER)
   ========================================================================== */

function openMissionStepsSlideOver(quizId, title) {
    activeQuizId = quizId;
    activeTitle = title;

    document.getElementById('stepsSlideoverTitle').textContent = 'Mission Steps: ' + title;
    document.getElementById('legacyQuestionsLink').href = `/lecturer/quizzes/${quizId}/questions`;

    const container = document.getElementById('stepsListContainer');
    container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--nl-text-tertiary); font-size: 0.82rem;"><i class="fa-solid fa-spinner fa-spin" style="margin-right: 8px;"></i> Loading mission steps...</div>';

    fetch(`/lecturer/quizzes/${quizId}/steps-data`)
        .then(res => res.json())
        .then(data => {
            renderMissionSteps(data.steps || []);
        })
        .catch(err => {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: #f87171; font-size: 0.82rem;">Failed to load mission steps.</div>';
        });

    document.getElementById('stepsSlideoverBackdrop').classList.add('active');
}

function closeMissionStepsSlideOver() {
    document.getElementById('stepsSlideoverBackdrop').classList.remove('active');
}

function renderMissionSteps(steps) {
    const container = document.getElementById('stepsListContainer');
    if (!steps || steps.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 28px; color: var(--nl-text-tertiary); font-size: 0.84rem; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.08);">
                <i class="fa-solid fa-clipboard-list" style="font-size: 1.5rem; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                No sub-task steps defined yet.<br>Add your first step above to structure this challenge.
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    steps.forEach((step, index) => {
        const card = document.createElement('div');
        card.className = 'step-card';
        card.id = `step-card-${step.question_id}`;
        card.innerHTML = `
            <span class="step-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
            <span class="step-num-badge">${index + 1}</span>
            <span class="step-content">${escapeHtml(step.instruction)}</span>
            <button type="button" class="step-delete-btn" onclick="deleteMissionStep(${step.question_id})" title="Delete sub-task">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        `;
        container.appendChild(card);
    });
}

function addNewMissionStep() {
    const input = document.getElementById('newStepInput');
    const instruction = input.value.trim();
    if (!instruction) return;

    fetch(`/lecturer/quizzes/${activeQuizId}/steps-data`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ instruction: instruction })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            input.value = '';
            // Refresh steps
            openMissionStepsSlideOver(activeQuizId, activeTitle);
            updateTableRowStepCount(activeQuizId, data.total_steps);
            showToast('✓ Sub-task step added successfully.', 'success');
        }
    })
    .catch(err => {
        showToast('⚠️ Could not add step: ' + err.message, 'error');
    });
}

function deleteMissionStep(questionId) {
    if (!confirm('Remove this sub-task from the mission?')) return;

    fetch(`/lecturer/quizzes/${activeQuizId}/steps-data/${questionId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            openMissionStepsSlideOver(activeQuizId, activeTitle);
            updateTableRowStepCount(activeQuizId, data.total_steps);
            showToast('✓ Sub-task step removed.', 'success');
        }
    });
}

function updateTableRowStepCount(quizId, count) {
    const tableSpan = document.getElementById(`table-step-count-${quizId}`);
    if (tableSpan) tableSpan.textContent = count;
    const btnSpan = document.getElementById(`btn-step-count-${quizId}`);
    if (btnSpan) btnSpan.textContent = `Steps (${count})`;
}

function launchCanvasFromSteps() {
    closeMissionStepsSlideOver();
    openPuzzleCanvasModal(activeQuizId, activeTopicId || 1, activeTitle, currentPuzzleCategory);
}


/* ==========================================================================
   LAYER 2: SETTINGS & GAMIFICATION (CENTERED MODAL)
   ========================================================================== */

function openQuizSettingsModal(quizId, title, topicId, passingMark, category = 'topology', scenario = '', xp = 150) {
    activeQuizId = quizId;
    activeTopicId = topicId;
    activeTitle = title;

    const form = document.getElementById('settingsQuizForm');
    form.action = '/lecturer/quizzes/' + quizId;
    document.getElementById('settingsMethodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('modal_quiz_id').value = quizId;
    document.getElementById('modal_title').value = title;
    document.getElementById('modal_topic_id').value = topicId;
    document.getElementById('modal_passing_mark').value = passingMark;
    document.getElementById('settingsPassingBadge').textContent = passingMark + '%';
    document.getElementById('modal_scenario').value = scenario || 'Branch office router rebooted with cleared NVRAM. Configure routing protocols and establish gateway convergence.';
    document.getElementById('modal_xp_input').value = xp || 150;
    document.getElementById('settingsXpBadge').textContent = '+' + (xp || 150) + ' XP';

    document.getElementById('settingsModalTitle').textContent = 'Edit Challenge: ' + title;
    document.getElementById('settingsSubmitBtnText').textContent = 'Save Settings';

    selectModalCategoryCard(category || 'topology');

    document.getElementById('settingsModalBackdrop').classList.add('active');
}

function openNewQuizSettingsModal() {
    activeQuizId = null;
    activeTopicId = null;
    activeTitle = '';

    const form = document.getElementById('settingsQuizForm');
    form.action = '{{ route("lecturer.quizzes.store") }}';
    document.getElementById('settingsMethodContainer').innerHTML = '';

    document.getElementById('modal_quiz_id').value = '';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_passing_mark').value = 80;
    document.getElementById('settingsPassingBadge').textContent = '80%';
    document.getElementById('modal_scenario').value = 'Network scenario briefing: Analyze subnet architecture and establish node communication.';
    document.getElementById('modal_xp_input').value = 150;
    document.getElementById('settingsXpBadge').textContent = '+150 XP';

    document.getElementById('settingsModalTitle').textContent = 'New Quiz Challenge Mission';
    document.getElementById('settingsSubmitBtnText').textContent = 'Create Challenge Mission';

    selectModalCategoryCard('topology');

    document.getElementById('settingsModalBackdrop').classList.add('active');
}

function closeQuizSettingsModal() {
    document.getElementById('settingsModalBackdrop').classList.remove('active');
}

function selectModalCategoryCard(category) {
    currentPuzzleCategory = category;
    document.getElementById('modal_puzzle_category').value = category;

    document.querySelectorAll('#settingsModalBackdrop .category-select-card').forEach(card => {
        card.classList.toggle('selected', card.getAttribute('data-cat') === category);
    });
}


/* ==========================================================================
   LAYER 3: DESIGN BOARD (FULL-SCREEN SPATIAL CANVAS MODAL)
   ========================================================================== */

function openPuzzleCanvasModal(quizId, topicId, title, category = 'topology', xp = 150) {
    activeQuizId = quizId;
    activeTopicId = topicId;
    activeTitle = title;

    document.getElementById('modalMissionTitle').textContent = 'Design Board: ' + title;
    document.getElementById('modal_xp_slider').value = xp || 150;
    document.getElementById('modalXpBadge').textContent = '+' + (xp || 150) + ' XP';

    // Initialize Global State
    puzzleState.mission_id = quizId;
    puzzleState.topic_id = topicId;

    // Load any existing board configuration from server
    fetch(`/lecturer/puzzles/${topicId}/state`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.puzzle && data.puzzle.correct_answer) {
                const ans = data.puzzle.correct_answer;
                if (ans.nodes && ans.nodes.length > 0) {
                    puzzleState.nodes = ans.nodes;
                    puzzleState.links = ans.links || [];
                    puzzleState.win_conditions = ans.win_conditions || {};
                } else {
                    initDefaultTopologyNodes();
                }
            } else {
                initDefaultTopologyNodes();
            }
            renderTopologyCanvas();
        })
        .catch(() => {
            initDefaultTopologyNodes();
            renderTopologyCanvas();
        });

    switchModalCategory(category);
    document.getElementById('puzzleCanvasBackdrop').classList.add('active');
}

function closePuzzleCanvasModal() {
    document.getElementById('puzzleCanvasBackdrop').classList.remove('active');
    if (isSandboxActive) toggleStudentSandbox();
}

function initDefaultTopologyNodes() {
    puzzleState.nodes = [
        { id: 'node_r1', type: 'router', name: 'Core-R1', role: 'GATEWAY_ROUTER', expected_ip: '192.168.1.1', x: 120, y: 120 },
        { id: 'node_sw1', type: 'switch', name: 'Distribution-SW01', role: 'DISTRIBUTION_SW', expected_ip: 'VLAN 10', x: 360, y: 240 },
        { id: 'node_pc1', type: 'host', name: 'Client-Host-A', role: 'STATION_ENDPOINT', expected_ip: '192.168.1.100', x: 600, y: 120 },
    ];
    puzzleState.links = [
        { source: 'node_r1', target: 'node_sw1' },
        { source: 'node_sw1', target: 'node_pc1' }
    ];
    activeNodeId = 'node_r1';
}

function switchModalCategory(category) {
    currentPuzzleCategory = category;

    document.querySelectorAll('[data-modal-cat]').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-modal-cat') === category);
    });

    document.querySelectorAll('#puzzleCanvasWindow .canvas-panel').forEach(panel => {
        panel.classList.toggle('active', panel.id === `modal-canvas-${category}`);
    });

    document.querySelectorAll('#puzzleCanvasWindow .inspector-form-group').forEach(panel => {
        panel.classList.toggle('active', panel.id === `modal-inspector-${category}`);
    });

    if (category === 'subnet') modalCalcSubnet();
    if (category === 'topology') checkTopologySyntax();
}

/* ── Interactive Topology Drag, Drop, and Render ── */

function handlePaletteDragStart(e, nodeType) {
    e.dataTransfer.setData('text/plain', nodeType);
}

function handleCanvasDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
}

function handleCanvasDrop(e) {
    e.preventDefault();
    const nodeType = e.dataTransfer.getData('text/plain') || 'router';
    const canvasRect = document.getElementById('modal-canvas-topology').getBoundingClientRect();

    const x = Math.max(20, Math.min(canvasRect.width - 150, e.clientX - canvasRect.left - 60));
    const y = Math.max(20, Math.min(canvasRect.height - 100, e.clientY - canvasRect.top - 40));

    const id = `node_${Date.now()}`;
    const nameMap = {
        router: 'Gateway-R' + (puzzleState.nodes.filter(n => n.type === 'router').length + 1),
        switch: 'SW-Fabric0' + (puzzleState.nodes.filter(n => n.type === 'switch').length + 1),
        firewall: 'Edge-Firewall',
        host: 'Workstation-' + String.fromCharCode(65 + puzzleState.nodes.filter(n => n.type === 'host').length)
    };

    const roleMap = {
        router: 'GATEWAY_ROUTER',
        switch: 'DISTRIBUTION_SW',
        firewall: 'SECURITY_GATEWAY',
        host: 'STATION_ENDPOINT'
    };

    const ipMap = {
        router: '192.168.1.1',
        switch: 'VLAN 10',
        firewall: '192.168.1.254',
        host: '192.168.1.' + (100 + puzzleState.nodes.filter(n => n.type === 'host').length)
    };

    const newNode = {
        id: id,
        type: nodeType,
        name: nameMap[nodeType] || 'Node',
        role: roleMap[nodeType] || 'DEVICE',
        expected_ip: ipMap[nodeType] || '192.168.1.50',
        x: x,
        y: y
    };

    puzzleState.nodes.push(newNode);
    activeNodeId = id;
    renderTopologyCanvas();
    syncActiveNodeInspector();
    checkTopologySyntax();
}

function renderTopologyCanvas() {
    renderTopologyNodes();
    renderTopologyConnections();
}

function renderTopologyNodes() {
    const container = document.getElementById('topologyNodesContainer');
    if (!container) return;
    container.innerHTML = '';

    const iconMap = {
        router: 'fa-network-wired',
        switch: 'fa-diagram-project',
        firewall: 'fa-shield-halved',
        host: 'fa-desktop'
    };

    puzzleState.nodes.forEach(node => {
        const el = document.createElement('div');
        el.className = `stage-node node-${node.type}` + (node.id === activeNodeId ? ' active-node' : '');
        el.id = node.id;
        el.style.left = node.x + 'px';
        el.style.top = node.y + 'px';

        el.innerHTML = `
            <div class="node-icon-box"><i class="fa-solid ${iconMap[node.type] || 'fa-server'}"></i></div>
            <div class="node-details">
                <span class="node-role">${node.role}</span>
                <span class="node-name">${escapeHtml(node.name)}</span>
                <span class="node-meta">${escapeHtml(node.expected_ip)}</span>
            </div>
            <span class="node-status-led led-green"></span>
        `;

        // Click to select node
        el.addEventListener('mousedown', (e) => {
            selectActiveNode(node.id);
            initNodeDrag(e, node);
        });

        container.appendChild(el);
    });
}

function renderTopologyConnections() {
    const svg = document.getElementById('topologyWiresSvg');
    if (!svg) return;
    svg.innerHTML = '';

    puzzleState.links.forEach((link, idx) => {
        const src = puzzleState.nodes.find(n => n.id === link.source);
        const tgt = puzzleState.nodes.find(n => n.id === link.target);
        if (!src || !tgt) return;

        const x1 = src.x + 65;
        const y1 = src.y + 45;
        const x2 = tgt.x + 65;
        const y2 = tgt.y + 45;

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const cx = (x1 + x2) / 2;
        path.setAttribute('d', `M ${x1} ${y1} C ${cx} ${y1}, ${cx} ${y2}, ${x2} ${y2}`);
        path.setAttribute('class', 'cable-path');
        svg.appendChild(path);

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', String(cx));
        circle.setAttribute('cy', String((y1 + y2) / 2));
        circle.setAttribute('r', '4');
        circle.setAttribute('class', `packet-particle packet-pulse-${(idx % 2) + 1}`);
        svg.appendChild(circle);
    });
}

function linkAllDefaultNodes() {
    puzzleState.links = [];
    const routers = puzzleState.nodes.filter(n => n.type === 'router');
    const switches = puzzleState.nodes.filter(n => n.type === 'switch');
    const hosts = puzzleState.nodes.filter(n => n.type === 'host');

    routers.forEach(r => {
        switches.forEach(s => {
            puzzleState.links.push({ source: r.id, target: s.id });
        });
    });

    switches.forEach(s => {
        hosts.forEach(h => {
            puzzleState.links.push({ source: s.id, target: h.id });
        });
    });

    renderTopologyConnections();
    showToast('✓ Nodes interconnected via distribution fabric.', 'success');
}

function selectActiveNode(id) {
    activeNodeId = id;
    document.querySelectorAll('.stage-node').forEach(el => {
        el.classList.toggle('active-node', el.id === id);
    });
    syncActiveNodeInspector();
}

function syncActiveNodeInspector() {
    const node = puzzleState.nodes.find(n => n.id === activeNodeId);
    if (!node) return;

    document.getElementById('inspector_node_name').value = node.name;
    document.getElementById('inspector_node_ip').value = node.expected_ip;
    document.getElementById('activeNodeRolePill').textContent = node.role;
}

function updateActiveNodeName(val) {
    const node = puzzleState.nodes.find(n => n.id === activeNodeId);
    if (node) {
        node.name = val;
        const nameEl = document.querySelector(`#${node.id} .node-name`);
        if (nameEl) nameEl.textContent = val;
    }
}

function updateActiveNodeIp(val) {
    const node = puzzleState.nodes.find(n => n.id === activeNodeId);
    if (node) {
        node.expected_ip = val;
        const metaEl = document.querySelector(`#${node.id} .node-meta`);
        if (metaEl) metaEl.textContent = val;
        checkTopologySyntax();
    }
}

function deleteActiveNode() {
    if (!activeNodeId) return;
    puzzleState.nodes = puzzleState.nodes.filter(n => n.id !== activeNodeId);
    puzzleState.links = puzzleState.links.filter(l => l.source !== activeNodeId && l.target !== activeNodeId);
    activeNodeId = puzzleState.nodes.length > 0 ? puzzleState.nodes[0].id : null;
    renderTopologyCanvas();
    syncActiveNodeInspector();
    checkTopologySyntax();
}

function initNodeDrag(e, node) {
    e.preventDefault();
    const startX = e.clientX;
    const startY = e.clientY;
    const initialNodeX = node.x;
    const initialNodeY = node.y;

    function onMouseMove(ev) {
        node.x = initialNodeX + (ev.clientX - startX);
        node.y = initialNodeY + (ev.clientY - startY);
        const el = document.getElementById(node.id);
        if (el) {
            el.style.left = node.x + 'px';
            el.style.top = node.y + 'px';
        }
        renderTopologyConnections();
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

// ── Smart Defaults & Presets Menu ──
function applyPresetTemplate(presetKey) {
    if (!presetKey) return;

    if (presetKey === 'topo_wan') {
        switchModalCategory('topology');
        puzzleState.nodes = [
            { id: 'node_r1', type: 'router', name: 'WAN-Core-R1', role: 'GATEWAY_ROUTER', expected_ip: '10.0.0.1/30', x: 140, y: 160 },
            { id: 'node_r2', type: 'router', name: 'WAN-Branch-R2', role: 'REMOTE_GATEWAY', expected_ip: '10.0.0.2/30', x: 500, y: 160 }
        ];
        puzzleState.links = [{ source: 'node_r1', target: 'node_r2' }];
        activeNodeId = 'node_r1';
        renderTopologyCanvas();
        syncActiveNodeInspector();
        checkTopologySyntax();
    } else if (presetKey === 'topo_switch_hosts') {
        switchModalCategory('topology');
        initDefaultTopologyNodes();
        renderTopologyCanvas();
        syncActiveNodeInspector();
        checkTopologySyntax();
    } else if (presetKey === 'subnet_30') {
        switchModalCategory('subnet');
        document.getElementById('m_subnet_base').value = '192.168.10.0';
        document.getElementById('m_subnet_hosts').value = 30;
        modalCalcSubnet();
    } else if (presetKey === 'proto_web') {
        switchModalCategory('protocol');
    }
}

// ── Real-Time Syntax Checking ──
function checkTopologySyntax() {
    const banner = document.getElementById('syntaxWarningBanner');
    const text = document.getElementById('syntaxWarningText');

    const router = puzzleState.nodes.find(n => n.type === 'router');
    const host = puzzleState.nodes.find(n => n.type === 'host');

    if (!router || !host) {
        banner.style.background = 'rgba(16, 185, 129, 0.12)';
        banner.style.borderColor = 'rgba(16, 185, 129, 0.35)';
        banner.style.color = '#4ade80';
        text.innerHTML = `<strong>✓ Topology Canvas Ready:</strong> Drag routers, switches, and hosts to design the challenge scenario.`;
        return;
    }

    const rSub = router.expected_ip.split('.').slice(0, 3).join('.');
    const hSub = host.expected_ip.split('.').slice(0, 3).join('.');

    if (rSub !== hSub) {
        banner.style.background = 'rgba(239, 68, 68, 0.15)';
        banner.style.borderColor = 'rgba(239, 68, 68, 0.4)';
        banner.style.color = '#f87171';
        text.innerHTML = `<strong>⚠️ Subnet Mismatch Detected:</strong> ${router.name} (${router.expected_ip}) and ${host.name} (${host.expected_ip}) reside on different subnets. Students cannot ping across this link without routing.`;
    } else {
        banner.style.background = 'rgba(16, 185, 129, 0.12)';
        banner.style.borderColor = 'rgba(16, 185, 129, 0.35)';
        banner.style.color = '#4ade80';
        text.innerHTML = `<strong>✓ Syntax Validated:</strong> Nodes converge cleanly within the ${rSub}.0/24 subnet boundary. Ready for Phaser.js compilation.`;
    }
}

// ── Sandbox Mode ──
function toggleStudentSandbox() {
    isSandboxActive = !isSandboxActive;
    const btn = document.getElementById('btnSandboxToggle');
    const hud = document.getElementById('sandboxHud');
    const btnText = document.getElementById('sandboxBtnText');

    if (isSandboxActive) {
        btn.classList.add('in-sandbox');
        btnText.textContent = 'Exit Playtest';
        hud.style.display = 'flex';
    } else {
        btn.classList.remove('in-sandbox');
        btnText.textContent = 'Test as Student';
        hud.style.display = 'none';
    }
}

function simulateStudentPing() {
    showToast('🛰️ [PING SUCCESS] ICMP Echo reply received! Round-trip: 1.4ms. Student win-state triggered.', 'success');
}

// ── Auto-Calculating Subnet Calculator ──
function modalCalcSubnet() {
    const baseIp = document.getElementById('m_subnet_base')?.value.trim() || '192.168.10.0';
    const reqHosts = parseInt(document.getElementById('m_subnet_hosts')?.value || 30, 10);

    const hostBits = Math.max(2, Math.ceil(Math.log2(reqHosts + 2)));
    const prefix = 32 - hostBits;
    const totalAddresses = Math.pow(2, hostBits);

    const maskInt = (0xFFFFFFFF << hostBits) >>> 0;
    const maskString = [(maskInt >>> 24) & 255, (maskInt >>> 16) & 255, (maskInt >>> 8) & 255, maskInt & 255].join('.');

    const ipOctets = baseIp.split('.').map(o => parseInt(o, 10) || 0);
    while (ipOctets.length < 4) ipOctets.push(0);

    const netId = ipOctets.slice(0, 3).join('.') + '.0';
    const firstUsable = ipOctets.slice(0, 3).join('.') + '.1';
    const lastUsable = ipOctets.slice(0, 3).join('.') + `.${totalAddresses - 2}`;
    const bcast = ipOctets.slice(0, 3).join('.') + `.${totalAddresses - 1}`;

    subnetState.win_conditions = {
        base_ip: baseIp,
        required_hosts: reqHosts,
        network_address: netId,
        broadcast_address: bcast,
        subnet_mask: maskString,
        cidr: `/${prefix}`,
        first_usable: firstUsable,
        last_usable: lastUsable
    };

    document.getElementById('mCalcMask').textContent = maskString;
    document.getElementById('mCalcCidr').textContent = `/${prefix}`;
    document.getElementById('mCalcRange').textContent = `${firstUsable} - ${lastUsable}`;
    document.getElementById('mCalcBcast').textContent = bcast;

    document.getElementById('mTermBase').textContent = baseIp;
    document.getElementById('mTermHosts').textContent = reqHosts;
    document.getElementById('mTermMask').textContent = `${maskString} (/${prefix})`;
    document.getElementById('mTermNet').textContent = netId;
    document.getElementById('mTermRange').textContent = `${firstUsable} - ${lastUsable}`;
    document.getElementById('mTermBcast').textContent = bcast;
}

// ── Save & Lock Board Configuration (POST JSON to Backend) ──
function savePuzzleBoardConfiguration() {
    const saveBtn = document.getElementById('btnSavePuzzleBoard');
    const saveBtnText = document.getElementById('btnSavePuzzleBoardText');

    let answerPayload = {};
    if (currentPuzzleCategory === 'topology') {
        puzzleState.win_conditions = {
            target_mask: document.getElementById('m_topo_mask')?.value || '255.255.255.0',
            ping_enforced: document.getElementById('m_topo_ping')?.checked ?? true
        };
        answerPayload = {
            nodes: puzzleState.nodes,
            links: puzzleState.links,
            win_conditions: puzzleState.win_conditions
        };
    } else if (currentPuzzleCategory === 'routing') {
        answerPayload = {
            blocked_ports: document.getElementById('m_routing_blocked')?.value || 'Port 80',
            max_cost: parseInt(document.getElementById('m_routing_cost')?.value || 15, 10),
            time_limit: parseInt(document.getElementById('m_routing_limit')?.value || 60, 10)
        };
    } else if (currentPuzzleCategory === 'protocol') {
        answerPayload = sequenceState;
    } else if (currentPuzzleCategory === 'subnet') {
        answerPayload = subnetState;
    }

    const xpPoints = parseInt(document.getElementById('modal_xp_slider')?.value || 150, 10);

    const payload = {
        quiz_id: activeQuizId,
        topic_id: activeTopicId,
        puzzle_type: currentPuzzleCategory,
        points: xpPoints,
        instruction: `Simulation Challenge: ${activeTitle || 'Networking Lab'}`,
        question_text: `Interactive mission evaluating ${currentPuzzleCategory} mechanics.`,
        correct_answer: answerPayload
    };

    saveBtn.disabled = true;
    saveBtnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

    fetch('{{ route("lecturer.puzzles.configure") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => {
        if (!res.ok) throw new Error('Server error while saving configuration.');
        return res.json();
    })
    .then(data => {
        saveBtn.disabled = false;
        saveBtnText.innerHTML = '<i class="fa-solid fa-check"></i> Saved!';

        showToast('✓ Simulation Board locked and synced with quiz curriculum!', 'success');

        // Dynamically update the status indicator tag on table to "Ready"
        if (activeQuizId) {
            const statusContainer = document.getElementById('status-container-' + activeQuizId);
            if (statusContainer) {
                const categoryLabels = {
                    'topology': ['Topology Builder', 'fa-network-wired', 'tag-topology'],
                    'routing': ['Routing Maze', 'fa-route', 'tag-routing'],
                    'protocol': ['Protocol Matcher', 'fa-layer-group', 'tag-protocol'],
                    'subnet': ['Subnet Calculator', 'fa-calculator', 'tag-subnet'],
                };
                const info = categoryLabels[currentPuzzleCategory] || categoryLabels['topology'];
                statusContainer.innerHTML = `
                    <span class="status-indicator-tag ready">
                        <span class="status-dot ready"></span>
                        <span>Ready</span>
                    </span>
                    <span class="item-tag ${info[2]}" id="puzzle-badge-${activeQuizId}" style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid ${info[1]}"></i>
                        <span>${info[0]}</span>
                    </span>
                `;
            }
        }

        setTimeout(() => {
            saveBtnText.innerHTML = 'Save & Lock Board';
            closePuzzleCanvasModal();
        }, 1000);
    })
    .catch(err => {
        saveBtn.disabled = false;
        saveBtnText.innerHTML = 'Save & Lock Board';
        showToast('⚠️ Failed to save simulation: ' + err.message, 'error');
    });
}


/* ==========================================================================
   LAYER 4: WARNING DELETE CONFIRMATION MODAL
   ========================================================================== */

function openDeleteConfirmModal(quizId, title) {
    document.getElementById('deleteModalQuizTitle').textContent = `"${title}"`;
    document.getElementById('deleteQuizForm').action = `/lecturer/quizzes/${quizId}`;
    document.getElementById('deleteModalBackdrop').classList.add('active');
}

function closeDeleteConfirmModal() {
    document.getElementById('deleteModalBackdrop').classList.remove('active');
}


/* ==========================================================================
   TOAST & KEYBOARD NAVIGATION
   ========================================================================== */

function showToast(msg, type = 'success') {
    const toast = document.getElementById('netlearnToast');
    const toastMsg = document.getElementById('toastMsg');
    const toastIcon = document.getElementById('toastIcon');

    toastMsg.textContent = msg;
    if (type === 'error') {
        toast.style.borderColor = '#ef4444';
        toastIcon.className = 'fa-solid fa-circle-exclamation';
        toastIcon.style.color = '#f87171';
    } else {
        toast.style.borderColor = 'var(--nl-cyan)';
        toastIcon.className = 'fa-solid fa-circle-check';
        toastIcon.style.color = 'var(--nl-cyan)';
    }

    toast.style.display = 'flex';
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, 3500);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}

// Keyboard shortcuts: ESC closes top-most active overlay
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (document.getElementById('deleteModalBackdrop').classList.contains('active')) {
            closeDeleteConfirmModal();
        } else if (document.getElementById('puzzleCanvasBackdrop').classList.contains('active')) {
            closePuzzleCanvasModal();
        } else if (document.getElementById('settingsModalBackdrop').classList.contains('active')) {
            closeQuizSettingsModal();
        } else if (document.getElementById('stepsSlideoverBackdrop').classList.contains('active')) {
            closeMissionStepsSlideOver();
        }
    }
});
</script>
@endsection
