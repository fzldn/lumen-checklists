<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'checklists',
            'id' => $this->id,
            'attributes' => collect([
                'created_at',
                'created_by',
                'object_domain',
                'object_id',
                'description',
                'due',
                'urgency',
                'is_completed',
                'completed_at',
                'updated_at',
                'updated_by',
            ])
                ->mapWithKeys(function ($value) {
                    return [$value => $this->$value];
                })
                ->merge([
                    'items' => $this->whenLoaded('items')
                ])
                ->all(),
            'links' => [
                'self' => url("/checklists/{$this->id}"),
            ]
        ];
    }
}
