<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogUpdate
{
    /**
     * @var \Celebros\Celexport\Model\Exporter
     */
    private $exporter;

    /**
     * @param \Celebros\Celexport\Model\Exporter $exporter
     */
    public function __construct(
        \Celebros\Celexport\Model\Exporter $exporter
    ) {
        $this->exporter = $exporter;
    }

    /**
     * @inheritDoc
     */
    public function execute($observer)
    {
        $this->exporter->export_celebros(false);
        return $this;
    }
}
