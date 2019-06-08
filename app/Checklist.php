<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Checklist extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_domain',
        'object_id',
        'description',
        'due',
        'urgency',
        'created_by',
        'updated_by',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_completed',
        'completed_at'
    ];

    /**
     * Get the items for the checklist.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * get items completed
     *
     * @return boolean
     */
    public function getIsCompletedAttribute()
    {
        if ($this->items()->where('is_completed', false)->count()) {
            return false;
        }
        return true;
    }

    /**
     * get items completed at
     *
     * @return boolean
     */
    public function getCompletedAtAttribute()
    {
        if ($this->is_completed) {
            return Carbon::parse($this->items()->orderBy('completed_at', 'desc')->first()->completed_at)->format('c');
        }
        return null;
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
}
