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
}
