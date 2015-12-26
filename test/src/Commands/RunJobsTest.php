<?php

namespace ActiveCollab\JobQueue\Test\Commands;

use ActiveCollab\JobQueue\Commands\RunJobs;
use ActiveCollab\JobsQueue\Dispatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Exception;


class RunJobsTest extends AbstractCommandTest
{
    /**
     * Set up test environment
     */
    public function setUp(){
        parent::setUp();
        $this->command =  new RunJobs();
    }

    public function testExecuteJobWellDone(){
        $this->assertTrue(true);//TODO
    }
}