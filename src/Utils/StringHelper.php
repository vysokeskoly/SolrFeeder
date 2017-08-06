<?php

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
            ? strpos($haystack, $needle) !== false
            : stripos($haystack, $needle) !== false;
    }
}
