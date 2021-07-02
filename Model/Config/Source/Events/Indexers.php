<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_Celexport
 */

namespace Celebros\Celexport\Model\Config\Source\Events;

use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollection;

class Indexers implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var IndexerCollection
     */
    protected $indexerCollectionFactory;

    /**
     * @param IndexerCollection $indexerCollectionFactory
     * @return void
     */
    public function __construct(
        IndexerCollection $indexerCollectionFactory
    ) {
        $this->indexerCollectionFactory = $indexerCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $array = $this->toArray();
        $options = array_map(
            function ($value, $label) {
                return ['value' => $value, 'label' => $label];
            },
            array_keys($array),
            $array
        );

        return $options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        $indexers = $this->indexerCollectionFactory->create()->getItems();
        $indexers = array_combine(
            array_map(
                function ($item) {
                    return $item->getId();
                },
                $indexers
            ),
            $indexers
        );
        foreach ($indexers as $indexer) {
            $result[$indexer->getId()] = $indexer->getTitle() . ' Indexer';
        }

        return $result;
    }
}
