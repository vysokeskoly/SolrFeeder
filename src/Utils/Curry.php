<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Utils;

use function Functional\curry_n;

class Curry
{
    public static function explode(): callable
    {
        return curry_n(2, 'explode');
    }

    public static function implode(): callable
    {
        return curry_n(2, 'implode');
    }

    public static function replace(): callable
    {
        return curry_n(3, 'str_replace');
    }

    public static function trim(): callable
    {
        $trim = function (string $characters, string $string) {
            return trim($string, $characters);
        };

        return curry_n(2, $trim);
    }
}
