<?php

namespace VysokeSkoly\SolrFeeder\Service;

use Assert\Assertion;
use VysokeSkoly\SolrFeeder\Entity\Timestamps;

class TimestampUpdater
{
    public function updateCurrentTimestamps(Timestamps $timestamps, string $type, string $primaryKeyValue)
    {
        $timestampMap = $timestamps->getTimestampMap();
        Assertion::true($timestampMap->containsKey($type));

        $timestampMap->get($type)->update($timestamps->getCurrentTimestamp($primaryKeyValue));
    }
}
