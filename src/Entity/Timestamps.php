<?php

namespace VysokeSkoly\SolrFeeder\Entity;

use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;
use VysokeSkoly\SolrFeeder\Service\XmlParser;

class Timestamps
{
    /** @var string */
    private $filePath;

    /** @var IMap<string, Timestamp> */
    private $timestampMap;

    /** @var XmlParser */
    private $xmlParser;

    /** @var bool */
    private $lastValuesLoaded = false;

    /** @var \MF\Collection\Mutable\Generic\IMap */
    private $current;

    public function __construct(string $filePath, IMap $timestampList, XmlParser $xmlParser)
    {
        $this->filePath = $filePath;
        $this->timestampMap = $timestampList;
        $this->xmlParser = $xmlParser;

        $this->current = new Map('string', 'string');
    }

    /**
     * @return IMap|Timestamp[]
     */
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
                ->each(function (string $value, string $type) {
                    /** @var Timestamp $timestamp */
                    $timestamp = $this->timestampMap->get($type);

                    $timestamp->setLastValue($value);
                });
            $this->lastValuesLoaded = true;
        }
    }

    public function saveValuesToFile()
    {
        $fileName = $this->getFileFullPath();
        $dirName = dirname($fileName);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><timestamps/>');
        $xml->addAttribute('updatedOn', (new \DateTime())->format('Y-m-d H:i:s.vP'));

        $this->getTimestampMap()->reduce(
            function (\SimpleXMLElement $xml, Timestamp $timestamp) {
                $xml
                    ->addChild('timestamp', $timestamp->getCurrentUpdated())
                    ->addAttribute('name', $timestamp->getType());

                return $xml;
            },
            $xml
        );

        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }

        $xml->saveXML($fileName);
    }

    private function getFileFullPath(): string
    {
        return __DIR__ . '/../../' . $this->filePath;
    }

    public function setCurrent(string $primaryKey, string $currentTimestamp): void
    {
        $this->current->set($primaryKey, $currentTimestamp);
    }

    public function getCurrentTimestamp(string $primaryKeyValue): string
    {
        return $this->current->get($primaryKeyValue);
    }
}
