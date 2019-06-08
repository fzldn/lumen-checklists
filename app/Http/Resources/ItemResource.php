<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'items',
            'id' => $this->id,
            'attributes' => collect([
                'created_at',
                'created_by',
                'description',
                'due',
                'urgency',
                'is_completed',
                'completed_at',
                'assignee_id',
                'task_id',
                'updated_at',
                'updated_by',
            ])
                ->mapWithKeys(function ($value) {
                    return [$value => $this->$value];
                })
                ->all(),
            'links' => [
                'self' => url("/checklists/{$this->checklist_id}/items/{$this->id}"),
            ]
        ];
    }
}
