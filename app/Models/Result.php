<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $primaryKey = 'result_id';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_questions',
        'percentage',
        'status',
        'date_taken',
        'points_earned',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'quiz_id');
    }
}