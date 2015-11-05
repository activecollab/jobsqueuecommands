<?php

  namespace ActiveCollab\JobQueue\Commands;

  use ActiveCollab\JobsQueue\Jobs\Job;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Output\OutputInterface;
  use Exception;
  use DirectoryIterator;

  /**
   * @package ActiveCollab\JobQueue\Commands
   */
  class PrepareHourlyJobs extends Command
  {
    /**
     * Configure command
     */
    protected function configure()
    {
      parent::configure();

      $this->setName('prepare_hourly_jobs')->setDescription('Prepare hourly jobs for all active instances');
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      try {
        $log = $this->log($input);
        $connection = $this->getDatabaseConnection($input);
        $timestamp = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $jobs_created = 0;

        if ($instance_ids = $this->getInstanceIds()) {
          $priority = Job::HAS_PRIORITY + 1;

          foreach ($this->getInstanceIds() as $instance_id) {
            if ($connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `jobs_queue` WHERE `instance_id` = ? AND `type` = ?', $instance_id, 'ActiveCollab\\ActiveCollabJobs\\Jobs\\Hourly')) {
              $log->warn("Hourly job for instance #{$instance_id} already exists");
            } else {
              $connection->execute('INSERT INTO `jobs_queue` (`type`, `instance_id`, `priority`, `data`, `available_at`) VALUES (?, ?, ?, ?, ?)', 'ActiveCollab\\ActiveCollabJobs\\Jobs\\Hourly', $instance_id, $priority, json_encode([
                'instance_id' => $instance_id,
                'priority' => $priority,
              ]), $timestamp);

              $jobs_created++;
              $log->info("Hourly job set for instance #{$instance_id} (should run at $timestamp)");
            }
          }

          switch ($jobs_created) {
            case 0:
              return $this->success('No jobs created', $input, $output);
            case 1;
              return $this->success('One created', $input, $output);
            default:
              return $this->success("$jobs_created jobs created", $input, $output);
          }
        } else {
          return $this->abort('No instances found', 1, $input, $output);
        }
      } catch (Exception $e) {
        return $this->abortDueToException($e, $input, $output);
      }
    }

    /**
     * Return array of instances that need to be triggered
     *
     * @return array
     */
    private function getInstanceIds()
    {
      $result = [];

      foreach (new DirectoryIterator('/var/www/feather/instances') as $entry) {
        if (!$entry->isDot() && $entry->isDir() && ctype_digit($entry->getFilename()) && is_file($entry->getPathname() . '/tasks/activecollab-cli.php')) {
          $result[] = (integer) $entry->getFilename();
        }
      }

      return $result;
    }
  }