<?php

namespace Database\Seeders;

use App\Models\AlaAyuMaster;
use App\Models\WarigaActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KalenderBaliCurrentActivityRuleSeeder extends Seeder
{
    private const SOURCE = 'https://kalenderbali.org/pilihdewasa.php';

    private const DEFINITIONS = [
        'PWI' => [
            'positive' => 'Amerta Bumi, Ayu Nulus, Buda Ireng, Buda Suka, Dasa Amerta, Derman Bagia, Kamajaya, Panca Amerta, Purnasuka',
            'negative' => 'Amerta Papageran, Asuasa, Carik Walangati, Dadig Krana, Dina Carik, Geheng Manyinget, Geni Agung, Kala Bregala, Kala Dangastra, Kala Pati, Kala Pegat, Kala Prawani, Kala Sungsang, Kala Suwung, Kala Tampak, Kala Temah, Kala Tumpar, Kala Wong, Kaleburau, Lebur Awu, Macekan Lanang, Mreta Sula, Pamacekan, Panca Prawani, Pati Paten, Purnama Danta, Purwani, Purwanin Dina, Rangda Tiga, Sarik Agung, Uncal Balung, Kala Mretyu, Kala Ngruda, Kala Garuda',
        ],
        'NGB' => [
            'positive' => 'Amerta Akasa, Amerta Buwana, Amerta Dadi, Amerta Dewa, Amerta Dewa Jaya, Amerta Kundalini, Ayu Dana, Catur Laba, Dewa Ngelayang, Dewa Stata, Dewasa Mentas, Kala Buingrau, Sedana Tiba, Siwa Sampurna, Subacara, Swarga Menge, Wredhi Guna',
            'negative' => 'Amerta Papageran, Asuasa, Carik Walangati, Dadig Krana, Gagak Anungsang Pati, Geheng Manyinget, Geni Agung, Kala Bregala, Kala Dangastra, Kala Garuda, Kala Gotongan, Kala Jengking, Kala Mretyu, Kala Ngruda, Kala Pati, Kala Pegat, Kala Prawani, Kala Sungsang, Kala Suwung, Kala Temah, Kala Tumpar, Kala Wisesa, Kala Wong, Kaleburau, Macekan Lanang, Mreta Sula, Pamacekan, Panca Prawani, Pati Paten, Purnama Danta, Purwani, Purwanin Dina, Rarung Pagelangan, Salah Wadi, Sarik Agung, Semut Sedulur, Uncal Balung, Dina Carik',
        ],
        'MEP' => [
            'positive' => 'Amerta Murti, Ayu Nulus, Buda Ireng, Buda Suka, Dasa Amerta, Dewa Werdi, Dirgayusa, Panca Werdi',
            'negative' => 'Amerta Papageran, Asuasa, Dadig Krana, Dina Carik, Geheng Manyinget, Geni Agung, Kala Bregala, Kala Dangastra, Kala Garuda, Kala Jengking, Kala Mretyu, Kala Ngruda, Kala Pati, Kala Pegat, Kala Prawani, Kala Sungsang, Kala Suwung, Kala Temah, Kala Tumpar, Kala Wisesa, Kala Wong, Kaleburau, Macekan Lanang, Mreta Sula, Pamacekan, Panca Prawani, Pati Paten, Purnama Danta, Purwani, Purwanin Dina, Rarung Pagelangan, Salah Wadi, Sarik Agung, Uncal Balung',
        ],
        'MLS' => [
            'positive' => 'Istri Payasan',
            'negative' => 'Dina Carik, Geheng Manyinget, Geni Agung, Geni Rawana, Gni Rawana Jejepan, Kala Prawani, Sarik Agung',
        ],
        'MBR' => [
            'positive' => 'Amerta Masa, Amertayoga, Ayu Badra, Dauh Ayu, Derman Bagia, Dewasa Mentas, Dewasa Ngelayang, Guntur Umah/Graha, Kala Empas Munggah, Kala Graha, Kamajaya, Ratu Ngemban Putra, Sampi Gumarang Turun, Siwa Sampurna',
            'negative' => 'Agni Agung Doyan Basmi, Agni Agung Patra Limutan, Carik Walangati, Geni Murub, Gni Rawana Rangkep, Kala Awus, Kala Buingrau, Kala Empas Turun, Kala Rau, Kala Sapuhau, Lebur Awu',
        ],
        'PNR' => [
            'positive' => 'Guntur Umah/Graha',
            'negative' => '',
        ],
        'USH' => [
            'positive' => 'Amertayoga, Jiwa Menganti, Kala Gotongan, Kala Olih',
            'negative' => '',
        ],
        'TNM' => [
            'positive' => 'Upadana Amerta',
            'negative' => 'Asuajeg Munggah, Babi Munggah, Bojog Munggah, Geni Rawana, Gni Rawana Jejepan, Kajeng Uwudan, Kala Gumarang Munggah, Kala Mereng, Kala Muas, Kala Sor',
        ],
        'PTP' => [
            'positive' => 'Kala Buingrau, Kala Was, Kala Wisesa',
            'negative' => '',
        ],
        'MYD' => [
            'positive' => 'Amerta Dewa, Amerta Dewa Jaya, Amerta Kundalini, Ayu Dana, Ayu Nulus, Buda Ireng, Buda Suka, Catur Laba, Dewa Stata, Dewasa Mentas, Siwa Sampurna, Subacara, Wredhi Guna',
            'negative' => 'Amerta Papageran, Asuasa, Dadig Krana, Dina Carik, Geheng Manyinget, Geni Agung, Kala Bregala, Kala Dangastra, Kala Garuda, Kala Jengking, Kala Mretyu, Kala Ngruda, Kala Pati, Kala Pegat, Kala Prawani, Kala Sungsang, Kala Suwung, Kala Temah, Kala Tumpar, Kala Wisesa, Kala Wong, Kaleburau, Macekan Lanang, Mreta Sula, Pamacekan, Panca Prawani, Pati Paten, Purnama Danta, Purwani, Purwanin Dina, Rarung Pagelangan, Salah Wadi, Sarik Agung, Uncal Balung',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::DEFINITIONS as $code => $definition) {
                $activity = WarigaActivity::query()->where('kode', $code)->firstOrFail();
                DB::table('ala_ayu_activity_rules')->where('activity_id', $activity->id)->delete();

                foreach (['positive', 'negative'] as $effect) {
                    foreach ($this->names($definition[$effect]) as $name) {
                        $master = AlaAyuMaster::query()->where('nama', $name)->first();
                        if (! $master) {
                            throw new RuntimeException("Master Ala-Ayuning tidak ditemukan: {$name}");
                        }
                        DB::table('ala_ayu_activity_rules')->insert([
                            'activity_id' => $activity->id,
                            'ala_ayu_master_id' => $master->id,
                            'effect' => $effect,
                            'alahing_priority' => null,
                            'source_url' => self::SOURCE,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    /** @return list<string> */
    private function names(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }
}
