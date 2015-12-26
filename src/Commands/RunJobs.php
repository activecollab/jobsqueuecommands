<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use ActiveCollab\JobsQueue\Jobs\Job;
use ActiveCollab\JobsQueue\Queue\QueueInterface;
/**
 * @package ActiveCollab\JobQueue\Commands
 */
class RunJobs extends Command
{
    /**
     * Configure command
     */
    protected function configure ()
    {
        parent::configure();
        $this->setName('run_jobs')
             ->addOption('seconds', 's', InputOption::VALUE_REQUIRED, 'Run jobs for -s seconds before quitting the process', 50)
             ->addOption('channels', '', InputOption::VALUE_REQUIRED, 'Select one or more channels for jobs for process',QueueInterface::MAIN_CHANNEL)
             ->setDescription('Run jobs that are next in line for up to N seconds');
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $log = $this->log($input);

        // ---------------------------------------------------
        //  Prepare dispatcher and success and error logs
        // ---------------------------------------------------

        $dispatcher = $this->getDispatcher($input);

        $jobs_ran = $jobs_failed = [];

        $dispatcher->getQueue()->onJobFailure(function (Job $job) use (&$jobs_failed) {
            $job_id = $job->getQueueId();

            if (!in_array($job_id, $jobs_failed)) {
                $jobs_failed[] = $job_id;
            }
        });

        // ---------------------------------------------------
        //  Set max execution time for the jobs in queue
        // ---------------------------------------------------

        $max_execution_time = (integer)$input->getOption('seconds');

        $output->writeln("There are " . $dispatcher->getQueue()->count() . " jobs in the queue. Preparing to work for {$max_execution_time} seconds.");

        $work_until = time() + $max_execution_time; // Assume that we spent 1 second bootstrapping the command
        // ---------------------------------------------------
        //  Set channels for the jobs in queue
        // ---------------------------------------------------

        $channels = $this->getChannels($input->getOption('channels'));
        // ---------------------------------------------------
        //  Enter the execution loop
        // ---------------------------------------------------

        do {
            if ($next_in_line = call_user_func_array([$dispatcher->getQueue(), 'nextInLine'], $channels)) {
                $log->info('Running job #' . $next_in_line->getQueueId() . ' (' . get_class($next_in_line) . ')');

                if ($output->getVerbosity()) {
                    $output->writeln('<info>OK</info> Running job #' . $next_in_line->getQueueId() . ' for instance #' . $next_in_line->getData()['instance_id'] . ' (' . get_class($next_in_line) . ')');
                }

                $dispatcher->getQueue()->execute($next_in_line);

                if ($output->getVerbosity()) {
                    $output->writeln('<info>OK</info> Job #' . $next_in_line->getQueueId() . ' done');
                }

                $job_id = $next_in_line->getQueueId();

                if (!in_array($job_id, $jobs_ran)) {
                    $jobs_ran[] = $job_id;
                }
            } else {
                if ($dispatcher->getQueue()->count()) {
                    $log->info('Next in line not found.');

                    if ($output->getVerbosity()) {
                        $sleep_for = mt_rand(900000, 1000000);

                        $output->writeln("<error>Error</error> Reservation collision. Sleeping for $sleep_for microseconds");
                        usleep($sleep_for);
                    }
                } else {
                    break; // No new jobs? Break from the loop. Check is needed because nextInLine() can return NULL in case of job snatching
                }
            }
        } while (time() < $work_until);

        // ---------------------------------------------------
        //  Print stats
        // ---------------------------------------------------

        $execution_stats = [
            'time_limit' => $max_execution_time,
            'exec_time' => round(microtime(true) - ACTIVECOLLAB_JOBS_CONSUMER_SCRIPT_TIME, 3),
            'jobs_ran' => count($jobs_ran),
            'jobs_failed' => count($jobs_failed),
            'left_in_queue' => $dispatcher->getQueue()->count(),
        ];

        $log->info('Execution stats', $execution_stats);
        $output->writeln('Execution stats: ' . $execution_stats['jobs_ran'] . ' ran, ' . $execution_stats['jobs_failed'] . ' failed. ' . $execution_stats['left_in_queue'] . " left in queue. Executed in " . $execution_stats['exec_time']);

        $log->info('Done in ' . (isset($execution_stats) ? $execution_stats['exec_time'] : round(microtime(true) - ACTIVECOLLAB_JOBS_CONSUMER_SCRIPT_TIME, 3)) . ' seconds');
    }

    /**
     * Convert channels string to channel list
     * @param $channels
     * @return array
     * @throws \Exception
     */
    protected function getChannels ($channels)
    {
        $channels = trim($channels);
        if (empty($channels))
        {
            throw new \Exception('No channel found.');
        } elseif ($channels = '*')
        {
            return [];
        } else
        {
            return explode(',', $channels);
        }
    }
}