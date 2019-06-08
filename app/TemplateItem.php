<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TemplateItem extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'due_interval',
        'due_unit',
        'urgency',
        'assignee_id',
        'task_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the template that owns the item template.
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('c');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('c');
    }
}
