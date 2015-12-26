<?php

/**
 * Bootstrap command line application
 */

date_default_timezone_set('UTC');

defined('APP_PATH') or define('APP_PATH', dirname(dirname(__DIR__)));
define('JOBS_QUEUE_SCRIPT_TIME', microtime(true));

require APP_PATH . '/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application('ID', '1.0');//TODO select version

foreach (new DirectoryIterator(APP_PATH . '/src/Commands') as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $class_name = ('\\ActiveCollab\\JobQueue\\Commands\\' . $file->getBasename('.php'));

        if (!(new ReflectionClass($class_name))->isAbstract()) {

            $command = new $class_name;
            $application->add($command);
        }
    }
}
$application->run();