<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    public const COMMAND_PREFIX = 'solr-feeder:';

    protected SymfonyStyle $io;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('VysokeSkoly/SolrFeeder runs just for you :)');
        $this->io->section((string) $this->getName());
    }

    public function setName(string $name): static
    {
        return parent::setName(self::COMMAND_PREFIX . $name);
    }
}
