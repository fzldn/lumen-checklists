<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'templates',
            'id' => $this->id,
            'name' => $this->name,
            'checklist' => $this->whenLoaded('checklist'),
            'items' => $this->whenLoaded('items'),
            'links' => [
                'self' => url("/checklists/templates/{$this->id}"),
            ]
        ];
    }
}
