<?php

/**
 * Bootstrap command line application
 */

date_default_timezone_set('UTC');

defined('APP_PATH') or define('APP_PATH', dirname(dirname(__DIR__)));

require APP_PATH . '/vendor/autoload.php';

//use ActiveCollab\Id\Container;
use Symfony\Component\Console\Application;

$application = new Application('ID', '1.0');//TODO select version
/*
$container = new Container();
$container['settings'] = function() {
    $settings = include dirname(__DIR__) . '/settings.php';

    return $settings['settings'];
};*/
require_once dirname(__DIR__) . '/dependencies.php';

foreach (new DirectoryIterator(APP_PATH . '/src/Commands') as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $class_name = ('\\ActiveCollab\\JobQueue\\Commands\\' . $file->getBasename('.php'));

        if (!(new ReflectionClass($class_name))->isAbstract()) {

            $command = new $class_name;
            //$command->setContainer($container);
            $application->add($command);
        }
    }
}
$application->run();