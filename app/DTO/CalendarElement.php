<?php

namespace App\DTO;

use JsonSerializable;

final readonly class CalendarElement implements JsonSerializable
{
    public function __construct(
        public string $kode,
        public string $nama,
        public int $urutan,
        public ?int $urip,
    ) {}

    /**
     * @return array{kode: string, nama: string, urutan: int, urip: int|null}
     */
    public function toArray(): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'urutan' => $this->urutan,
            'urip' => $this->urip,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
