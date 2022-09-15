<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\Map;
use VysokeSkoly\SolrFeeder\Service\XmlParser;
use VysokeSkoly\SolrFeeder\ValueObject\PrimaryKey;

class Timestamps
{
    private bool $lastValuesLoaded = false;
    /** @phpstan-var IMap<string, string> */
    private IMap $current;

    /** @phpstan-param IMap<string, Timestamp> $timestampMap */
    public function __construct(
        private readonly string $filePath,
        private readonly IMap $timestampMap,
        private readonly XmlParser $xmlParser,
    ) {
        $this->current = new Map();
    }

    /** @phpstan-return IMap<string, Timestamp> */
    public function getTimestampMap(): IMap
    {
        $this->loadLastValuesFromFile();

        return $this->timestampMap;
    }

    private function loadLastValuesFromFile(): void
    {
        $filename = $this->getFileFullPath();
        if (!$this->lastValuesLoaded && file_exists($filename)) {
            $this->xmlParser->parseTimestampsFile($filename)
                ->each(function (string $value, string $type = ''): void {
                    /** @var Timestamp $timestamp */
                    $timestamp = $this->timestampMap->get($type);

                    $timestamp->setLastValue($value);
                });
            $this->lastValuesLoaded = true;
        }
    }

    public function saveValuesToFile(): void
    {
        $fileName = $this->getFileFullPath();
        $dirName = dirname($fileName);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><timestamps/>');
        $xml->addAttribute('updatedOn', (new \DateTime())->format('Y-m-d H:i:s.vP'));

        $xml = $this->getTimestampMap()->reduce(
            function (\SimpleXMLElement $xml, Timestamp $timestamp) {
                $xml
                    ->addChild('timestamp', $timestamp->getCurrentUpdated())
                    ->addAttribute('name', $timestamp->getType());

                return $xml;
            },
            $xml,
        );

        if (!file_exists($dirName)) {
            if (!mkdir($dirName, 0777, true) && !is_dir($dirName)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirName));
            }
        }

        $xml->saveXML($fileName);
    }

    private function getFileFullPath(): string
    {
        return __DIR__ . '/../../' . $this->filePath;
    }

    public function setCurrent(PrimaryKey $primaryKey, string $currentTimestamp): void
    {
        $this->current = $this->current->set($primaryKey->getValue(), $currentTimestamp);
    }

    public function getCurrentTimestamp(PrimaryKey $primaryKey): ?string
    {
        $primaryKeyValue = $primaryKey->getValue();

        return $this->current->containsKey($primaryKeyValue)
            ? $this->current->get($primaryKeyValue)
            : null;
    }
}
