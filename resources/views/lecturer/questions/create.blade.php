@extends('layouts.app')

@section('content')
    <a
        class="breadcrumb-link"
        href="{{ route('lecturer.questions.index', $quiz->quiz_id) }}"
    >
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Manage Questions
    </a>

    <section class="page-banner">
        <p class="eyebrow">Create Question</p>
        <h1>Add New Question</h1>
        <p>
            Add a question for
            <strong style="color: var(--cyan);">
                {{ $quiz->title }}
            </strong>
        </p>
    </section>

    @if ($errors->any())
        <div class="message error" style="margin-bottom: 1.5rem;">
            @foreach ($errors->all() as $error)
                <p style="margin-bottom: 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="management-card">
        <form
            method="POST"
            action="{{ route('lecturer.questions.store', $quiz->quiz_id) }}"
        >
            @csrf

            <div class="form-group">
                <label for="question_text">Question Text</label>
                <textarea
                    id="question_text"
                    name="question_text"
                    rows="4"
                    required
                >{{ old('question_text') }}</textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="option_a">Option A</label>
                    <input
                        id="option_a"
                        type="text"
                        name="option_a"
                        value="{{ old('option_a') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="option_b">Option B</label>
                    <input
                        id="option_b"
                        type="text"
                        name="option_b"
                        value="{{ old('option_b') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="option_c">Option C</label>
                    <input
                        id="option_c"
                        type="text"
                        name="option_c"
                        value="{{ old('option_c') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="option_d">Option D</label>
                    <input
                        id="option_d"
                        type="text"
                        name="option_d"
                        value="{{ old('option_d') }}"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="correct_answer">Correct Answer</label>
                <select id="correct_answer" name="correct_answer" required>
                    <option value="">Select correct answer</option>
                    <option value="A" {{ old('correct_answer') === 'A' ? 'selected' : '' }}>A</option>
                    <option value="B" {{ old('correct_answer') === 'B' ? 'selected' : '' }}>B</option>
                    <option value="C" {{ old('correct_answer') === 'C' ? 'selected' : '' }}>C</option>
                    <option value="D" {{ old('correct_answer') === 'D' ? 'selected' : '' }}>D</option>
                </select>
            </div>

            <div class="actions">
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Save Question
                </button>

                <a
                    class="button secondary"
                    href="{{ route('lecturer.questions.index', $quiz->quiz_id) }}"
                >
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection