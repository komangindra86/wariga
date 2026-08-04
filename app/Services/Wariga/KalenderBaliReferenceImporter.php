<?php

namespace App\Services\Wariga;

use App\Models\AlaAyuMaster;
use App\Models\WarigaActivity;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class KalenderBaliReferenceImporter
{
    public const MASTER_URL = 'https://kalenderbali.org/referensialaayu.php';

    public const SELECTOR_URL = 'https://kalenderbali.org/pilihdewasa.php';

    /**
     * @return array{imported: int, names: list<string>}
     */
    public function importMaster(?string $html = null): array
    {
        $html ??= $this->download(self::MASTER_URL);
        $rows = $this->parseMaster($html);

        if (count($rows) < 100) {
            throw new RuntimeException(
                'Halaman referensi tidak lengkap; sinkronisasi dibatalkan agar master lama tidak rusak.',
            );
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                AlaAyuMaster::query()->updateOrCreate(
                    ['nama' => $row['nama']],
                    [
                        'deskripsi' => $row['deskripsi'],
                        'komposisi_teks' => $row['komposisi_teks'],
                        'komposisi' => $this->parseComposition($row['komposisi_teks']),
                        'sumber_nama' => 'Kalender Bali Digital — referensi eksternal',
                        'sumber_url' => self::MASTER_URL,
                        'diimpor_pada' => now(),
                    ],
                );
            }
        });

        return [
            'imported' => count($rows),
            'names' => array_column($rows, 'nama'),
        ];
    }

    /**
     * @return array{activities: int, relations: int, missing_rules: list<string>, failed_pages: list<string>}
     */
    public function importActivityRules(int $year = 2026, int $month = 7): array
    {
        $this->refreshActivityPresentation();
        $baseHtml = $this->download(self::SELECTOR_URL, [
            'bulan' => $month, 'tahun' => $year,
        ]);
        $categories = $this->discoverCategories($baseHtml);
        $activityPages = [];

        foreach ($categories as $category) {
            $categoryHtml = $this->download(self::SELECTOR_URL, [
                'bulan' => $month,
                'tahun' => $year,
                'jenis' => $category['jenis'],
                'macam' => $category['initial_activity'],
                'Submit' => 'Hasil',
            ]);
            foreach ($this->selectOptions($categoryHtml) as $activityName) {
                $activityPages[] = [
                    'category' => $category['nama'],
                    'name' => $activityName,
                    'query' => [
                        'bulan' => $month, 'tahun' => $year,
                        'jenis' => $category['jenis'], 'macam' => $activityName,
                        'Submit' => 'Hasil',
                    ],
                    'url' => self::SELECTOR_URL.'?'.http_build_query([
                        'bulan' => $month,
                        'tahun' => $year,
                        'jenis' => $category['jenis'],
                        'macam' => $activityName,
                        'Submit' => 'Hasil',
                    ]),
                ];
            }
        }

        $relationCount = 0;
        $missing = [];
        $failedPages = [];
        $failedNames = [];
        $preparedPages = [];

        foreach ($activityPages as $page) {
            try {
                $html = $this->download(self::SELECTOR_URL, $page['query']);
            } catch (\Throwable) {
                $failedPages[] = $page['url'];
                $failedNames[] = $page['name'];
                continue;
            }
            $preparedPages[] = $page + [
                'definition' => $this->parseActivityDefinition($html),
            ];
            usleep(600_000);
        }

        if (count($failedPages) > (int) floor(count($activityPages) / 2)) {
            return [
                'activities' => 0,
                'relations' => 0,
                'missing_rules' => [],
                'failed_pages' => $failedPages,
            ];
        }

        $signatures = array_unique(array_map(
            fn (array $page): string => hash('sha256', json_encode($page['definition'])),
            $preparedPages,
        ));
        if (count($preparedPages) >= 20 && count($signatures) < 10) {
            throw new RuntimeException(
                'Variasi aturan kegiatan dari sumber tidak wajar; sinkronisasi dibatalkan agar aturan lama tidak rusak.',
            );
        }

        DB::transaction(function () use (
            $preparedPages, $failedNames, &$relationCount, &$missing,
        ): void {
            foreach ($preparedPages as $index => $page) {
                $activity = $this->upsertActivity(
                    $page['name'],
                    $page['category'],
                    $index + 1,
                );
                DB::table('ala_ayu_activity_rules')
                    ->where('activity_id', $activity->id)
                    ->delete();

                foreach (['positive' => 'dipakai', 'negative' => 'dihindari'] as $effect => $key) {
                    foreach ($page['definition'][$key] as $ruleName) {
                        $master = $this->findMaster($ruleName);
                        if (! $master) {
                            $missing[] = $ruleName;
                            continue;
                        }

                        DB::table('ala_ayu_activity_rules')->updateOrInsert(
                            [
                                'activity_id' => $activity->id,
                                'ala_ayu_master_id' => $master->id,
                                'effect' => $effect,
                            ],
                            [
                                'alahing_priority' => null,
                                'source_url' => $page['url'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                        );
                        $relationCount++;
                    }
                }
            }

            if ($failedNames !== []) {
                WarigaActivity::query()->whereIn('nama', $failedNames)->update(['is_active' => false]);
            }
        });

        return [
            'activities' => count($preparedPages),
            'relations' => $relationCount,
            'missing_rules' => array_values(array_unique($missing)),
            'failed_pages' => $failedPages,
        ];
    }

    /**
     * Rapikan metadata katalog yang sudah tersimpan tanpa mengubah relasi aturan.
     * Aman dijalankan ketika situs sumber sedang membatasi permintaan.
     */
    public function refreshActivityPresentation(): int
    {
        $legacyCategories = [
            'PWI' => 'Upacara', 'NGB' => 'Upacara', 'MEP' => 'Upacara',
            'MLS' => 'Upacara', 'MYD' => 'Upacara', 'MBR' => 'Pembangunan',
            'PNR' => 'Aneka Kegiatan', 'USH' => 'Aneka Kegiatan',
            'TNM' => 'Pertanian-Perkebunan', 'PTP' => 'Pertanian-Perkebunan',
        ];
        $updated = 0;

        WarigaActivity::query()->each(function (WarigaActivity $activity) use ($legacyCategories, &$updated): void {
            $category = $legacyCategories[$activity->kode] ?? $activity->kategori;
            $icon = match ($category) {
                'Upacara' => '🙏',
                'Pertanian-Perkebunan' => '🌱',
                'Peternakan-Perikanan' => '🐄',
                'Peralatan-Senjata' => '🛠️',
                'Pembangunan' => '🏠',
                default => '📅',
            };
            $category = $this->displayCategory($category);
            $icon = $this->activityIcon($activity->nama, $category);
            $activity->update([
                'kategori' => $category,
                'deskripsi' => "Mencari hari yang dianjurkan dan perlu dihindari untuk {$activity->nama}, berdasarkan Ala-Ayuning Dewasa.",
                'disclaimer' => (string) config('wariga.reference_disclosure')
                    .' Konfirmasikan keputusan adat penting kepada ahli Wariga.',
                'icon' => $icon,
            ]);
            $updated++;
        });

        return $updated;
    }

    /**
     * Sinkronisasi terbatas pada kegiatan yang sudah tersedia di aplikasi.
     *
     * @return array{activities: int, relations: int, missing_rules: list<string>, failed_pages: list<string>}
     */
    public function importCurrentActivityRules(int $year = 2026, int $month = 7): array
    {
        $mapping = [
            ['code' => 'PWI', 'jenis' => 1, 'source' => 'Upacara Pernikahan'],
            ['code' => 'NGB', 'jenis' => 1, 'source' => 'Pitra Yadnya'],
            ['code' => 'MEP', 'jenis' => 1, 'source' => 'Manusa Yadnya (Potong Gigi)'],
            ['code' => 'MLS', 'jenis' => 1, 'source' => 'Upacara Melaspas'],
            ['code' => 'MBR', 'jenis' => 5, 'source' => 'Membangun Rumah'],
            ['code' => 'PNR', 'jenis' => 6, 'source' => 'Pindah Rumah'],
            ['code' => 'USH', 'jenis' => 6, 'source' => 'Mulai Membangun Usaha'],
            ['code' => 'TNM', 'jenis' => 2, 'source' => 'Menanam Tanaman'],
            ['code' => 'PTP', 'jenis' => 2, 'source' => 'Menebang Kayu'],
            ['code' => 'MYD', 'jenis' => 1, 'source' => 'Manusa Yadnya'],
        ];
        $prepared = [];
        $failed = [];

        foreach ($mapping as $item) {
            $url = self::SELECTOR_URL.'?'.http_build_query([
                'bulan' => $month, 'tahun' => $year, 'jenis' => $item['jenis'],
                'macam' => $item['source'], 'Submit' => 'Hasil',
            ]);
            try {
                $prepared[] = $item + [
                    'url' => $url,
                    'definition' => $this->parseActivityDefinition($this->download(self::SELECTOR_URL, [
                        'bulan' => $month, 'tahun' => $year, 'jenis' => $item['jenis'],
                        'macam' => $item['source'], 'Submit' => 'Hasil',
                    ])),
                ];
            } catch (\Throwable) {
                $failed[] = $url;
            }
            usleep(250_000);
        }

        if ($failed !== []) {
            return [
                'activities' => 0, 'relations' => 0,
                'missing_rules' => [], 'failed_pages' => $failed,
            ];
        }

        $relations = 0;
        $missing = [];
        DB::transaction(function () use ($prepared, &$relations, &$missing): void {
            foreach ($prepared as $item) {
                $activity = WarigaActivity::query()->where('kode', $item['code'])->firstOrFail();
                DB::table('ala_ayu_activity_rules')->where('activity_id', $activity->id)->delete();

                foreach (['positive' => 'dipakai', 'negative' => 'dihindari'] as $effect => $key) {
                    foreach ($item['definition'][$key] as $ruleName) {
                        $master = $this->findMaster($ruleName);
                        if (! $master) {
                            $missing[] = $ruleName;
                            continue;
                        }
                        DB::table('ala_ayu_activity_rules')->insert([
                            'activity_id' => $activity->id,
                            'ala_ayu_master_id' => $master->id,
                            'effect' => $effect,
                            'alahing_priority' => null,
                            'source_url' => $item['url'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $relations++;
                    }
                }
            }
        });

        return [
            'activities' => count($prepared),
            'relations' => $relations,
            'missing_rules' => array_values(array_unique($missing)),
            'failed_pages' => [],
        ];
    }

    /**
     * @return list<array{nama: string, deskripsi: string, komposisi_teks: string}>
     */
    public function parseMaster(string $html): array
    {
        $xpath = $this->xpath($html);
        $rows = [];

        foreach ($xpath->query('//tr[contains(concat(" ", normalize-space(@class), " "), " daftar ")]') as $tr) {
            if (! $tr instanceof DOMElement) {
                continue;
            }

            $cells = $xpath->query('./td', $tr);
            if ($cells->length < 3) {
                continue;
            }

            $name = $this->clean($cells->item(0)?->textContent ?? '');
            if ($name === '') {
                continue;
            }

            $rows[] = [
                'nama' => $name,
                'deskripsi' => $this->clean($cells->item(1)?->textContent ?? ''),
                'komposisi_teks' => $this->clean($cells->item(2)?->textContent ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * Komposisi dipertahankan sebagai alternatif (OR), sementara token di dalam
     * satu alternatif harus terpenuhi bersama-sama (AND).
     *
     * @return list<array{raw: string, tokens: list<string>}>
     */
    public function parseComposition(string $text): array
    {
        $alternatives = preg_split('/\.\s*/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_map(
            fn (string $alternative): array => [
                'raw' => trim($alternative),
                'tokens' => array_values(array_filter(
                    preg_split('/\s+/u', trim($alternative)) ?: [],
                )),
            ],
            $alternatives,
        ));
    }

    /**
     * @return array{dipakai: list<string>, dihindari: list<string>}
     */
    public function parseActivityDefinition(string $html): array
    {
        $xpath = $this->xpath($html);
        $text = '';

        foreach ($xpath->query('//p[contains(., "Hari dipakai:")]') as $paragraph) {
            $text = $this->clean($paragraph->textContent);
            break;
        }

        if (! preg_match('/Hari dipakai:\s*(.*?)\s*Hari dihindari:\s*(.*)$/u', $text, $matches)) {
            throw new RuntimeException('Definisi hari dipakai/dihindari tidak ditemukan.');
        }

        return [
            'dipakai' => $this->ruleNames($matches[1]),
            'dihindari' => $this->ruleNames($matches[2]),
        ];
    }

    public function download(string $url, array $query = []): string
    {
        $response = Http::timeout(30)->retry(2, 300)->get($url, $query);

        if (! $response->successful() || trim($response->body()) === '') {
            throw new RuntimeException("Gagal membaca sumber KalenderBali.org [{$response->status()}].");
        }

        return $response->body();
    }

    private function xpath(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $encoded = '<meta charset="UTF-8">'.mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
        $loaded = $document->loadHTML($encoded, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('HTML referensi KalenderBali.org tidak dapat dibaca.');
        }

        return new DOMXPath($document);
    }

    /**
     * @return list<array{nama: string, jenis: int, url: string, initial_activity: string}>
     */
    private function discoverCategories(string $html): array
    {
        $xpath = $this->xpath($html);
        $categories = [];

        foreach ($xpath->query('//a[contains(@href, "pilihdewasa.php") and contains(@href, "jenis=")]') as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }
            $href = html_entity_decode($link->getAttribute('href'));
            parse_str((string) parse_url($href, PHP_URL_QUERY), $query);
            $jenis = (int) ($query['jenis'] ?? 0);
            $name = $this->clean($link->textContent);

            if ($jenis < 1 || $name === '' || isset($categories[$jenis])) {
                continue;
            }

            $categories[$jenis] = [
                'nama' => $name,
                'jenis' => $jenis,
                'url' => 'https://kalenderbali.org/'.ltrim($href, '/'),
                'initial_activity' => (string) ($query['macam'] ?? ''),
            ];
        }

        ksort($categories);

        return array_values($categories);
    }

    /**
     * @return list<string>
     */
    private function selectOptions(string $html): array
    {
        $xpath = $this->xpath($html);
        $select = $xpath->query('//select')->item(0);
        if (! $select) {
            return [];
        }

        $options = [];
        foreach ($xpath->query('.//option', $select) as $option) {
            $name = $this->clean($option->textContent);
            if ($name !== '') {
                $options[] = $name;
            }
        }

        return $options;
    }

    private function upsertActivity(string $name, string $category, int $order): WarigaActivity
    {
        $existingCodes = [
            'Pitra Yadnya' => 'NGB',
            'Manusa Yadnya (Potong Gigi)' => 'MEP',
            'Upacara Melaspas' => 'MLS',
            'Upacara Pernikahan' => 'PWI',
            'Membangun Rumah' => 'MBR',
            'Pindah Rumah' => 'PNR',
            'Mulai Membangun Usaha' => 'USH',
            'Menanam Tanaman' => 'TNM',
            'Menebang Kayu' => 'PTP',
            'Manusa Yadnya' => 'MYD',
        ];
        $code = $existingCodes[$name] ?? 'KB-'.strtoupper(substr(sha1($category.'|'.$name), 0, 10));
        $activity = WarigaActivity::query()->where('kode', $code)->first()
            ?? WarigaActivity::query()->where('nama', $name)->first()
            ?? new WarigaActivity;
        $displayName = $activity->exists ? $activity->nama : $name;
        $icon = match ($category) {
            'Upacara' => '🙏',
            'Pertanian-Perkebunan' => '🌱',
            'Peternakan-Perikanan' => '🐄',
            'Peralatan-Senjata' => '🛠️',
            'Pembangunan' => '🏠',
            default => '📅',
        };

        $category = $this->displayCategory($category);
        $icon = $this->activityIcon($displayName, $category);
        $activity->fill([
            'kode' => $code,
            'nama' => $displayName,
            'slug' => Str::slug($displayName),
            'kategori' => $category,
            'deskripsi' => "Mencari hari yang dianjurkan dan perlu dihindari untuk {$displayName}, berdasarkan Ala-Ayuning Dewasa.",
            'disclaimer' => (string) config('wariga.reference_disclosure')
                .' Konfirmasikan keputusan adat penting kepada ahli Wariga.',
            'icon' => $icon,
            'urutan' => $order,
            'is_active' => true,
        ])->save();

        return $activity;
    }

    private function displayCategory(string $category): string
    {
        return match ($category) {
            'Aneka Kegiatan' => 'Aktivitas Sehari-hari',
            'Pembangunan' => 'Rumah & Bangunan',
            'Peralatan-Senjata' => 'Peralatan & Keterampilan',
            'Pertanian-Perkebunan' => 'Tani & Kebun',
            'Peternakan-Perikanan' => 'Ternak & Perairan',
            'Upacara' => 'Upacara & Yadnya',
            default => $category,
        };
    }

    private function activityIcon(string $name, string $category): string
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'perjalanan'), str_contains($name, 'bepergian') => '🧭',
            str_contains($name, 'usaha'), str_contains($name, 'dagang') => '💼',
            str_contains($name, 'rapat'), str_contains($name, 'peraturan') => '📝',
            str_contains($name, 'belajar'), str_contains($name, 'sekolah') => '📚',
            str_contains($name, 'obat'), str_contains($name, 'sakit') => '🌿',
            str_contains($name, 'menikah'), str_contains($name, 'pernikahan') => '💍',
            str_contains($name, 'rumah'), str_contains($name, 'bangunan') => '🏠',
            str_contains($name, 'air'), str_contains($name, 'sumur'), str_contains($name, 'irigasi') => '💧',
            str_contains($name, 'ikan'), str_contains($name, 'laut') => '🐟',
            str_contains($name, 'ternak') => '🐄',
            str_contains($name, 'tanam'), str_contains($name, 'kebun') => '🌱',
            $category === 'Upacara & Yadnya' => '🙏',
            $category === 'Rumah & Bangunan' => '🏗️',
            $category === 'Peralatan & Keterampilan' => '🛠️',
            $category === 'Tani & Kebun' => '🌾',
            $category === 'Ternak & Perairan' => '🐾',
            default => '✨',
        };
    }

    /**
     * @return list<string>
     */
    private function ruleNames(string $value): array
    {
        $value = trim($value, " \t\n\r\0\x0B.");
        if ($value === '' || strcasecmp($value, 'Tidak ditentukan') === 0) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $name): string => trim(
                (string) preg_replace('/(?:\x{00C2}|\x{00C3}\x{201A})+$/u', '', trim($name)),
                " \t\n\r\0\x0B.",
            ),
            explode(',', $value),
        )));
    }

    private function findMaster(string $ruleName): ?AlaAyuMaster
    {
        $master = AlaAyuMaster::query()->where('nama', $ruleName)->first();
        if ($master) {
            return $master;
        }

        // Beberapa baris master sumber menggabungkan dua nama dengan koma,
        // sedangkan halaman kegiatan menuliskannya sebagai dua butir.
        return AlaAyuMaster::query()
            ->where('nama', 'like', '%'.$ruleName.'%')
            ->get()
            ->first(fn (AlaAyuMaster $candidate): bool => in_array(
                $ruleName,
                array_map('trim', explode(',', $candidate->nama)),
                true,
            ));
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
