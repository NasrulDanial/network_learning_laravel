<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function topics()
    {
        return $this->hasMany(Topic::class, 'created_by', 'user_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'user_id', 'user_id');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class, 'user_id', 'user_id');
    }

    public function puzzleAttempts()
    {
        return $this->hasMany(PuzzleAttempt::class, 'user_id', 'user_id');
    }

    public function puzzleProgress()
    {
        return $this->hasMany(UserPuzzleProgress::class, 'user_id', 'user_id');
    }

    public function missionProgress()
    {
        return $this->hasMany(UserMissionProgress::class, 'user_id', 'user_id');
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class, 'user_id', 'user_id');
    }
}