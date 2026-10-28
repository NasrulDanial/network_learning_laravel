<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\LecturerDashboardController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentPerformanceController;
use App\Http\Controllers\PuzzleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Lecturer\MissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->role === 'student') {
            return redirect()->route('student.dashboard');
        }

        if (auth()->user()->role === 'lecturer') {
            return redirect()->route('lecturer.dashboard');
        }
    }

    return view('landing');
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/register', [AuthController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])
        ->name('student.dashboard');

    Route::get('/student/learning', [StudentController::class, 'learning'])
        ->name('student.learning');

    Route::get('/student/topic/{topic}', [StudentController::class, 'topic'])
        ->name('student.topic');

    Route::get('/student/quiz', [StudentController::class, 'quizHub'])
        ->name('student.quiz');

    Route::get('/student/quiz/{quiz}', [StudentController::class, 'quiz'])
        ->name('student.quiz.take');

    Route::post('/student/quiz/submit', [StudentController::class, 'submitQuiz'])
        ->name('student.quiz.submit');

    // Instant per-question check; the answer is locked in the session once checked
    Route::post('/student/quiz/{quiz}/check', [StudentController::class, 'checkQuizAnswer'])
        ->middleware('throttle:60,1')
        ->name('student.quiz.check');

    Route::get('/student/result/{result}', [StudentController::class, 'result'])
        ->name('student.result');

    Route::get('/student/progress', [StudentController::class, 'progress'])
        ->name('student.progress');

    Route::get('/student/achievements', [StudentController::class, 'achievements'])
        ->name('student.achievements');

    // Mission Run (continuous play) and its debrief data
    Route::get('/student/missions/{mission}/run', [StudentController::class, 'missionRun'])
        ->name('student.mission.run');

    Route::get('/student/missions/{mission}/summary', [StudentController::class, 'missionSummary'])
        ->name('student.mission.summary');

    Route::get('/student/profile', [ProfileController::class, 'show'])
        ->name('student.profile');

    Route::put('/student/profile', [ProfileController::class, 'update'])
        ->name('student.profile.update');

    Route::put('/student/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('student.profile.password');
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/lecturer/dashboard', [LecturerDashboardController::class, 'index'])
        ->name('lecturer.dashboard');

    Route::get('/lecturer/topics', [TopicController::class, 'index'])
        ->name('lecturer.topics.index');

    Route::get('/lecturer/topics/create', [TopicController::class, 'create'])
        ->name('lecturer.topics.create');

    Route::post('/lecturer/topics', [TopicController::class, 'store'])
        ->name('lecturer.topics.store');

    Route::get('/lecturer/topics/{topic}/edit', [TopicController::class, 'edit'])
        ->name('lecturer.topics.edit');

    Route::put('/lecturer/topics/{topic}', [TopicController::class, 'update'])
        ->name('lecturer.topics.update');

    Route::delete('/lecturer/topics/{topic}', [TopicController::class, 'destroy'])
        ->name('lecturer.topics.destroy');

    Route::get('/lecturer/quizzes', [QuizController::class, 'index'])
        ->name('lecturer.quizzes.index');

    Route::get('/lecturer/quizzes/create', [QuizController::class, 'create'])
        ->name('lecturer.quizzes.create');

    Route::post('/lecturer/quizzes', [QuizController::class, 'store'])
        ->name('lecturer.quizzes.store');

    Route::get('/lecturer/quizzes/{quiz}/edit', [QuizController::class, 'edit'])
        ->name('lecturer.quizzes.edit');

    Route::put('/lecturer/quizzes/{quiz}', [QuizController::class, 'update'])
        ->name('lecturer.quizzes.update');

    Route::delete('/lecturer/quizzes/{quiz}', [QuizController::class, 'destroy'])
        ->name('lecturer.quizzes.destroy');

    Route::get('/lecturer/quizzes/{quiz}/questions', [QuestionController::class, 'index'])
        ->name('lecturer.questions.index');

    Route::get('/lecturer/quizzes/{quiz}/questions/create', [QuestionController::class, 'create'])
        ->name('lecturer.questions.create');

    Route::post('/lecturer/quizzes/{quiz}/questions', [QuestionController::class, 'store'])
        ->name('lecturer.questions.store');

    Route::get('/lecturer/quizzes/{quiz}/questions/{question}/edit', [QuestionController::class, 'edit'])
        ->name('lecturer.questions.edit');

    Route::put('/lecturer/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'update'])
        ->name('lecturer.questions.update');

    Route::delete('/lecturer/quizzes/{quiz}/questions/{question}', [QuestionController::class, 'destroy'])
        ->name('lecturer.questions.destroy');

    Route::get('/lecturer/student-performance', [StudentPerformanceController::class, 'index'])
        ->name('lecturer.student-performance.index');

    // Mission & Puzzle Activity Management (PBL Core)
    Route::get('/lecturer/missions', [MissionController::class, 'index'])
        ->name('lecturer.missions.index');
    Route::get('/lecturer/topics/{topic}/missions', [MissionController::class, 'index'])
        ->name('lecturer.topics.missions');
    Route::get('/lecturer/missions/create', [MissionController::class, 'create'])
        ->name('lecturer.missions.create');
    Route::get('/lecturer/topics/{topic}/missions/create', [MissionController::class, 'create'])
        ->name('lecturer.topics.missions.create');
    Route::post('/lecturer/missions', [MissionController::class, 'store'])
        ->name('lecturer.missions.store');
    Route::get('/lecturer/missions/{mission}', [MissionController::class, 'show'])
        ->name('lecturer.missions.show');
    Route::get('/lecturer/missions/{mission}/edit', [MissionController::class, 'edit'])
        ->name('lecturer.missions.edit');
    Route::put('/lecturer/missions/{mission}', [MissionController::class, 'update'])
        ->name('lecturer.missions.update');
    Route::delete('/lecturer/missions/{mission}', [MissionController::class, 'destroy'])
        ->name('lecturer.missions.destroy');

    // Puzzle Authoring within a Mission
    Route::get('/lecturer/missions/{mission}/puzzles/create', [MissionController::class, 'createPuzzle'])
        ->name('lecturer.missions.puzzles.create');
    Route::post('/lecturer/missions/{mission}/puzzles', [MissionController::class, 'storePuzzle'])
        ->name('lecturer.missions.puzzles.store');
    Route::get('/lecturer/puzzles/{puzzle}/edit', [MissionController::class, 'editPuzzle'])
        ->name('lecturer.missions.puzzles.edit');
    Route::put('/lecturer/puzzles/{puzzle}', [MissionController::class, 'updatePuzzle'])
        ->name('lecturer.missions.puzzles.update');
    Route::delete('/lecturer/puzzles/{puzzle}', [MissionController::class, 'destroyPuzzle'])
        ->name('lecturer.missions.puzzles.destroy');
    Route::post('/lecturer/puzzles/{puzzle}/toggle-status', [MissionController::class, 'togglePuzzleStatus'])
        ->name('lecturer.missions.puzzles.toggle');
    Route::get('/lecturer/puzzles/{puzzle}/preview', [MissionController::class, 'previewPuzzle'])
        ->name('lecturer.missions.puzzles.preview');
    Route::post('/lecturer/puzzles/{puzzle}/preview-submit', [MissionController::class, 'evaluatePreview'])
        ->name('lecturer.missions.puzzles.preview.submit');

    // Topic Reorder & Status Toggle
    Route::post('/lecturer/topics/reorder', [TopicController::class, 'reorder'])
        ->name('lecturer.topics.reorder');
    Route::post('/lecturer/topics/{topic}/toggle-status', [TopicController::class, 'toggleStatus'])
        ->name('lecturer.topics.toggle');

    // Student Performance Detail Dossier
    Route::get('/lecturer/student-performance/{user}', [StudentPerformanceController::class, 'show'])
        ->name('lecturer.student-performance.show');

    // Instructor Profile & Security
    Route::get('/lecturer/profile', [ProfileController::class, 'show'])
        ->name('lecturer.profile');
    Route::put('/lecturer/profile', [ProfileController::class, 'update'])
        ->name('lecturer.profile.update');
    Route::put('/lecturer/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('lecturer.profile.password');
});

// Puzzle routes are student-only — prevents lecturers from awarding themselves XP
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/puzzles/{id}', [PuzzleController::class, 'play'])
        ->name('puzzles.show');

    Route::get('/puzzles/{id}/data', [PuzzleController::class, 'show'])
        ->name('puzzles.data');

    Route::post('/puzzles/{id}/submit', [PuzzleController::class, 'submitAttempt'])
        ->name('puzzles.submit');

    Route::post('/puzzles/{id}/hint', [PuzzleController::class, 'revealHint'])
        ->name('puzzles.hint');
});