<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Item extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'task_id',
        'due',
        'urgency',
        'created_by',
        'updated_by',
        'is_completed',
        'completed_at',
        'assignee_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Get the checklist for the item.
     */
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('c');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('c');
    }

    public function getDueAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('c') : null;
    }

    public function getCompletedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('c') : null;
    }
}
