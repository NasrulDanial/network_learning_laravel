<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPuzzleProgress extends Model
{
    protected $table = 'user_puzzle_progress';
    protected $primaryKey = 'user_puzzle_progress_id';

    protected $fillable = [
        'user_id',
        'puzzle_id',
        'best_score',
        'attempts_count',
        'try_attempts',
        'try_hint_used',
        'is_completed',
        'xp_awarded',
        'hints_used',
        'best_stars',
        'first_completed_at',
        'last_attempt_at',
    ];

    protected $casts = [
        'hints_used' => 'integer',
        'best_stars' => 'integer',
        'best_score' => 'integer',
        'attempts_count' => 'integer',
        'try_attempts' => 'integer',
        'try_hint_used' => 'boolean',
        'is_completed' => 'boolean',
        'xp_awarded' => 'integer',
        'first_completed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function puzzle()
    {
        return $this->belongsTo(Puzzle::class, 'puzzle_id', 'puzzle_id');
    }
}
