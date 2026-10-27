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
}