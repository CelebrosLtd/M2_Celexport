<?php
namespace Celebros\Celexport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    /**
     * @var \Celebros\Celexport\Model\Exporter
     */
    protected $exporter;
    
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;
    
    /**
     * @param \Celebros\Celexport\Model\Exporter $exporter
     */
    public function __construct(
        \Celebros\Celexport\Model\Exporter $exporter,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->exporter = $exporter;
        $this->_objectManager = $objectManager;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setName('celebros:export:export')
            ->setDescription('Celebros Export Process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->exporter->export_celebros($this->_objectManager, false);
        return $this;
    }
}
