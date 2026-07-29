<?php

namespace App\DTO;

use JsonSerializable;

final readonly class BaliDateResult implements JsonSerializable
{
    public function __construct(
        public string $tanggalMasehi,
        public int $cycleIndex,
        public int $hariKeSiklus210,
        public string $engineVersion,
    ) {}

    /**
     * @return array{
     *     tanggal_masehi: string,
     *     cycle_index: int,
     *     hari_ke_siklus_210: int,
     *     engine_version: string
     * }
     */
    public function toArray(): array
    {
        return [
            'tanggal_masehi' => $this->tanggalMasehi,
            'cycle_index' => $this->cycleIndex,
            'hari_ke_siklus_210' => $this->hariKeSiklus210,
            'engine_version' => $this->engineVersion,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
