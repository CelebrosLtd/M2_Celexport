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

interface RemoteInterface
{
    /**
     * Send file to remote location
     *
     * @param array $config
     * @param string $filePath
     * @param string $remotePath
     * @return bool
     */
    public function send(
        array $config,
        string $filePath,
        string $remotePath
    ): bool;
}
