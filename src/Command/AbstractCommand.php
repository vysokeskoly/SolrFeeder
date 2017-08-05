<?php

namespace VysokeSkoly\SolrFeeder\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    const COMMAND_PREFIX = 'solr-feeder:';

    /** @var SymfonyStyle */
    protected $io;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('VysokeSkoly/SolrFeeder runs just for you :)');
        $this->io->section($this->getName());
    }

    public function setName($name)
    {
        return parent::setName(self::COMMAND_PREFIX . $name);
    }
}
