<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMissionProgress extends Model
{
    protected $table = 'user_mission_progress';
    protected $primaryKey = 'user_mission_progress_id';

    protected $fillable = [
        'user_id',
        'mission_id',
        'is_unlocked',
        'is_completed',
        'best_score_percentage',
        'best_stars',
        'xp_reward_granted',
        'completed_at',
    ];

    protected $casts = [
        'best_stars' => 'integer',
        'is_unlocked' => 'boolean',
        'is_completed' => 'boolean',
        'best_score_percentage' => 'integer',
        'xp_reward_granted' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class, 'mission_id', 'mission_id');
    }
}
