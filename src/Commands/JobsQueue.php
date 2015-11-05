<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
class JobsQueue extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('jobs_queue')
             ->setDescription('List all jobs queues grouped by type');
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

            $type_rows = $connection->execute('SELECT `type`, COUNT(`id`) AS "queued_jobs_count" FROM `jobs_queue` GROUP BY `type`');

            if (count($type_rows)) {
                $table = new Table($output);
                $table->setHeaders(['Event Type', 'Jobs Count']);

                foreach ($type_rows as $row) {
                    $table->addRow([$row['type'], $row['queued_jobs_count']]);
                }

                $table->render();
                $output->writeln('');
            } else {
                return $this->success('No jobs in the queue', $input, $output);
            }
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}