<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'title',
        'description',
        'content',
        'created_by',
        'topic_order',
        'cover_image',
        'objectives',
        'difficulty',
        'status',
        'estimated_time_minutes',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function missions()
    {
        return $this->hasMany(Mission::class, 'topic_id', 'topic_id')->orderBy('mission_order', 'asc');
    }

    public function publishedMissions()
    {
        return $this->hasMany(Mission::class, 'topic_id', 'topic_id')
            ->where('status', 'published')
            ->orderBy('mission_order', 'asc');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'topic_id', 'topic_id');
    }

    public function puzzles()
    {
        return $this->hasMany(Puzzle::class, 'topic_id', 'topic_id');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class, 'topic_id', 'topic_id');
    }

    /**
     * Topics students may see (legacy rows without a status count as published).
     */
    public function scopeVisibleToStudents($query)
    {
        return $query->where(function ($q) {
            $q->where('topics.status', 'published')
                ->orWhereNull('topics.status');
        });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === null || $this->status === 'published';
    }

    /**
     * A visible topic is unlocked when it is the first visible topic, or when the
     * preceding visible topic has been completed by the student. Draft topics are
     * skipped so hiding a topic never strands students behind it.
     */
    public function isUnlockedFor(int $userId): bool
    {
        if (! $this->isVisibleToStudents()) {
            return false;
        }

        $previousTopic = static::visibleToStudents()
            ->where('topic_order', '<', $this->topic_order)
            ->orderByDesc('topic_order')
            ->first();

        if (! $previousTopic) {
            return true;
        }

        return Progress::where('user_id', $userId)
            ->where('topic_id', $previousTopic->topic_id)
            ->where('learning_status', 'Completed')
            ->exists();
    }

    /** Playable missions of this topic the student has cleared: ['done' => n, 'total' => n]. */
    public function missionsClearedBy(int $userId): array
    {
        $ids = Mission::playable()->where('topic_id', $this->topic_id)->pluck('mission_id');
        $done = $ids->isEmpty() ? 0 : UserMissionProgress::where('user_id', $userId)
            ->whereIn('mission_id', $ids)
            ->where('is_completed', true)
            ->count();

        return ['done' => $done, 'total' => $ids->count()];
    }

    /** The topic quiz opens once the topic is unlocked and all of its missions are cleared. */
    public function assessmentUnlockedFor(int $userId): bool
    {
        if (! $this->isUnlockedFor($userId)) {
            return false;
        }
        $missions = $this->missionsClearedBy($userId);

        return $missions['done'] === $missions['total']; // a topic without missions is open
    }

    /**
     * Study notes split into sections on ALL-CAPS heading lines. Text before the first heading
     * becomes "Introduction". Bodies are plain text; views escape them.
     *
     * @return array<int, array{id: string, title: string, body: string}>
     */
    public function noteSections(): array
    {
        $sections = [];
        $title = null;
        $body = [];
        $flush = function () use (&$sections, &$title, &$body) {
            $text = trim(implode("
", $body));
            if ($title !== null || $text !== '') {
                $name = $title ?? 'Introduction';
                $sections[] = [
                    'id' => 'note-' . (count($sections) + 1) . '-' . \Illuminate\Support\Str::slug($name),
                    'title' => $name, // kept as written: title-casing would mangle acronyms (OSI, TCP/IP)
                    'body' => $text,
                ];
            }
            $body = [];
        };

        foreach (preg_split('/\R/u', (string) $this->content) as $line) {
            $trimmed = trim($line);
            $isHeading = mb_strlen($trimmed) >= 3 && mb_strlen($trimmed) <= 80
                && preg_match('/\p{L}/u', $trimmed)
                && mb_strtoupper($trimmed) === $trimmed;
            if ($isHeading) {
                $flush();
                $title = rtrim($trimmed, ':');
            } else {
                $body[] = $line;
            }
        }
        $flush();

        return array_values(array_filter($sections, fn ($s) => $s['body'] !== '' || $s['title'] !== 'Introduction'));
    }
}
