<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puzzle extends Model
{
    protected $primaryKey = 'puzzle_id';

    protected $fillable = [
        'mission_id',
        'topic_id',
        'puzzle_type',
        'question_text',
        'instruction',
        'activity_data',
        'correct_answer',
        'hint',
        'correct_feedback',
        'incorrect_feedback',
        'explanation',
        'points',
        'activity_order',
        'difficulty',
        'max_attempts',
        'is_required',
        'status',
    ];

    protected $casts = [
        'activity_data' => 'array',
        'correct_answer' => 'array',
        'points' => 'integer',
        'activity_order' => 'integer',
        'is_required' => 'boolean',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class, 'mission_id', 'mission_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    public function attempts()
    {
        return $this->hasMany(PuzzleAttempt::class, 'puzzle_id', 'puzzle_id');
    }

    public function userProgress()
    {
        return $this->hasMany(UserPuzzleProgress::class, 'puzzle_id', 'puzzle_id');
    }

    /**
     * Return student-safe activity payload without revealing answer keys.
     */
    public function getSafeActivityData(): array
    {
        $base = [
            'puzzle_id'     => $this->puzzle_id,
            'mission_id'    => $this->mission_id,
            'topic_id'      => $this->topic_id,
            'puzzle_type'   => $this->puzzle_type,
            'question_text' => $this->question_text,
            'instruction'   => $this->instruction,
            'hint'          => $this->hint,
            'points'        => $this->points,
            'activity_order'=> $this->activity_order,
            'difficulty'    => $this->difficulty,
        ];

        // Specific safe presentation contracts per puzzle type
        $data = $this->activity_data ?? [];

        switch ($this->puzzle_type) {
            case 'identification':
                $base['options'] = $data['options'] ?? [];
                $base['image_url'] = $data['image_url'] ?? null;
                $base['prompt'] = $data['prompt'] ?? $this->question_text;
                break;

            case 'matching':
                $left = $data['left_items'] ?? [];
                $right = $data['right_items'] ?? [];
                // Client-side shuffle right items for educational challenge
                shuffle($right);
                $base['left_items'] = $left;
                $base['right_items'] = $right;
                break;

            case 'sequencing':
                $items = $data['items'] ?? [];
                // Shuffle items so student must sequence them
                shuffle($items);
                $base['items'] = $items;
                break;

            case 'classification':
                $base['categories'] = $data['categories'] ?? [];
                $items = $data['items'] ?? [];
                shuffle($items);
                $base['items'] = $items;
                break;

            case 'drag_and_drop':
                $base['template_key'] = $data['template_key'] ?? 'device_to_osi_layer';
                $base['draggables'] = $data['draggables'] ?? ($data['nodes'] ?? []);
                $base['drop_zones'] = $data['drop_zones'] ?? [];
                $base['links'] = $data['links'] ?? [];
                break;

            case 'scenario':
                $base['scenario_text'] = $data['scenario_text'] ?? $this->instruction;
                $base['choices'] = $data['choices'] ?? [];
                break;
        }

        return $base;
    }
}
