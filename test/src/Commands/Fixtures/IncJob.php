<?php

namespace ActiveCollab\JobQueue\Test\Commands\Fixtures;

use ActiveCollab\JobsQueue\Jobs\Job;
use InvalidArgumentException;

/**
 * @package ActiveCollab\JobQueue\Test\Commands\Fixtures
 */
class IncJob extends Job
{
    /**
     * Construct a new Job instance
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if (!($data && is_array($data) && array_key_exists('number', $data) && is_int($data['number']))) {
            throw new InvalidArgumentException('Number is required and it needs to be an integer value');
        }

        parent::__construct($data);
    }

    /**
     * Increment a number
     *
     * @return integer
     */
    public function execute()
    {
        return $this->getData()['number'] + 1;
    }
}
