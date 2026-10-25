<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\LecturerDashboardController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentPerformanceController;
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

    Route::get('/student/result/{result}', [StudentController::class, 'result'])
        ->name('student.result');

    Route::get('/student/progress', [StudentController::class, 'progress'])
        ->name('student.progress');
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
});