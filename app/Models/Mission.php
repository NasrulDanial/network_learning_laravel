<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $primaryKey = 'mission_id';

    protected $fillable = [
        'topic_id',
        'title',
        'description',
        'objective',
        'difficulty',
        'mission_order',
        'pass_mark',
        'xp_reward',
        'status',
    ];

    protected $casts = [
        'pass_mark' => 'integer',
        'xp_reward' => 'integer',
        'mission_order' => 'integer',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    public function puzzles()
    {
        return $this->hasMany(Puzzle::class, 'mission_id', 'mission_id')
            ->orderBy('activity_order', 'asc');
    }

    public function publishedPuzzles()
    {
        return $this->hasMany(Puzzle::class, 'mission_id', 'mission_id')
            ->where('status', 'published')
            ->orderBy('activity_order', 'asc');
    }

    public function userProgress()
    {
        return $this->hasMany(UserMissionProgress::class, 'mission_id', 'mission_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Missions students can actually play: published and holding at least one published activity.
     * Empty missions are skipped for display, unlocking and progress so they never block a topic.
     */
    public function scopePlayable($query)
    {
        return $query->where('missions.status', 'published')
            ->whereHas('puzzles', function ($q) {
                $q->where('status', 'published');
            });
    }

    /**
     * The first published mission of a topic is always open; later ones open once the
     * previous published mission is completed (or were explicitly unlocked earlier).
     * Checking the previous mission keeps newly inserted missions reachable.
     */
    public function isUnlockedFor(int $userId): bool
    {
        $previousMission = static::playable()
            ->where('topic_id', $this->topic_id)
            ->where('mission_order', '<', $this->mission_order)
            ->orderByDesc('mission_order')
            ->first();

        if (! $previousMission) {
            return true;
        }

        return UserMissionProgress::where('user_id', $userId)
            ->where(function ($q) use ($previousMission) {
                $q->where(function ($own) {
                    $own->where('mission_id', $this->mission_id)->where('is_unlocked', true);
                })->orWhere(function ($prev) use ($previousMission) {
                    $prev->where('mission_id', $previousMission->mission_id)->where('is_completed', true);
                });
            })
            ->exists();
    }
}
