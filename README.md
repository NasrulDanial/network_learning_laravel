# NetLearn — Gamified Puzzle-Based Learning (PBL) Mission Engine

> **A high-end, gamified educational platform for teaching Computer Networking at UiTM Arau.**  
> Built with Laravel, Phaser 3, MySQL, and a bespoke macOS-inspired glassmorphic design system.

---

## 🚀 Overview

**NetLearn** transforms traditional rote-memorization quizzes into dynamic **Puzzle-Based Learning (PBL)** missions. Rather than answering static multiple-choice questions, students solve interactive network topology configurations, OSI layer sequencing, protocol mapping, and subnet routing challenges inside an immersive virtual laboratory terminal.

### Key Architectural Pillars

1. **Topic → Mission → Puzzle Hierarchy**:
   - Each **Topic** (e.g., OSI Reference Model, Network Hardware) contains structured **Missions**.
   - Each **Mission** possesses a narrative objective, pass mark percentage, XP reward, and a sequential chain of **Puzzles**.
   - Missions unlock progressively as students clear prerequisites with the required passing score.

2. **Server-Side Authoritative Evaluation**:
   - The browser client is **never trusted**. Client-submitted `is_correct` and `score` fields are discarded.
   - All player submissions are evaluated strictly on the server by `PuzzleEvaluatorService` using private truth tables.
   - Student game clients receive safe JSON data via `getSafeActivityData()` with all answers and pair mappings stripped.

3. **Anti-Farming XP Delta Engine**:
   - XP is awarded strictly for the **personal-best score delta** on each puzzle attempt via `MissionProgressionService`.
   - Replaying a cleared puzzle with the same or lower score awards **0 additional XP**, permanently preventing score farming.
   - Completing a mission awards a one-time Mission Completion XP bounty.

4. **Zero-Destructive Schema Evolution**:
   - Forward-only migrations preserve all existing user accounts, progress records, conventional quizzes, and results.
   - Conventional quizzes remain accessible alongside the interactive PBL mission board.

---

## 🧩 The 6 Puzzle Archetypes

NetLearn features six distinct puzzle mechanics, each driven by dedicated Phaser 3 interaction templates and server evaluators:

| Archetype | Key | Interaction Mechanism | Telemetry Payload | Example Networking Challenge |
| :--- | :--- | :--- | :--- | :--- |
| **Identification** | `identification` | Interactive cards with network telemetry HUD | `{ "selected_option": "opt_2" }` | Identifying default gateway in a routing table |
| **Matching** | `matching` | Multi-node connection board with click-to-pair lines | `{ "pairs": { "item_0": "opt_1" } }` | Mapping network protocols to default ports |
| **Sequencing** | `sequencing` | Drag-and-drop slots with visual order indicators | `{ "ordered_ids": ["layer_1", ...] }` | Arranging OSI model layers (Physical to Application) |
| **Classification** | `classification` | Multi-category bins with slot counts and device pills | `{ "placements": { "dev_1": "cat_2" } }` | Sorting devices into Layer 2 vs Layer 3 broadcast domains |
| **Drag & Drop** | `drag_and_drop` | Interactive grid canvas with device nodes and dropzones | `{ "placements": { "node_1": "zone_A" } }` | Placing routers, switches, and PCs in star topology |
| **Scenario** | `scenario` | Narrative briefing terminal with branch options | `{ "choice_id": "ch_2" }` | Diagnosing packet drop issues and picking remediation |

---

## 🔒 Security & Role Architecture

The platform operates on a strict **two-tier role system**:
- **`student`**: Can view mission boards, play published puzzles, submit answers, earn XP, and unlock ranks.
- **`lecturer`**: Can create and edit missions, author puzzles via code-free visual forms, preview gameplay in sandbox mode, and manage curriculum topics.

### Security Enforcements
- **Public Registration Guard**: Public registration (`/register`) is hardcoded to assign `role = 'student'`. Any client-injected role parameters (e.g. `role=lecturer`) are ignored.
- **Authoring Guard**: All `/lecturer/*` routes are protected by `auth` and `role:lecturer` middleware. Students receive HTTP `403 Forbidden`.
- **Gameplay XP Guard**: Puzzle gameplay submission routes (`/puzzles/{id}/submit`) are student-only (`role:student`). Lecturers cannot submit attempts or artificially inject XP into the economy.
- **Draft Guard**: Draft puzzles are blocked from students (`403 Forbidden`). Only `published` puzzles can be played.
- **Safe Preview Mode**: Lecturers can preview any puzzle via `/lecturer/puzzles/{puzzle}/preview`. Testing mode is completely sandboxed: zero XP is credited, zero attempts are logged, and responses are resolved locally.

---

## 🎨 Design System: "macOS Glassmorphism"

NetLearn enforces a bespoke desktop-grade aesthetic:
- **Base Background**: Deep dark palette (`#071426` to `#0D2138`).
- **Glassmorphism**: Semi-transparent surface fills (`rgba(13, 33, 56, 0.65)`) with `backdrop-filter: blur(20px)` and subtle borders (`rgba(255, 255, 255, 0.1)`).
- **Gamified Accents**: Cyan (`#22d3ee`) and Purple (`#a855f7`) gradients reserved strictly for active states, XP bars, and hover glows.
- **Geometry**: Squircle radii — 12px for buttons, 16px for cards, 24px for main panels.
- **Typography**: Clean hierarchy with high contrast and subtle opacity steps.
- **Spring Physics**: Smooth cubic-bezier transitions (`cubic-bezier(0.25, 1, 0.5, 1)`).

---

## 🛠️ Lecturer Authoring Guide

### 1. Creating a Mission
1. In the sidebar, navigate to **Manage Missions (PBL)** (`/lecturer/missions`).
2. Select the target **Curriculum Topic** tab.
3. Click **+ New Learning Mission** (`/lecturer/missions/create`).
4. Enter the **Title**, **Tactical Objective**, **Difficulty Level**, **Pass Mark (%)**, and **XP Reward**.
5. Save the mission.

### 2. Adding Puzzle Activities (Code-Free)
1. Open the mission's detail view (`/lecturer/missions/{id}`).
2. Click **+ Add Puzzle Activity** or click any archetype card in the empty state.
3. **Step 1 (Archetype)**: Select one of the 6 puzzle types using the visual selector.
4. **Step 2 (Metadata)**: Fill in the question text, player instruction, XP reward, and custom feedback strings.
5. **Step 3 (Dynamic Form)**: Use the dynamic builder (add/remove options, define correct answers using radio buttons or dropdowns). **No JSON knowledge is required.**
6. Click **Publish Activity**.

### 3. Testing via Sandbox Preview
- Click the **flask icon (Preview)** on any puzzle row in the mission show page.
- Play the exact Phaser simulation students see. A yellow header banner indicates sandbox mode.

---

## 🧪 Automated Test Suite

NetLearn includes a comprehensive PHPUnit / Pest feature test suite verifying all security boundaries, evaluator logic, anti-tampering guards, and UI endpoints:

```bash
php artisan test
```

### Verified Test Matrix (31 Tests, 109 Assertions)
- `public registration cannot create lecturer account` (Privilege escalation guard)
- `student cannot access lecturer missions dashboard` (403 guard)
- `lecturer cannot submit puzzle attempts to earn xp` (403 guard)
- `student cannot access puzzle authoring routes` (403 guard)
- `puzzle data endpoint does not expose correct answer` (Safe JSON)
- `matching puzzle data does not expose pair mapping` (Safe JSON)
- Evaluator scoring correctness for all 6 archetypes (Identification, Matching, Sequencing, Classification, Drag & Drop, Scenario)
- `student cannot submit fake is correct true to earn xp` (Anti-tampering server evaluator)
- `student cannot play draft puzzle` (Draft isolation)
- `lecturer can view missions index and show page`
- `lecturer can access puzzle creation form for each type`
- `lecturer can preview puzzle without logging attempts` (Sandbox safety)
- `student can view topic missions board`
- Module smoke tests and quiz management tests

---

## 💻 Local Installation & Setup

### Prerequisites
- PHP 8.1+
- Composer
- MySQL 8.0+ / MariaDB (e.g., via XAMPP)
- Apache / Nginx (or PHP built-in server)

### Setup Steps

1. **Clone and Install Dependencies**:
   ```bash
   git clone <repository-url>
   cd network_learning_laravel
   composer install
   ```

2. **Environment Configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Ensure `.env` database credentials point to your local MySQL instance:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=network_learning_laravel
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   *(Safe to run on existing databases: all seeders use idempotent `updateOrCreate`)*

4. **Start Development Server**:
   ```bash
   php artisan serve --port=8000
   ```
   Access the app at: `http://127.0.0.1:8000`

---

## 👥 User Roles for Evaluation

| Role | Default Email | Password | Primary Interface |
| :--- | :--- | :--- | :--- |
| **Lecturer** | `lecturer@netlearn.edu` | `password123` | `/lecturer/dashboard`, `/lecturer/missions` |
| **Student** | `student@netlearn.edu` | `password123` | `/student/dashboard`, `/student/topic/1` |
