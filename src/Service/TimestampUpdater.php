<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;
use VysokeSkoly\SolrFeeder\ValueObject\PrimaryKey;

class TimestampUpdater
{
    public function updateCurrentTimestamps(Timestamps $timestamps, string $type, PrimaryKey $primaryKey): void
    {
        $timestampMap = $timestamps->getTimestampMap();
        Assertion::true($timestampMap->containsKey($type));

        $timestampMap->get($type)->update($timestamps->getCurrentTimestamp($primaryKey));
    }
}
