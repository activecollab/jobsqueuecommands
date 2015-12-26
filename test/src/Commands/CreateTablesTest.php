<?php
namespace ActiveCollab\JobQueue\Test\Commands;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use ActiveCollab\JobQueue\Commands\CreateTables;
use ActiveCollab\JobsQueue\Queue\MySqlQueue;

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
            'command'                    => $command->getName(),
            '--config-path'              => $this->config_path,
        ]);

        $this->assertRegExp('/Tables created/', $commandTester->getDisplay());
        $this->assertDBTableExists(MySqlQueue::TABLE_NAME);
        $this->assertDBTableExists(MySqlQueue::TABLE_NAME_FAILED);
        $this->assertDBTableExists('email_log');
    }

    /**
     * Check if table exists in database
     * @param $table_name
     */
    private function assertDBTableExists($table_name){
        $this->assertSame('1', $this->connection->executeFirstCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = '" . $this->config_options['db_name'] . "' and table_name ='" . $table_name . "'"));
    }
    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `email_log`');
        parent::tearDown();
    }
}