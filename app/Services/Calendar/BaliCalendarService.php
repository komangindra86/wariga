<?php

namespace App\Services\Calendar;

use App\DTO\BaliDateResult;
use App\DTO\CalendarElement;
use App\Repositories\CalendarMasterRepository;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Throwable;

final class BaliCalendarService
{
    public function __construct(
        private readonly CycleCalculator $cycleCalculator,
        private readonly WukuCalculator $wukuCalculator,
        private readonly WewaranCalculator $wewaranCalculator,
        private readonly CalendarMasterRepository $masterRepository,
    ) {}

    public function calculate(Carbon|string $date): BaliDateResult
    {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');
        $calendarDate = $this->normalizeDate($date, $timezone);
        $cycleIndex = $this->cycleCalculator->calculateCycleIndex($calendarDate);
        $pancaOrder = ($cycleIndex % 5) + 1;
        $saptaOrder = ($cycleIndex % 7) + 1;
        $panca = $this->requiredMaster('panca_wara', $pancaOrder);
        $sapta = $this->requiredMaster('sapta_wara', $saptaOrder);
        $orders = $this->wewaranCalculator->calculate(
            $cycleIndex,
            (int) $panca->urip,
            (int) $sapta->urip,
        );

        return new BaliDateResult(
            tanggalMasehi: $calendarDate->format('Y-m-d'),
            cycleIndex: $cycleIndex,
            hariKeSiklus210: $cycleIndex + 1,
            wuku: $this->element($this->requiredMaster(
                'wuku',
                $this->wukuCalculator->calculateOrder($cycleIndex),
            )),
            wewaran: $this->resolveWewaran($orders),
            engineVersion: (string) config('wariga.engine_version', '0.2.0'),
        );
    }

    private function requiredMaster(string $master, int $order): stdClass
    {
        $record = $this->masterRepository->findByOrder($master, $order);

        if ($record === null || ! $record->is_active) {
            throw new RuntimeException(
                "Master Kalender Bali [{$master}] urutan {$order} belum tersedia atau tidak aktif."
            );
        }

        return $record;
    }

    private function element(stdClass $record): CalendarElement
    {
        return new CalendarElement(
            kode: $record->kode,
            nama: $record->nama,
            urutan: (int) $record->urutan,
            urip: $record->urip === null ? null : (int) $record->urip,
        );
    }

    /**
     * @param  array<string, int|null>  $orders
     * @return array<string, CalendarElement|null>
     */
    private function resolveWewaran(array $orders): array
    {
        $result = [];

        foreach ($orders as $master => $order) {
            $result[$master] = $order === null
                ? null
                : $this->element($this->requiredMaster($master, $order));
        }

        return $result;
    }

    private function normalizeDate(Carbon|string $date, string $timezone): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy()->setTimezone($timezone)->startOfDay();
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $date, $timezone)->startOfDay();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Tanggal Masehi harus menggunakan format YYYY-MM-DD.',
                previous: $exception
            );
        }
    }
}
