<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Utils;

use Assert\Assertion;

class StringHelper
{
    public function contains(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        Assertion::notEmpty($needle);

        if (empty($haystack)) {
            return false;
        }

        return $caseSensitive
            ? mb_strpos($haystack, $needle) !== false
            : mb_stripos($haystack, $needle) !== false;
    }
}
