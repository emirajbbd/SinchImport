<?php

namespace SITC\Sinchimport\Model\Import;

class IndexManagement {
    /** @var \Magento\Framework\Indexer\StateInterfaceFactory $stateFactory */
    private $stateFactory;
    /** @var \Magento\Framework\Indexer\ConfigInterface $indexerConfig */
    private $indexerConfig;

    /** @var \SITC\Sinchimport\Helper\Data $helper */
    private $helper;

    public function __construct(
        \Magento\Framework\Indexer\StateInterfaceFactory $stateFactory,
        \Magento\Framework\Indexer\ConfigInterface $indexerConfig,
        \SITC\Sinchimport\Helper\Data $helper
    ){
        $this->stateFactory = $stateFactory;
        $this->indexerConfig = $indexerConfig;
        $this->helper = $helper;
    }

    /**
     * Ensure no indexers are running, this should be run at the start of the import
     * Will wait for index completion if sinchimport/general/wait_for_index_completion is true
     * 
     * @return bool True if no indexers are currently in the "working" state
     */
    public function ensureIndexersNotRunning()
    {
        $waitForIndexers = $this->helper->getStoreConfig('sinchimport/general/wait_for_index_completion');
        if($waitForIndexers) {
            $this->waitForIndexCompletion();
        }
        return $this->noIndexersRunning();
    }
    
    /**
     * Check the state of the indexers
     * Returns true if all indexers are NOT running (none in state "working") and false otherwise
     * 
     * @return bool
     */
    private function noIndexersRunning()
    {
        foreach(array_keys($this->indexerConfig->getIndexers()) as $indexerId) {
            $indexerState = $this->stateFactory->create();
            $indexerState->loadByIndexer($indexerId);
            if ($indexerState->getStatus() == \Magento\Framework\Indexer\StateInterface::STATUS_WORKING) {
                return false;
            }
        }
        return true;
    }

    /**
     * Doesn't return until the none of the indexers are in the "working" state
     * 
     * @return void
     */
    private function waitForIndexCompletion()
    {
        while(!$this->noIndexersRunning()) {
            sleep(5);
        }
    }
}