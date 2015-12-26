<?php

namespace ActiveCollab\JobQueue\Test\Commands;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use ActiveCollab\JobQueue\Command\ClearFailedJobs;
use ActiveCollab\JobsQueue\Dispatcher;
use Exception;

class ClearFailedJobsTest extends AbstractCommandTest
{
    public function setUp(){
        parent::setUp();
        $this->command =  new ClearFailedJobs();
    }

    /**
     * Test if execute will delete all records from failed job table
     */
    public function testExecuteRunsOK(){
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('clear_failed_jobs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'                    => $command->getName(),
            '--config-path'              => $this->config_path
        ]);

        $this->assertRegExp('/Done/', $commandTester->getDisplay());
        $this->assertFailedRecordsCount(0);
    }
    /**
     * Test if unexpected exception  is handel
     */
    public function testExecuteThrowErrorToDisplay(){

        $error_message = 'Expected test exception.';
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['clear'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('clear')
            ->will($this->throwException(new Exception($error_message)));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('clear_failed_jobs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path
        ]);
        $this->assertRegExp("/$error_message/", $commandTester->getDisplay());
    }
}