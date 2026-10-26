<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $primaryKey = 'quiz_id';

    protected $fillable = [
        'topic_id',
        'title',
        'passing_mark',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    public function puzzle()
    {
        return $this->hasOne(Puzzle::class, 'topic_id', 'topic_id');
    }

    public function getTopicTitleAttribute()
    {
        return $this->topic ? $this->topic->title : '';
    }

    public function getTotalQuestionsAttribute()
    {
        return $this->questions_count ?? $this->questions()->count();
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'quiz_id', 'quiz_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'quiz_id', 'quiz_id');
    }
}