<?php
declare(strict_types=1);

namespace Sokil\IsoCodes\Database;

use Sokil\IsoCodes\AbstractPartitionedDatabase;
use Sokil\IsoCodes\Database\Subdivisions\Subdivision;

class SubdivisionsPartitioned extends AbstractPartitionedDatabase
{
    public static function getISONumber(): string
    {
        return '3166-2';
    }

    /**
     * @param mixed[] $entry
     */
    protected function arrayToEntry(array $entry): Subdivision
    {
        return new Subdivision(
            $entry['name'],
            $entry['code'],
            $entry['type'],
            !empty($entry['parent']) ? $entry['parent'] : null
        );
    }

    private function loadFromJSONFile(string $countryCodeAlpha2) : array
    {
        $pathToPartitionFile = sprintf(
            '%s/iso_%s/%s.json',
            $this->getDatabasesPath(),
            $this->getISONumber(),
            $countryCodeAlpha2
        );

        if (!file_exists($pathToPartitionFile)) {
            // todo: benchmark this
            return [];
        }

        // todo: stream_get_contents
        return \json_decode(\file_get_contents($pathToPartitionFile), true);
    }

    /**
     * @param string $subdivisionCode in format "alpha2country-subdivision", e.g. "UA-43"
     */
    public function getByCode(string $subdivisionCode): ?Subdivision
    {
        if (strpos($subdivisionCode, '-') === false) {
            return null;
        }

        [$alpha2CountryCode] = explode('-', $subdivisionCode);

        return $this->getAllByCountryCode($alpha2CountryCode)[$subdivisionCode] ?? null;
    }

    /**
     * @param string $alpha2CountryCode e.g. "UA"
     *
     * @return Subdivision[]
     */
    public function getAllByCountryCode(string $alpha2CountryCode): array
    {
        $subdivisions = [];

        foreach ($this->loadFromJSONFile($alpha2CountryCode) as $subdivision) {
            $subdivisions[$subdivision['code']] = $this->arrayToEntry($subdivision);
        }

        // todo: cache result for reuse
        return $subdivisions;
    }
}
