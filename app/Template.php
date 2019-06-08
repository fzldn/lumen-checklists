<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Template extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the checklist template record associated with the template.
     */
    public function checklist()
    {
        return $this->hasOne(TemplateChecklist::class);
    }

    /**
     * Get the item templates record associated with the template.
     */
    public function items()
    {
        return $this->hasMany(TemplateItem::class);
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
