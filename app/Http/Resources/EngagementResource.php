<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EngagementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'message' => $this->message,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sent_at' => $this->sent_at,
            'triggered_by' => $this->whenLoaded('triggeredBy', fn () => $this->triggeredBy?->name),
        ];
    }
}
