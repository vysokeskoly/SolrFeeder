<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Service;

class Log
{
    public function saveStatusReport(string $filePath, int $status, string $message = 'OK'): void
    {
        if (!file_exists($filePath)) {
            $dirName = dirname($filePath);

            if (!file_exists($dirName)) {
                mkdir($dirName, 0777, true);
            }
        }

        file_put_contents(
            $filePath,
            sprintf(
                '%d %s %s',
                $status,
                (new \DateTime())->format('Y-m-d\TH:i:s'),
                $message
            )
        );
    }
}
