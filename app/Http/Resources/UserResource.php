<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    private ?string $accessToken = null;

    public function withToken(string $token): static
    {
        $this->accessToken = $token;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'token' => $this->when(
                $this->accessToken !== null,
                fn () => $this->accessToken,
            ),
        ];
    }
}
