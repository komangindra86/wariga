<?php

namespace App\Services\Lunar;

use App\Contracts\LunarCalendarProviderInterface;
use App\DTO\LunarDateResult;
use App\Models\CalendarLunarDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class VerifiedDatasetLunarProvider implements LunarCalendarProviderInterface
{
    public function __construct(private readonly FormulaLunarProvider $formula) {}

    public function getForDate(Carbon $date): LunarDateResult
    {
        $dateString = $date->copy()
            ->setTimezone((string) config('wariga.timezone', 'Asia/Makassar'))
            ->format('Y-m-d');

        $record = CalendarLunarDate::query()
            ->whereDate('tanggal_masehi', $dateString)
            ->where('is_active', true)
            ->first();

        if ($record === null) {
            return $this->formula->getForDate(Carbon::parse($dateString));
        }

        $sasih = DB::table('master_sasih')->where('id', $record->sasih_id)->first();

        if ($sasih === null || ! $sasih->is_active) {
            return $this->formula->getForDate(Carbon::parse($dateString));
        }

        return new LunarDateResult(
            available: true,
            verificationStatus: $record->verification_status,
            tanggalMasehi: $dateString,
            sasih: ['id' => $sasih->id, 'kode' => $sasih->kode, 'nama' => $sasih->nama],
            fase: $record->fase,
            nomorHari: $record->nomor_hari,
            isPurnama: $record->is_purnama,
            isTilem: $record->is_tilem,
            isNampihSasih: $record->is_nampih_sasih,
            sumber: $record->sumber,
            referensi: $record->referensi,
            catatan: $record->catatan,
        );
    }
}
