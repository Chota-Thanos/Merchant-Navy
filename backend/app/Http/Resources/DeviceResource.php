<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A Sanctum token shown as a logged-in device. Matches `Device` in student-app/src/types/index.ts */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastActiveAt' => ($this->last_used_at ?? $this->created_at)->toIso8601String(),
            'isCurrent' => $request->user()->currentAccessToken()->id === $this->id,
        ];
    }
}
