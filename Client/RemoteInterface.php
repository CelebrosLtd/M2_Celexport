<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 ******************************************************************************
 * @category    Celebros
 * @package     Celebros_Celexport
 */

namespace Celebros\Celexport\Client;

interface RemoteInterface
{
    /**
     * Send file to remote location
     *
     * @param string $filename
     * @param int $mode
     * @return bool
     */
    public function send(
        array $config,
        string $filePath,
        string $remotePath
    );
}
