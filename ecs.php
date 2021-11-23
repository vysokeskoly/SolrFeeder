<?php declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__ . '/tools/coding-standards/vendor/lmc/coding-standard/ecs.php');
    $containerConfigurator->import(__DIR__ . '/tools/coding-standards/vendor/lmc/coding-standard/ecs-7.4.php');
};
