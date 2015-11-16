<?php

namespace ActiveCollab\JobQueue\Test\Commands;


use ActiveCollab\JobQueue\Commands\FailedJobs;
use ActiveCollab\JobsQueue\Dispatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Exception;

class FailedJobsTest extends AbstractCommandTest
{
    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();
        $this->command =  new FailedJobs();
    }

    /**
     * Test if command send friendly message when no job is found
     */
    public function testExecuteNoJobFound(){
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['failedJobStatistics'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('failedJobStatistics')
            ->will($this->returnValue([]));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('failed_jobs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);
        $this->assertRegExp('/No failed jobs found/', $commandTester->getDisplay());
    }

    /**
     * Test if unexpected exception from queue is handel
     */
    public function testExecuteThrowErrorOnQueueCall(){
        $error_message = 'Expected test exception.';
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['failedJobStatistics'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('failedJobStatistics')
            ->will($this->throwException(new Exception($error_message)));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('failed_jobs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);
        $this->assertRegExp("/$error_message/", $commandTester->getDisplay());

    }
    /**
     * Test data is displayed correctly
     */
    public function testExecuteDisplayCorrectResponse(){
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['failedJobStatistics'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('failedJobStatistics')
            ->will($this->returnValue([
                'type1' => [
                    '2.4.2015' => 3,
                    '2.5.2015' => 12377,
                    '2.6.2015' => 1,
                ],
                'type2' => [
                    '2.7.2015' => 91,
                ]
            ]));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('failed_jobs');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);
        $this->assertRegExp('/type1/', $commandTester->getDisplay());
        $this->assertRegExp('/2.4.2015/', $commandTester->getDisplay());
        $this->assertRegExp('/12377/', $commandTester->getDisplay());
        $this->assertRegExp('/91/', $commandTester->getDisplay());
    }
}