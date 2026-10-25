@extends('layouts.app')

@section('content')
    <section class="page-banner">
        <p class="eyebrow">Topic Management</p>
        <h1>Manage Learning Topics</h1>
        <p>
            Add, edit, and organize networking learning topics for the student
            learning journey.
        </p>
    </section>

    @if (session('success'))
        <div class="message success" style="margin-bottom: 1.5rem;">
            <p style="margin-bottom: 0;">
                {{ session('success') }}
            </p>
        </div>
    @endif

    <section class="management-card">
        <div class="stage-meta" style="margin-bottom: 1.5rem;">
            <div>
                <p class="eyebrow">Topic List</p>
                <h2 style="margin-bottom: 0;">Available Topics</h2>
            </div>

            <a class="button" href="{{ route('lecturer.topics.create') }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Topic
            </a>
        </div>

        @if ($topics->count() > 0)
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Topic</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($topics as $topic)
                            <tr>
                                <td>
                                    Stage {{ (int) $topic->topic_order }}
                                </td>

                                <td>
                                    <strong>{{ $topic->title }}</strong>
                                </td>

                                <td>
                                    {{ $topic->description ?? 'No description provided.' }}
                                </td>

                                <td>
                                    User ID: {{ $topic->created_by }}
                                </td>

                                <td>
                                    <div class="actions">
                                        <a
                                            class="button secondary"
                                            href="{{ route('lecturer.topics.edit', $topic->topic_id) }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('lecturer.topics.destroy', $topic->topic_id) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this topic? Related quizzes and progress may also be deleted.');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="danger-button">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-book-open"></i>
                </div>

                <h2>No topics available</h2>

                <p>
                    Create the first networking topic to start building the student
                    learning journey.
                </p>

                <a class="button" href="{{ route('lecturer.topics.create') }}">
                    Add First Topic
                </a>
            </section>
        @endif
    </section>
@endsection