<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuzzleAttempt extends Model
{
    protected $primaryKey = 'attempt_id';

    protected $fillable = [
        'user_id',
        'puzzle_id',
        'score',
        'status',
        'attempt_no',
        'date_taken',
    ];

    protected $casts = [
        'date_taken' => 'datetime',
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
