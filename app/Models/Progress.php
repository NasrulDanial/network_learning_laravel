<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    protected $table = 'progress';

    protected $primaryKey = 'progress_id';

    protected $fillable = [
        'user_id',
        'topic_id',
        'learning_status',
        'quiz_status',
        'progress_percentage',
        'completed_missions',
        'total_xp',
        'current_level',
        'current_rank',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }
}