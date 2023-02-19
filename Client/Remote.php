<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Client;

use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\AbstractIo;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class Remote implements RemoteInterface
{
    /**
     * @var array
     */
    protected $portMapping = [
        22 => 'sftp',
        21 => 'ftp',
        990 => 'ftp'
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * Send file to remote location
     *
     * @param array $config
     * @param string $filePath
     * @param string|null $remotePath
     * @return bool
     * @throws LocalizedException
     */
    public function send(
        array $config,
        string $filePath,
        string $remotePath = null
    ): bool {
        $this->config = $config;
        $type = $this->getRemoteType();
        $methodName = 'sendT' . $type;
        if (!$type || !method_exists($this, $methodName)) {
            throw new LocalizedException(new Phrase('Type or method for type is not exist.'));
        }

        return $this->$methodName($filePath, $remotePath);
    }

    /**
     * Detect protocol type
     *
     * @return string
     * @throws LocalizedException
     */
    protected function getRemoteType(): string
    {
        $type = $this->config['type'] ?? null;
        if (!$type) {
            $port = $this->config['port'] ?? null;
            if (!$port || !($type = $this->portMapping[$port] ?? null)
            ) {
                throw new LocalizedException(new Phrase('Type or port fields are not exist in config'));
            }
        }

        return $type;
    }

    /**
     * Send file over SFTP
     *
     * @param string $filePath
     * @param string|null $remotePath
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function sendTsftp(
        string $filePath,
        string $remotePath = null
    ): bool {
        $connection = new Sftp();
        return $this->sendFile($connection, $filePath, $remotePath);
    }

    /**
     * Send File over FTP
     *
     * @param string $filePath
     * @param string|null $remotePath
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function sendTftp(
        string $filePath,
        string $remotePath = null
    ): bool {
        $connection = new Ftp();
        return $this->sendFile($connection, $filePath, $remotePath);
    }

    /**
     * Send file
     *
     * @param AbstractIo $connection
     * @param string $filePath
     * @param string|null $remotePath
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function sendFile(
        AbstractIo $connection,
        string $filePath,
        string $remotePath = null
    ): bool {
        $connection->open([
            'host' => $this->config['host'] ?? null,
            'username' => $this->config['user'] ?? null,
            'user' => $this->config['user'] ?? null,
            'password' => $this->config['password'] ?? null,
            'passive' => $this->config['passive'] ?? null,
            'ssl' => $this->config['ssl'] ?? null
        ]);

        $fileDriver = new FileDriver();
        if ($fileDriver->isExists($filePath) && $fileDriver->isReadable($filePath)) {
            $file = new File();
            $fileInfo = $file->getPathInfo($filePath);
            $result = $connection->write($fileInfo['basename'], $filePath);

            $connection->close();

            return (bool) $result;
        }

        return false;
    }
}
