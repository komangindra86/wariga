<?php

namespace App\DTO;

use JsonSerializable;

final readonly class LunarDateResult implements JsonSerializable
{
    public function __construct(
        public bool $available,
        public string $verificationStatus,
        public string $tanggalMasehi,
        public ?array $sasih = null,
        public ?string $fase = null,
        public ?int $nomorHari = null,
        public bool $isPurnama = false,
        public bool $isTilem = false,
        public bool $isNampihSasih = false,
        public ?string $sumber = null,
        public ?string $referensi = null,
        public ?string $catatan = null,
        public ?string $message = null,
    ) {}

    public static function unavailable(string $date): self
    {
        return new self(
            available: false,
            verificationStatus: 'unavailable',
            tanggalMasehi: $date,
            message: 'Data sasih dan fase bulan belum tersedia.',
        );
    }

    public function toArray(): array
    {
        if (! $this->available) {
            return [
                'available' => false,
                'verification_status' => 'unavailable',
                'message' => $this->message,
            ];
        }

        return [
            'available' => true,
            'tanggal_masehi' => $this->tanggalMasehi,
            'sasih' => $this->sasih,
            'fase' => $this->fase,
            'nomor_hari' => $this->nomorHari,
            'is_purnama' => $this->isPurnama,
            'is_tilem' => $this->isTilem,
            'is_nampih_sasih' => $this->isNampihSasih,
            'sumber' => $this->sumber,
            'referensi' => $this->referensi,
            'catatan' => $this->catatan,
            'verification_status' => $this->verificationStatus,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
