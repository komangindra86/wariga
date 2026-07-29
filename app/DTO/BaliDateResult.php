<?php

namespace App\DTO;

use JsonSerializable;

final readonly class BaliDateResult implements JsonSerializable
{
    public function __construct(
        public string $tanggalMasehi,
        public int $cycleIndex,
        public int $hariKeSiklus210,
        public CalendarElement $wuku,
        /** @var array<string, CalendarElement|null> */
        public array $wewaran,
        public string $engineVersion,
    ) {}

    /**
     * @return array{
     *     tanggal_masehi: string,
     *     cycle_index: int,
     *     hari_ke_siklus_210: int,
     *     wuku: array{kode: string, nama: string, urutan: int, urip: int|null},
     *     wewaran: array<string, array{kode: string, nama: string, urutan: int, urip: int|null}|null>,
     *     engine_version: string
     * }
     */
    public function toArray(): array
    {
        return [
            'tanggal_masehi' => $this->tanggalMasehi,
            'cycle_index' => $this->cycleIndex,
            'hari_ke_siklus_210' => $this->hariKeSiklus210,
            'wuku' => $this->wuku->toArray(),
            'wewaran' => array_map(
                fn (?CalendarElement $element): ?array => $element?->toArray(),
                $this->wewaran,
            ),
            'engine_version' => $this->engineVersion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
