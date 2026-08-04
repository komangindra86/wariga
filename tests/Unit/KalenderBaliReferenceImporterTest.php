<?php

namespace Tests\Unit;

use App\Services\Wariga\KalenderBaliReferenceImporter;
use PHPUnit\Framework\TestCase;

class KalenderBaliReferenceImporterTest extends TestCase
{
    public function test_parses_master_rows_and_composition_alternatives(): void
    {
        $html = <<<'HTML'
        <html><body><table>
          <tr class="daftar">
            <td>Amerta Bumi</td>
            <td>Baik untuk perkawinan.</td>
            <td>Soma Wage Penanggal 1. Buda Pon Penanggal 10.</td>
          </tr>
        </table></body></html>
        HTML;

        $importer = new KalenderBaliReferenceImporter;
        $rows = $importer->parseMaster($html);
        $composition = $importer->parseComposition($rows[0]['komposisi_teks']);

        $this->assertSame('Amerta Bumi', $rows[0]['nama']);
        $this->assertSame(
            ['Soma', 'Wage', 'Penanggal', '1'],
            $composition[0]['tokens'],
        );
        $this->assertSame(
            ['Buda', 'Pon', 'Penanggal', '10'],
            $composition[1]['tokens'],
        );
    }

    public function test_parses_activity_positive_and_negative_rule_names(): void
    {
        $html = <<<'HTML'
        <html><body>
          <p>
            Hari dipakai: Amerta Akasa, Kala Buingrau, Kala KutilaÂ.
            Hari dihindari: Gagak Anungsang Pati, Kala Temah.
          </p>
        </body></html>
        HTML;

        $result = (new KalenderBaliReferenceImporter)->parseActivityDefinition($html);

        $this->assertSame(['Amerta Akasa', 'Kala Buingrau', 'Kala Kutila'], $result['dipakai']);
        $this->assertSame(
            ['Gagak Anungsang Pati', 'Kala Temah'],
            $result['dihindari'],
        );
    }
}
