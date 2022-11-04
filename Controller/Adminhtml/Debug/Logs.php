<?php
/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\Celexport\Controller\Adminhtml\Debug;

use Magento\Framework\App\Filesystem\DirectoryList;

class Logs extends \Celebros\Celexport\Controller\Adminhtml\Debug
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Celebros\Celexport\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->helper = $helper;
    }

    public function execute()
    {
        if ($fileName = $this->getRequest()->getParam('filename', false)) {
            $filePath = $this->helper->getExportPath() . '/' . $fileName;
            $content = file_get_contents($filePath);

            return $this->fileFactory->create(
                $fileName,
                $content,
                DirectoryList::TMP
            );
        }
    }
}
