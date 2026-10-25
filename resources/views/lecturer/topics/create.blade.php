@extends('layouts.app')

@section('content')
    <a class="breadcrumb-link" href="{{ route('lecturer.topics.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to Manage Topics
    </a>

    <section class="page-banner">
        <p class="eyebrow">Create Topic</p>
        <h1>Add New Learning Topic</h1>
        <p>
            Create a new networking topic for the student learning path.
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
        <form method="POST" action="{{ route('lecturer.topics.store') }}">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Topic Title</label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="topic_order">Topic Order</label>
                    <input
                        id="topic_order"
                        type="number"
                        name="topic_order"
                        min="1"
                        value="{{ old('topic_order', 1) }}"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="description">Short Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                >{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label for="content">Learning Content</label>
                <textarea
                    id="content"
                    name="content"
                    rows="12"
                    required
                >{{ old('content') }}</textarea>
            </div>

            <div class="actions">
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Save Topic
                </button>

                <a class="button secondary" href="{{ route('lecturer.topics.index') }}">
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection