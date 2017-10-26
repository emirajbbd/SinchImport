<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Sinchimport\Controller\Adminhtml\Ajax;

class IndexingData extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;
    
    /**
     * Logging instance
     *
     * @var \Magebuzz\Sinchimport\Logger\Logger
     */
    protected $_logger;
    
    protected $_jsonEncoder;
    
    protected $sinch;
    
    protected $_directory;
    
    /**
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory            $layoutFactory
     * @param \Magento\Framework\Json\EncoderInterface         $jsonEncoder
     * @param \Magebuzz\Sinchimport\Logger\Logger              $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magebuzz\Sinchimport\Model\Sinch $sinch,
        \Magebuzz\Sinchimport\Logger\Logger $logger,
        \Magento\Framework\Filesystem\DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory     = $layoutFactory;
        $this->_jsonEncoder      = $jsonEncoder;
        $this->sinch             = $sinch;
        $this->_logger           = $logger;
        $this->_directory        = $directoryList;
    }
    
    /**
     * Category list suggestion based on already entered symbols
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->_logger->info('Start Full Import');
        
        /**
 * @var \Magento\Framework\Controller\Result\Json $resultJson
*/
        $resultJson = $this->resultJsonFactory->create();
        
        $rootDir = $this->_directory->getRoot() . '/';
        
        $php_run_string_array = explode(';', $this->sinch->php_run_strings);
        foreach ($php_run_string_array as $php_run_string) {
            exec(
                "nohup php " . $rootDir
                . "bin/magento sinch:url:generate > /dev/null & echo $!"
            );
        }
        
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
