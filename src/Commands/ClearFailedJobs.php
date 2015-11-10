<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
class ClearFailedJobs extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('clear_failed_jobs')
             ->setDescription('Clear all failed jobs');
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->getDispatcher($input)->getQueue()->clear();
            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}