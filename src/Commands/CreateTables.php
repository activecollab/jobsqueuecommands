<?php

namespace ActiveCollab\JobQueue\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * @package ActiveCollab\JobQueue\Commands
 */
class CreateTables extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('create_tables')->setDescription('Create tables that are needed for MySQL queue to work');
    }

    /**
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $connection = $this->getDatabaseConnection($input);

            $connection->execute("CREATE TABLE IF NOT EXISTS  `jobs_queue` (
              `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
              `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ApplicationObject',
              `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
              `priority` int(5) unsigned DEFAULT NULL,
              `data` text COLLATE utf8mb4_unicode_ci,
              `available_at` datetime DEFAULT NULL,
              `reservation_key` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `reserved_at` datetime DEFAULT NULL,
              `attempts` int(5) unsigned DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `reservation_key` (`reservation_key`),
              KEY `type` (`type`),
              KEY `priority` (`priority`),
              KEY `reserved_at` (`reserved_at`),
              KEY `instance_id` (`instance_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $connection->execute("CREATE TABLE IF NOT EXISTS `jobs_queue_failed` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ApplicationObject',
              `data` text COLLATE utf8mb4_unicode_ci,
              `failed_at` datetime DEFAULT NULL,
              `reason` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
              PRIMARY KEY (`id`),
              KEY `type` (`type`),
              KEY `failed_at` (`failed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $connection->execute("CREATE TABLE IF NOT EXISTS  `email_log` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
              `parent_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `parent_id` int(10) unsigned DEFAULT NULL,
              `sender` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `recipient` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `sent_on` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `message_id` (`message_id`),
              KEY `instance_id` (`instance_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return $this->success('Tables created', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}