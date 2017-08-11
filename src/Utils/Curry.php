<?php

namespace VysokeSkoly\SolrFeeder\Utils;

use function Functional\curry_n;

class Curry
{
    public static function explode()
    {
        return curry_n(2, 'explode');
    }
}
