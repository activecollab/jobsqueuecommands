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
            $connection = $this->getDatabaseConnection($input);

            $event_type_names = $connection->executeFirstColumn('SELECT DISTINCT(`type`) FROM `jobs_queue_failed` WHERE `type` LIKE ?', '%' . $input->getArgument('type') . '%');

            if (count($event_type_names) > 1) {
                return $this->abort('More than one job type found', 1, $input, $output);
            } elseif (count($event_type_names) == 0) {
                return $this->abort('No job type that matches type argument found under failed jobs', 1, $input, $output);
            }

            $type = $event_type_names[0];

            $output->writeln("Reasons why <comment>'$type'</comment> job failed:");
            $output->writeln('');

            foreach ($connection->execute('SELECT DISTINCT(`reason`) AS "reason" FROM `jobs_queue_failed` WHERE `type` = ?', $type) as $row) {
                $output->writeln("    <comment>*</comment> $row[reason]");
            }

            $output->writeln('');
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}