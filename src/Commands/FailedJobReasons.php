<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
class FailedJobReasons extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('failed_job_reasons')
             ->addArgument('type', InputArgument::REQUIRED, 'Name of the job type, or part that matches only one job')
             ->setDescription('List distinct reasons why a particular job type failed');
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $dispatcher = $this->getDispatcher($input);
            $queue = $dispatcher->getQueue();
            $type = $dispatcher->unfurlType($input->getArgument('type'));

            $output->writeln("Reasons why <comment>'$type'</comment> job failed:");
            $output->writeln('');

            foreach ($queue->getFailedJobReasons($type) as $row) {
                $output->writeln("    <comment>*</comment> $row[reason]");
            }

            $output->writeln('');
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}