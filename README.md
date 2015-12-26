# Jobs Queue Commands

This package contains a couple of useful CLI commands that make work with `activecollab/jobsqueue` easier. Implemented commands are:

* `clear_failed_jobs` - Clear failed jobs losts
* `create_tables` - Create job queue tables (in case you are using MySQL queue)
* `failed_job_reasons` - Find job by type, and return all distinct reasons why it failed in the past
* `failed_jobs` - List content of failed jobs log
* `restore_failed_jobs` - Restore failed jobs by type
* `run_jobs` - Run jobs from the queue
* `run_queue_maintenance` - Perform queue maintenance (unstuck jobs, remove old logs etc)

## Running Jobs

This command is designed to be started and to execute jobs until a particular time limit is reached. This command supports following options:

* `--seconds` (`-s`) - How long should one call be kept alive and execute jobs (default value is 50 seconds),
* `--channels` (`-c`) - Channels that this command should pick jobs up from (default is `main` channel).

Check this example cron configuration:

```
*   *   *   *   *   /path/to/my/consumer run_jobs -s 180 -c main,mail
```

This command starts a consumer instance that listens on `mail` and `mail` channels every minute, and it is kept alive for 180 seconds. As a result, we always have three consumer instances running and executing jobs, which means that we can push quite a bit of work through them.

## DI Container

All commands expect that you provide them with `Interop\Container\ContainerInterface` DI container with following elements:

* `log` - Instance that implements `\Psr\Log\LoggerInterface` interface
* `dispatcher` - Instance that implements `\ActiveCollab\JobsQueue\DispatcherInterface` interface (usually `\ActiveCollab\JobsQueue\Dispatcher` instance)

Commands use these instances to perform the work that they need to do and DIC offers a convenient way to inject them.
