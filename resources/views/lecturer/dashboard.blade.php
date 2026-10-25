@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Lecturer Control Centre</p>

            <h1>Welcome, {{ auth()->user()->name }}!</h1>

            <p>
                Monitor student progress, review quiz performance,
                and manage networking learning activities.
            </p>
        </div>

        <div class="hero-symbol" aria-hidden="true">
            <i class="fa-solid fa-chart-column"></i>
        </div>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">System Overview</p>
        <h2 style="margin-bottom: 1.5rem;">Learning Analytics Summary</h2>

        <div class="summary-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                    Total Students
                </span>

                <strong class="stat-value">
                    {{ (int) $totalStudents }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    Total Topics
                </span>

                <strong class="stat-value">
                    {{ (int) $totalTopics }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    Quiz Attempts
                </span>

                <strong class="stat-value">
                    {{ (int) $totalAttempts }}
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    Average Score
                </span>

                <strong class="stat-value">
                    {{ number_format((float) $averageScore, 0) }}%
                </strong>
            </div>
        </div>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Performance Charts</p>
        <h2 style="margin-bottom: 1.5rem;">Quiz Result Analytics</h2>

        <div class="dashboard-grid">
            <article class="management-card">
                <div class="stage-meta" style="margin-bottom: 1rem;">
                    <span class="eyebrow">Pass / Fail Distribution</span>
                </div>

                <canvas id="passFailChart" height="220"></canvas>
            </article>

            <article class="management-card">
                <div class="stage-meta" style="margin-bottom: 1rem;">
                    <span class="eyebrow">Average Score by Topic</span>
                </div>

                <canvas id="topicPerformanceChart" height="220"></canvas>
            </article>
        </div>
    </section>

    <section style="margin-bottom: 2rem;">
        <p class="eyebrow">Management Menu</p>
        <h2 style="margin-bottom: 1.5rem;">Lecturer Actions</h2>

        <nav class="dashboard-grid">
            <a class="card" href="{{ route('lecturer.topics.index') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-book-open"></i>
                </span>

                <h2>Manage Topics</h2>
                <p>
                    Add, edit, and organize networking learning topics.
                </p>
            </a>

            <a class="card" href="{{ route('lecturer.quizzes.index') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </span>

                <h2>Manage Quizzes</h2>
                <p>
                    Create quizzes, set passing marks, and manage challenges.
                </p>
            </a>

            <a class="card" href="{{ route('lecturer.student-performance.index') }}">
                <span class="card-icon" aria-hidden="true">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </span>

                <h2>Student Performance</h2>
                <p>
                    Review individual student scores and learning progress.
                </p>
            </a>
        </nav>
    </section>

    <section>
        <p class="eyebrow">Recent Activity</p>
        <h2 style="margin-bottom: 1.5rem;">Latest Quiz Attempts</h2>

        @if ($recentResults->count() > 0)
            <div class="management-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Topic</th>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Date Taken</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($recentResults as $result)
                                <tr>
                                    <td>
                                        <strong>{{ $result->student_name }}</strong>
                                        <br>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">
                                            {{ $result->student_email }}
                                        </span>
                                    </td>

                                    <td>{{ $result->topic_title }}</td>

                                    <td>{{ $result->quiz_title }}</td>

                                    <td>
                                        <strong>
                                            {{ (int) $result->score }}/{{ (int) $result->total_questions }}
                                        </strong>
                                        <br>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">
                                            {{ number_format((float) $result->percentage, 0) }}%
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status-badge {{ $result->status === 'Pass' ? 'status-pass' : 'status-fail' }}">
                                            {{ $result->status }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $result->date_taken }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <section class="empty-state">
                <div class="empty-state-icon" aria-hidden="true">
                    <i class="fa-solid fa-chart-column"></i>
                </div>

                <h2>No quiz attempts yet</h2>

                <p>
                    Student quiz results will appear here after students complete quiz challenges.
                </p>
            </section>
        @endif
    </section>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const passFailLabels = @json(array_keys($passFailData));
        const passFailValues = @json(array_values($passFailData));

        const topicLabels = @json($topicPerformance->pluck('topic_title'));
        const topicAverages = @json($topicPerformance->pluck('average_percentage')->map(fn ($value) => round($value, 2)));

        const passFailCanvas = document.getElementById('passFailChart');

        if (passFailCanvas) {
            new Chart(passFailCanvas, {
                type: 'doughnut',
                data: {
                    labels: passFailLabels,
                    datasets: [{
                        data: passFailValues
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        const topicPerformanceCanvas = document.getElementById('topicPerformanceChart');

        if (topicPerformanceCanvas) {
            new Chart(topicPerformanceCanvas, {
                type: 'bar',
                data: {
                    labels: topicLabels,
                    datasets: [{
                        label: 'Average Score (%)',
                        data: topicAverages
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
@endsection