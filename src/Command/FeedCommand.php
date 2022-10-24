<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VysokeSkoly\SolrFeeder\Facade\FeedFacade;
use VysokeSkoly\SolrFeeder\Service\Notifier;

class FeedCommand extends AbstractCommand
{
    public function __construct(private readonly FeedFacade $feedFacade, private readonly Notifier $notifier)
    {
        parent::__construct('feed');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Feed data from database to SOLR by xml configuration')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to xml config file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->notifier->setIo($this->io);

            $configPath = $input->getArgument('config');
            $this->feedFacade->feedDataToSolr($configPath);

            $this->io->success('Done');

            return 0;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return $e->getCode() ?: 1;
        }
    }
}
