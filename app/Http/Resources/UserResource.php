<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'role'               => $this->role,
            'is_active'          => $this->is_active,
            'email_verified_at'  => $this->email_verified_at?->toISOString(),
            'created_at'         => $this->created_at->toISOString(),

            // Only include addresses if they were eager-loaded
            // $user->load('addresses') must be called first
            'addresses'          => AddressResource::collection(
                $this->whenLoaded('addresses')
            ),
        ];
    }
}