<?php

namespace ActiveCollab\JobQueue\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Command
 */
class FailedJobs extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('failed_jobs')
            ->setDescription('List failed jobs grouped by type and date');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $event_types = $this->dispatcher->getQueue()->failedJobStatistics();

            if (count($event_types)) {
                foreach ($event_types as $event_type => $failed_jobs) {
                    $output->writeln("$event_type:");

                    $table = new Table($output);
                    $table->setHeaders(['Date', 'Jobs Count']);

                    foreach ($failed_jobs as $date => $failed_jobs_count) {
                        $table->addRow([$date, $failed_jobs_count]);
                    }

                    $table->render();

                    $output->writeln('');
                }

                return 0;
            } else {
                return $this->success('No failed jobs found', $input, $output);
            }
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
