<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarigaActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->kode,
            'name' => $this->nama,
            'slug' => $this->slug,
            'category' => $this->kategori,
            'description' => $this->deskripsi,
            'disclaimer' => $this->disclaimer,
            'icon' => $this->icon,
        ];
    }
}
