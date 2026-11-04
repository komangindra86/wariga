<?php

namespace App\Services\Wariga;

final class VersionManager
{
    /** @return array{engine_version: string, rule_version: string, dataset_version: string} */
    public function current(): array
    {
        return [
            'engine_version' => (string) config('wariga.engine_version', '0.4.0'),
            'rule_version' => (string) config('wariga.rule_version', '1.0.0'),
            'dataset_version' => (string) config('wariga.dataset_version', '1.0.0'),
        ];
    }
}
