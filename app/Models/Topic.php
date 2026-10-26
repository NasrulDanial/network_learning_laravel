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
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'topic_id', 'topic_id');
    }

    public function puzzles()
    {
        return $this->hasMany(Puzzle::class, 'topic_id', 'topic_id');
    }

    public function puzzle()
    {
        return $this->hasOne(Puzzle::class, 'topic_id', 'topic_id');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class, 'topic_id', 'topic_id');
    }
}