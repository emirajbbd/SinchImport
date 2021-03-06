<?php

namespace SITC\Sinchimport\Controller\Adminhtml\Ajax;

class IndexingData extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * Logging instance
     *
     * @var \SITC\Sinchimport\Logger\Logger
     */
    protected $_logger;
    
    protected $_jsonEncoder;
    
    protected $sinch;
    
    protected $_directory;
    
    /**
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Json\EncoderInterface         $jsonEncoder
     * @param \SITC\Sinchimport\Logger\Logger                  $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \SITC\Sinchimport\Model\Sinch $sinch,
        \SITC\Sinchimport\Logger\Logger $logger,
        \Magento\Framework\Filesystem\DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_jsonEncoder      = $jsonEncoder;
        $this->sinch             = $sinch;
        $this->_logger           = $logger;
        $this->_directory        = $directoryList;
    }
    
    /**
     * Index data
     */
    public function execute()
    {
        $this->_logger->info('Start Index process and URL generation');

        $resultJson = $this->resultJsonFactory->create();
        
        $rootDir = $this->_directory->getRoot() . '/';
        
    
            exec(
                "nohup php " . $rootDir
                . "bin/magento sinch:url:generate > /dev/null & echo $!"
            );
        
        
        $result = ['success' => true];
        
        return $resultJson->setJsonData($this->_jsonEncoder->encode($result));
    }
    
    /**
     * Check if admin has permissions to visit related pages
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
