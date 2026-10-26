<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puzzle extends Model
{
    protected $primaryKey = 'puzzle_id';

    protected $fillable = [
        'topic_id',
        'puzzle_type',
        'question_text',
        'instruction',
        'correct_answer',
        'hint',
        'explanation',
        'points',
    ];

    protected $casts = [
        'correct_answer' => 'array',
    ];

    /**
     * The topic this puzzle belongs to.
     * Used by PuzzleController to look up the correct Progress row for XP.
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    /**
     * All attempts recorded for this puzzle.
     */
    public function attempts()
    {
        return $this->hasMany(PuzzleAttempt::class, 'puzzle_id', 'puzzle_id');
    }
}
