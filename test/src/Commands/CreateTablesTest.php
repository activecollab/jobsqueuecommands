<?php

namespace ActiveCollab\JobQueue\Test\Commands;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use ActiveCollab\JobQueue\Command\CreateTables;
use ActiveCollab\JobsQueue\Queue\MySqlQueue;

/**
 * @package ActiveCollab\JobQueue\Test\Commands
 */
class CreateTablesTest extends TestCase
{
    /**
     * @var CreateTables
     */
    private $command;

    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();

        $this->command =  new CreateTables();
        $this->command->setContainer($this->container);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        foreach ([MySqlQueue::BATCHES_TABLE_NAME, MySqlQueue::JOBS_TABLE_NAME, MySqlQueue::FAILED_JOBS_TABLE_NAME] as $table_name) {
            if ($this->connection->tableExists($table_name)) {
                $this->connection->dropTable($table_name);
            }
        }

        parent::tearDown();
    }

    /**
     * Test if create db script is run correctly
     */
    public function testExecuteRunsOK(){
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('create_tables');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertRegExp('/Tables created/', $commandTester->getDisplay());

        $this->assertTrue($this->connection->tableExists(MySqlQueue::BATCHES_TABLE_NAME));
        $this->assertTrue($this->connection->tableExists(MySqlQueue::JOBS_TABLE_NAME));
        $this->assertTrue($this->connection->tableExists(MySqlQueue::FAILED_JOBS_TABLE_NAME));
        $this->assertTrue($this->connection->tableExists('email_log'));
    }
}
