<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
class FailedJobs extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('failed_jobs')
             ->setDescription('List failed jobs grouped by type and date');
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

            $event_types = $connection->executeFirstColumn('SELECT DISTINCT(`type`) FROM `jobs_queue_failed`');

            if (count($event_types)) {
                foreach ($event_types as $event_type) {
                    $output->writeln("$event_type:");

                    $table = new Table($output);
                    $table->setHeaders(['Date', 'Jobs Count']);

                    foreach ($connection->execute('SELECT DATE(`failed_at`) AS "date", COUNT(`id`) AS "failed_jobs_count" FROM `jobs_queue_failed` WHERE `type` = ? GROUP BY DATE(`failed_at`)', $event_type) as $row) {
                        $table->addRow([$row['date'], $row['failed_jobs_count']]);
                    }

                    $table->render();

                    $output->writeln('');
                }
            } else {
                return $this->success('No failed jobs found', $input, $output);
            }
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}