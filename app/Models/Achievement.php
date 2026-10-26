<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $primaryKey = 'achievement_id';

    protected $fillable = [
        'badge_name',
        'description',
        'requirement_type',
        'requirement_value',
    ];

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id', 'achievement_id');
    }
}
