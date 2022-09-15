<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

class Log
{
    public function saveStatusReport(string $filePath, int $status, string $message = 'OK'): void
    {
        if (!file_exists($filePath)) {
            $dirName = dirname($filePath);

            if (!file_exists($dirName)) {
                if (!mkdir($dirName, 0777, true) && !is_dir($dirName)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirName));
                }
            }
        }

        file_put_contents(
            $filePath,
            sprintf(
                '%d %s %s',
                $status,
                (new \DateTime())->format('Y-m-d\TH:i:s'),
                $message,
            ),
        );
    }
}
