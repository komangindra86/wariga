<?php

namespace App\Services\Lunar;

use App\DTO\LunarDateResult;
use Carbon\Carbon;

final class PengalantakaLunarCalculator
{
    private const ANCHOR_DATE = '2026-02-02';
    private const ANCHOR_SERIAL = 15; // Purnama = Penanggal 15
    private const ANCHOR_SASIH_INDEX = 7; // Kawolu, zero based
    private const FIRST_PENGUNALATRI = '2026-02-03';
    private const PENGUNALATRI_INTERVAL = 63;

    private const SASIH = [
        ['kode' => 'kasa', 'nama' => 'Kasa'],
        ['kode' => 'karo', 'nama' => 'Karo'],
        ['kode' => 'katiga', 'nama' => 'Katiga'],
        ['kode' => 'kapat', 'nama' => 'Kapat'],
        ['kode' => 'kalima', 'nama' => 'Kalima'],
        ['kode' => 'kanem', 'nama' => 'Kanem'],
        ['kode' => 'kapitu', 'nama' => 'Kapitu'],
        ['kode' => 'kawolu', 'nama' => 'Kawolu'],
        ['kode' => 'kasanga', 'nama' => 'Kasanga'],
        ['kode' => 'kadasa', 'nama' => 'Kadasa'],
        ['kode' => 'jiyestha', 'nama' => 'Jiyestha'],
        ['kode' => 'sadha', 'nama' => 'Sadha'],
    ];

    public function calculate(Carbon|string $date): LunarDateResult
    {
        $target = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $anchor = Carbon::parse(self::ANCHOR_DATE)->startOfDay();
        $dayDelta = (int) $anchor->diffInDays($target, false);
        $eventDelta = $this->eventIndex($target) - $this->eventIndex($anchor);
        $absoluteSerial = self::ANCHOR_SERIAL + $dayDelta + $eventDelta;
        $monthDelta = $this->floorDiv($absoluteSerial - 1, 30);
        $serial = $this->positiveModulo($absoluteSerial - 1, 30) + 1;
        $sasih = self::SASIH[$this->positiveModulo(self::ANCHOR_SASIH_INDEX + $monthDelta, 12)];
        $isPurnama = $serial === 15;
        $isTilem = $serial === 30;

        return new LunarDateResult(
            available: true,
            verificationStatus: 'calculated',
            tanggalMasehi: $target->toDateString(),
            sasih: $sasih,
            fase: $serial <= 15 ? 'penanggal' : 'pangelong',
            nomorHari: $serial <= 15 ? $serial : $serial - 15,
            isPurnama: $isPurnama,
            isTilem: $isTilem,
            isNampihSasih: false,
            sumber: 'Rumus Pengalantaka internal',
            referensi: 'Anchor KalenderBali.org 2 Februari 2026 dan siklus pengunalatri 63 hari',
            catatan: 'Hasil kalkulasi. Gunakan dataset terverifikasi sebagai keputusan final bila tersedia.',
        );
    }

    public function isPengunalatri(Carbon|string $date): bool
    {
        $target = $date instanceof Carbon ? $date : Carbon::parse($date);
        $anchor = Carbon::parse(self::FIRST_PENGUNALATRI);
        $delta = (int) $anchor->diffInDays($target, false);

        return $delta % self::PENGUNALATRI_INTERVAL === 0;
    }

    private function eventIndex(Carbon $date): int
    {
        $eventAnchor = Carbon::parse(self::FIRST_PENGUNALATRI)->startOfDay();
        $delta = (int) $eventAnchor->diffInDays($date, false);

        return $this->floorDiv($delta, self::PENGUNALATRI_INTERVAL);
    }

    private function floorDiv(int $value, int $divisor): int
    {
        $quotient = intdiv($value, $divisor);

        return $value < 0 && $value % $divisor !== 0 ? $quotient - 1 : $quotient;
    }

    private function positiveModulo(int $value, int $modulus): int
    {
        return (($value % $modulus) + $modulus) % $modulus;
    }
}
