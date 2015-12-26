<?php

namespace ActiveCollab\JobQueue\Test\Commands;

use ActiveCollab\JobsQueue\Dispatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use ActiveCollab\JobQueue\Commands\FailedJobReasons;
use Exception;

class FailedJobReasonsTest extends AbstractCommandTest
{
    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();
        $this->command =  new FailedJobReasons();
    }

    /**
     * Test search for not existing type
     */
    public function testExecuteNoJobFound(){
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('failed_job_reasons');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
            'type'          => 'not-existing-type-on-acctivecollab'
        ]);

        $this->assertRegExp('/No job type that matches type argument found under failed jobs/', $commandTester->getDisplay());

    }

    /**
     * Test if unexpected exception  is handel
     */
    public function testExecuteThrowErrorToDisplay(){

        $error_message = 'Expected test exception.';
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
                          ->disableOriginalConstructor()
                          ->setMethods(['unfurlType'])
                          ->getMock();

        $mockQueue->expects($this->any())
                  ->method('unfurlType')
                  ->will($this->throwException(new Exception($error_message)));

        /*$mockCommand = $this->getMockBuilder(get_class($this->command))
            ->setMethods(['getDispatcher'])
            ->getMock();
        $mockCommand->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue(new Dispatcher($mockQueue)));
        */
        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('failed_job_reasons');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
            'type'          => 'type_one'
        ]);
        $this->assertRegExp("/$error_message/", $commandTester->getDisplay());
    }
    /**
     * Test if more then one job is found
     */
    public function testExecuteThrowErrorOnMoreThenOneJobFound(){
        $mockQueue = $this->getMockBuilder('ActiveCollab\\JobsQueue\\Queue\\MySqlQueue')
            ->disableOriginalConstructor()
            ->setMethods(['unfurlType'])
            ->getMock();

        $mockQueue->expects($this->any())
            ->method('unfurlType')
            ->will($this->returnValue(['type1','type2']));

        $reflection = new \ReflectionClass($this->command);
        $parent = $reflection->getParentClass();
        $reflection_property = $parent->getProperty('dispatcher');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->command, new Dispatcher($mockQueue));
        $application = new Application();
        $application->add($this->command);
        $command = $application->find('failed_job_reasons');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'       => $command->getName(),
            '--config-path' => $this->config_path,
            'type'          => 'type_one'
        ]);
        $this->assertRegExp('/More than one job type found/', $commandTester->getDisplay());
    }

}