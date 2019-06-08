<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d\TH:i:sP',
        'updated_at' => 'datetime:Y-m-d\TH:i:sP',
    ];

    /**
     * Get the items for the checklist.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
