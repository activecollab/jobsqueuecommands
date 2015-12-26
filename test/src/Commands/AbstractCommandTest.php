<?php
namespace ActiveCollab\JobQueue\Test\Commands;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\JobQueue\Command\Command;
use ActiveCollab\JobsQueue\Queue\MySqlQueue;
use mysqli;

/**
 * @package ActiveCollab\JobQueue\Test\Commands
 */
abstract class AbstractCommandTest extends TestCase
{
    /**
     * @var mysqli
     */
    protected $link;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config_options = false;
    /**
     * @var command
     */
    protected $command;
    /**
     * @var command class name
     */
    protected $command_class_name;
    /**
     * @var config path
     */
    protected $config_path;
    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->config_path = dirname(__DIR__) . '/config/activecollab-jobs.json';
        $this->getConfigOptions();
        $this->link = new \MySQLi($this->config_options['db_host'], $this->config_options['db_user'], $this->config_options['db_pass'], $this->config_options['db_name']);

        if ($this->link->connect_error) {
            throw new \RuntimeException('Failed to connect to database. MySQL said: ' . $this->link->connect_error);
        }

        $this->connection = new Connection($this->link);
    }

    /**
     * Read and return configuration options
     *
     * @return array
     */
    protected function &getConfigOptions()
    {
        if ($this->config_options === false) {
            if (is_file($this->config_path)) {
                $this->config_options = json_decode(file_get_contents($this->config_path), true);
            } else {
                throw new \InvalidArgumentException("Config file not found at '$this->config_path'");
            }
        }
        return $this->config_options;
    }
    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        if ($this->link) {
            $this->link->close();
        }


        //$this->last_failed_job = $this->last_failure_message = null;

        parent::tearDown();
    }

    /**
     * Check number of records in jobs queue table
     *
     * @param integer $expected
     */
    protected function assertRecordsCount($expected)
    {
        $this->assertSame($expected, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `' . MySqlQueue::JOBS_TABLE_NAME . '`'));
    }

    /**
     * Check number of records in failed jobs queue table
     *
     * @param integer $expected
     */
    protected function assertFailedRecordsCount($expected)
    {
        $this->assertSame($expected, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `' . MySqlQueue::FAILED_JOBS_TABLE_NAME. '`'));
    }
}
