<?php
namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\JobsQueue\Dispatcher;
use ActiveCollab\JobsQueue\Queue\MySqlQueue;
use ActiveCollab\JobsQueue\Jobs\Job;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Exception;
use DateTime;
use DateTimeZone;
use mysqli;
use RuntimeException;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
abstract class Command extends SymfonyCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->addOption('config-path', 'c', InputOption::VALUE_REQUIRED, 'Path to configuration file',  '/etc/activecollab-jobs.json')
             ->addOption('debug', '', InputOption::VALUE_NONE, 'Output debug details')
             ->addOption('json', '', InputOption::VALUE_NONE, 'Output JSON');
    }

    /**
     * Abort due to error
     *
     * @param  string $message
     * @param  integer $error_code
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function abort($message, $error_code, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('json')) {
            $output->writeln(json_encode([
                'ok' => false,
                'error_message' => $message,
                'error_code' => $error_code,
            ]));
        } else {
            $output->writeln($message);
        }

        return $error_code < 1 ? 1 : $error_code;
    }

    /**
     * Show success message
     *
     * @param  string $message
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function success($message, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('json')) {
            $output->writeln(json_encode([
                'ok' => true,
                'message' => $message,
            ]));
        } else {
            $output->writeln($message);
        }

        return 0;
    }

    /**
     * @param  array|null $data
     * @param  OutputInterface $output
     * @return int
     */
    protected function successJson(array $data = null, OutputInterface $output)
    {
        $result = ['ok' => true];

        if (!empty($data) && is_array($data)) {
            $result = array_merge($result, $data);

            if (!$result['ok'] === true) {
                $result['ok'] = true;
            }
        }

        $output->writeln(json_encode($result));

        return 0;
    }

    /**
     * Abort due to an exception
     *
     * @param  Exception $e
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function abortDueToException(Exception $e, InputInterface $input, OutputInterface $output)
    {
        $message = $e->getMessage();
        $code = $this->exceptionToErrorCode($e);

        if ($input->getOption('json')) {
            $response = [
                'ok' => false,
                'error_message' => $message,
                'error_code' => $code,
            ];

            if ($input->getOption('debug')) {
                $response['error_class'] = get_class($e);
                $response['error_file'] = $e->getFile();
                $response['error_line'] = $e->getLine();
                $response['error_trace'] = $e->getTraceAsString();
            }

            $output->writeln(json_encode($response));
        } else {
            if ($input->getOption('debug')) {
                $output->writeln('Jobs error: <' . get_class($e) . '>' . $message . ', in file ' . $e->getFile() . ' on line ' . $e->getLine());
                $output->writeln('');
                $output->writeln('Backtrace');
                $output->writeln('');
                $output->writeln($e->getTraceAsString());
            } else {
                $output->writeln('Jobs error: ' . $message);
            }
        }

        return $code;
    }

    /**
     * Get command error code from exception
     *
     * @param  Exception $e
     * @return int
     */
    protected function exceptionToErrorCode(Exception $e)
    {
        return $e->getCode() > 0 ? $e->getCode() : 1;
    }

    /**
     * Return a date time instance from input argument
     *
     * @param  InputInterface $input
     * @param  string $argument
     * @param  string $default
     * @return DateTime
     */
    protected function getDateTimeFromArgument(InputInterface $input, $argument, $default)
    {
        $value = $input->getArgument($argument);

        if (empty($value)) {
            $value = $default;
        }

        return new DateTime($value, new DateTimeZone('GMT'));
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * @var Connection
     */
    private $database_connection = false;

    /**
     * Return database connection
     *
     * @param  InputInterface $input
     * @return Connection
     */
    protected function &getDatabaseConnection(InputInterface $input)
    {
        if ($this->database_connection === false) {
            $mysqli = $this->getDatabaseConnectionFromConfig($this->getConfigOptions($input));

            $this->database_connection = new Connection($mysqli);
        }

        return $this->database_connection;
    }

    /**
     * Use first successful MySQL connection based on the options that we have
     *
     * @param  array  $config_options
     * @return mysqli
     */
    private function getDatabaseConnectionFromConfig(array $config_options)
    {
        $connection_error_message = '';
        $connection_error_code = 0;

        foreach (explode(',', $config_options['db_host']) as $host) {
            $mysqli = new mysqli($host, $config_options['db_user'], $config_options['db_pass'], $config_options['db_name']);

            if ($mysqli->connect_error) {
                $connection_error_message = $mysqli->connect_error;
                $connection_error_code = $mysqli->connect_errno;
            } else {
                return $mysqli;
            }
        }

        throw new RuntimeException("Failed to connect to database. MySQL said: $connection_error_message", $connection_error_code);
    }

    /**
     * @var Dispatcher
     */
    private $dispatcher = false;

    /**
     * Return job dispatcher instance
     *
     * @param  InputInterface $input
     * @return Dispatcher
     */
    protected function &getDispatcher(InputInterface $input)
    {
        if ($this->dispatcher === false) {
            $log = $this->log($input);
            $this->dispatcher = new Dispatcher(new MySqlQueue($this->getDatabaseConnection($input), false));

            $this->dispatcher->getQueue()->onJobFailure(function (Job $job, Exception $e) use (&$jobs_failed, &$log) {
                $log->error('Exception caught while running a job', [
                'job_type' => get_class($job),
                'job_id' => $job->getQueueId(),
                'exception' => $e,
                'thrown_on' => $e->getFile() . ' @ ' . $e->getLine(),
                ]);
            });
        }

        return $this->dispatcher;
    }

    /**
     * @var array
     */
    private $config_options = false;

    /**
     * Read and return configuration options
     *
     * @param  InputInterface $input
     * @return array
     */
    protected function &getConfigOptions(InputInterface $input)
    {
        if ($this->config_options === false) {
            $config_path = $input->getOption('config-path');

            if (is_file($config_path)) {
                $this->config_options = json_decode(file_get_contents($config_path), true);
            } else {
                throw new \InvalidArgumentException("Config file not found at '$config_path'");
            }
        }

        return $this->config_options;
    }

    /**
     * Return value of a particular configuration option
     *
     * @param  string         $name
     * @param  InputInterface $input
     * @return mixed
     */
    protected function getConfigOption($name, InputInterface $input)
    {
        if ($this->config_options === false) {
            $this->getConfigOptions($input);
        }

        return array_key_exists($name, $this->config_options) ? $this->config_options[$name] : null;
    }

    /**
     * @var Logger
     */
    private $log;

    /**
     * @param  InputInterface $input
     * @return Logger
     */
    protected function &log(InputInterface $input)
    {
        if (empty($this->log)) {
            $this->log = new Logger('cli');

            $logs_path = $this->getConfigOption('logs_path', $input);

            if (!is_dir($logs_path)) {
                throw new RuntimeException("Failed to find logs path: '$logs_path'");
            }

            $handler = new StreamHandler($logs_path . '/' . date('Y-m-d\HH') . '.txt',
            ($input->getOption('debug') ? Logger::DEBUG : Logger::WARNING));

            $formatter = new LineFormatter();
            $formatter->includeStacktraces(true);

            $handler->setFormatter($formatter);

            $this->log->pushHandler($handler);
        }

        return $this->log;
    }
}