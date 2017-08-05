<?php

namespace VysokeSkoly\SolrFeeder\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VysokeSkoly\SolrFeeder\Facade\FeedFacade;

class FeedCommand extends AbstractCommand
{
    /** @var FeedFacade */
    private $feedFacade;

    /**
     * @param FeedFacade $feedFacade
     */
    public function __construct(FeedFacade $feedFacade)
    {
        $this->feedFacade = $feedFacade;
        parent::__construct('feed');
    }

    protected function configure()
    {
        $this
            ->setDescription('Feed data from database to SOLR by xml configuration')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to xml config file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $configPath = $input->getArgument('config');
            $this->feedFacade->feedDataToSolr($configPath, $this->io);

            $this->io->success('Done');

            return 0;
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());

            return $e->getCode() ?? 1;
        }
    }
}
