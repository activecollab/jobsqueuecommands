<?php

namespace ActiveCollab\JobQueue\Test\Commands;


use ActiveCollab\JobQueue\Command\JobsQueue;
use ActiveCollab\JobsQueue\Dispatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Exception;

class JobsQueueTest extends AbstractCommandTest
{
    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();
        $this->command =  new JobsQueue();
    }

    /**
     * Test if command send friendly message when no job is found
     */
    public function testExecuteNoJobFound(){
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['countJobsByType'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('countJobsByType')
            ->will($this->returnValue([]));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('jobs_queue');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);
        $this->assertRegExp('/No jobs in the queue/', $commandTester->getDisplay());

    }
    /**
     * Test if unexpected exception from queue is handel
     */
    public function testExecuteThrowErrorOnQueueCall(){
        $error_message = 'Expected test exception.';
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['countJobsByType'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('countJobsByType')
            ->will($this->throwException(new Exception($error_message)));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('jobs_queue');
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
            ->setMethods(['countJobsByType'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('countJobsByType')
            ->will($this->returnValue([
                'type1' => 123,
                'type2' => 345
            ]));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('jobs_queue');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
        ]);
        $this->assertRegExp('/type1/', $commandTester->getDisplay());
        $this->assertRegExp('/123/', $commandTester->getDisplay());
        $this->assertRegExp('/type2/', $commandTester->getDisplay());
        $this->assertRegExp('/345/', $commandTester->getDisplay());
    }
}