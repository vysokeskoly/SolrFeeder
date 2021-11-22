<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Constant;

final class Functions
{
    public const FUNCTIONAL_NS = 'Functional\\';

    public const LAST = self::FUNCTIONAL_NS . 'last';
    public const FIRST = self::FUNCTIONAL_NS . 'first';

    private function __construct()
    {
    }
}
