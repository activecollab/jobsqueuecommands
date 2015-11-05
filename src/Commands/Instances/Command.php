<?php

namespace ActiveCollab\JobQueue\Commands\Instances;

use ActiveCollab\JobQueue\Commands\Command as BaseCommand;
use ActiveCollab\ConfigFile\ConfigFile;
use DirectoryIterator;
use RuntimeException;

/**
 * @package ActiveCollab\ActiveCollabJobsConsumers\Instances
 */
abstract class Command extends BaseCommand
{
    /**
     * @var string
     */
    protected $classic_instances_path = '/var/www/activecollab/instances';

    /**
     * @var string
     */
    protected $feather_instances_path = '/var/www/feather/instances';

    /**
     * Return all Classic instances. Key is instance name, value is full instance path
     *
     * @return array
     */
    public function getClassicInstances()
    {
        $result = [];

        foreach (new DirectoryIterator($this->classic_instances_path) as $entry) {
            if (!$entry->isDot() && $entry->isDir() && file_exists($entry->getPathname() . '/config/config.php')) {
                $result[$entry->getBasename()] = $entry->getPathname();
            }
        }

        if (!empty($result)) {
            ksort($result);
        }

        return $result;
    }

    /**
     * Read and return APPLICATION_UNIQUE_KEY value for the given classic instance
     *
     * @param  string           $instance_name
     * @return string
     * @throws RuntimeException
     */
    public function getClassicApplicationUniqueKey($instance_name)
    {
        $config_file_path = $this->classic_instances_path . '/' . $instance_name . '/config/config.php';

        if (is_file($config_file_path)) {
            $application_unique_key = (new ConfigFile($config_file_path))->getOption('APPLICATION_UNIQUE_KEY');

            if (empty($application_unique_key)) {
                throw new RuntimeException("APPLICATION_UNIQUE_KEY not found in '$config_file_path'");
            }

            return $application_unique_key;
        } else {
            throw new RuntimeException("Config file not found at '$config_file_path'");
        }
    }

    /**
     * Return all Feather instances. Key is instance name, value is full instance path
     *
     * @return array
     */
    public function getFeatherInstances()
    {
        $result = [];

        foreach (new DirectoryIterator($this->feather_instances_path) as $entry) {
            if (!$entry->isDot() && $entry->isDir() && ctype_digit($entry->getBasename()) && file_exists($entry->getPathname() . '/config/config.php')) {
                $result[(integer) $entry->getBasename()] = $entry->getPathname();
            }
        }

        if (!empty($result)) {
            ksort($result);
        }

        return $result;
    }

    /**
     * Read and return APPLICATION_UNIQUE_KEY value for the given feather instance
     *
     * @param  integer          $instance_id
     * @return string
     * @throws RuntimeException
     */
    public function getFeatherApplicationUniqueKey($instance_id)
    {
        $config_file_path = $this->feather_instances_path . '/' . $instance_id . '/config/config.php';

        if (is_file($config_file_path)) {

            // Legacy, we have all configuration options in the file, as a list of constants
            if (strpos(file_get_contents($config_file_path), 'APPLICATION_UNIQUE_KEY')) {
                $application_unique_key = (new ConfigFile($config_file_path))->getOption('APPLICATION_UNIQUE_KEY');

                if (empty($application_unique_key)) {
                    throw new RuntimeException("APPLICATION_UNIQUE_KEY not found in '$config_file_path'");
                }

                return $application_unique_key;

            // On the fly configuration options
            } else {
                $output = [];
                $code = 0;

                exec("/usr/bin/php " . escapeshellarg($config_file_path) . " --on-the-fly-config-as-json");

                if (empty($code)) {
                    $json = json_decode(implode("\n", $output), true);

                    if (is_array($json) && isset($json['APPLICATION_UNIQUE_KEY'])) {
                        return $json['APPLICATION_UNIQUE_KEY'];
                    } else {
                        throw new RuntimeException("Failed to load Active Collab configuration options from '$config_file_path'");
                    }
                } else {
                    throw new RuntimeException("Failed to read on the fly configuration. Output: " . implode("\n", $output) . " Error code: $code");
                }
            }
        } else {
            throw new RuntimeException("Config file not found at '$config_file_path'");
        }
    }

    /**
     * Return ElasticSearch index name for the given instance
     *
     * @param  integer $instance_id
     * @return string
     */
    public function getSearchIndexName($instance_id)
    {
        return strtolower('active_collab_' . $this->getFeatherApplicationUniqueKey($instance_id));
    }
}
