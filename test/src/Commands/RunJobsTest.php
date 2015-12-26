<?php

namespace ActiveCollab\JobQueue\Test\Commands;

use ActiveCollab\JobQueue\Command\RunJobs;

/**
 * @package ActiveCollab\JobQueue\Test\Commands
 */
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
