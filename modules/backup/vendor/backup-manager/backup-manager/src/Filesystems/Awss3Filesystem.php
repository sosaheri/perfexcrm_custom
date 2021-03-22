<?php namespace BackupManager\Filesystems;

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem as Flysystem;

/**
 * Class Awss3Filesystem
 * @package BackupManager\Filesystems
 */
class Awss3Filesystem implements Filesystem {

    /**
     * @param $type
     * @return bool
     */
    public function handles($type) {
        return strtolower($type) == 'awss3';
    }

    /**
     * @param array $config
     * @return \League\Flysystem\Filesystem
     */
    public function get(array $config) {
        $client = S3Client::factory([
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
            'region' => $config['region'],
            'version' => isset($config['version']) ? $config['version'] : 'latest',
        ]);

        return new Flysystem(new AwsS3Adapter($client, $config['bucket'], $config['root']));
    }
}
