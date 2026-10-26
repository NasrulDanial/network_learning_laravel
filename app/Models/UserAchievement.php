<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'achievement_id',
        'date_earned',
    ];

    protected $casts = [
        'date_earned' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'achievement_id');
    }
}
