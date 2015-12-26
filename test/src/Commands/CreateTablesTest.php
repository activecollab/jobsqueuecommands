<?php
namespace ActiveCollab\JobQueue\Test\Commands;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use ActiveCollab\JobQueue\Command\CreateTables;
use ActiveCollab\JobsQueue\Queue\MySqlQueue;

/**
 * @package ActiveCollab\JobQueue\Test\Commands
 */
class CreateTablesTest extends AbstractCommandTest
{
    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();

        $this->command =  new CreateTables();
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
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);

        $this->assertRegExp('/Tables created/', $commandTester->getDisplay());
        $this->assertDBTableExists(MySqlQueue::JOBS_TABLE_NAME);
        $this->assertDBTableExists(MySqlQueue::FAILED_JOBS_TABLE_NAME);
        $this->assertDBTableExists('email_log');
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('email_log')) {
            $this->connection->dropTable('email_log');
        }

        parent::tearDown();
    }

    /**
     * Check if table exists in database
     *
     * @param $table_name
     */
    private function assertDBTableExists($table_name){
        $this->assertSame('1', $this->connection->executeFirstCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = '" . $this->config_options['db_name'] . "' and table_name ='" . $table_name . "'"));
    }
}
