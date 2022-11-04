<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Celexport\Model\Config\Source;

use Celebros\Celexport\Model\Config\Source\Indexers;

class Events implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @param array events
     * @return void
     */
    public function __construct(
        array $events = []
    ) {
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $events = [];
        foreach ($this->events as $eventsList) {
            $events = array_merge($events, $eventsList->toOptionArray());
        }

        return $events;
    }
}
