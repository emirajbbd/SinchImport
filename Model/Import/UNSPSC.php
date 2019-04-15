<?php

namespace SITC\Sinchimport\Model\Import;

class UNSPSC {

    const ATTRIBUTE_NAME = "unspsc";
    const PRODUCT_PAGE_SIZE = 50;

    private $hasParseRun = false;

    private $resourceConn;
    private $cacheType;
    private $massProdValues;

    private $logger;

    private $productTempTable;
    private $cpeTable;

    /**
     * Mapping of UNSPSC -> [Sinch Product ID]
     */
    private $mapping = [];

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConn,
        \Magento\Framework\App\Cache\TypeListInterface $cacheType,
        \Magento\Catalog\Model\ResourceModel\Product\Action $massProdValues
    ){
        $this->resourceConn = $resourceConn;
        $this->cacheType = $cacheType;
        $this->massProdValues = $massProdValues;

        $this->productTempTable = $this->resourceConn->getTableName('products_temp');
        $this->cpeTable = $this->resourceConn->getTableName('catalog_product_entity');

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/sinch_unspsc.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->logger = $logger;
    }

    public function parse()
    {
        $this->logger->info("--- Begin UNSPSC Mapping ---");

        $unspsc_values = $this->getConnection()->query("SELECT DISTINCT unspsc FROM {$this->productTempTable} WHERE unspsc IS NOT NULL");
        foreach($unspsc_values as $unspsc){
            //List of Sinch products with the specified UNSPSC value
            $sinch_ids = $this->getConnection()->query(
                "SELECT store_product_id FROM {$this->productTempTable} WHERE unspsc = :unspsc",
                [":unspsc" => $unspsc]
            );

            $this->mapping[$unspsc] = $sinch_ids;
        }

        $this->hasParseRun = true;
        $this->logger->info("--- Completed UNSPSC mapping ---");
    }

    public function apply()
    {
        if(!$this->hasParseRun) {
            $this->logger->info("Not applying UNSPSC values as parse hasn't run");
            return;
        }
        
        $this->logger->info("--- Begin applying UNSPSC values ---");
        $applyStart = $this->microtime_float();

        $valueCount = count($this->mapping);
        $currIter = 0;

        foreach($this->mapping as $unspsc => $sinch_ids){
            $currIter += 1;

            $entityIds = $this->sinchToEntityIds($sinch_ids);
            if($entityIds === false){
                $this->logger->err("Failed to retreive entity ids");
                throw new \Magento\Framework\Exception\StateException(__("Failed to retrieve entity ids"));
            }

            $productCount = count($entityIds);
            $this->logger->info("({$currIter}/{$valueCount}) Setting UNSPSC to {$unspsc} for {$productCount} products");

            $this->massProdValues->updateAttributes(
                $entityIds, 
                [self::ATTRIBUTE_NAME => $unspsc],
                0 //store id (dummy value as they're global attributes)
            );
        }
        
        //Flush EAV cache
        $this->cacheType->cleanType('eav');

        $elapsed = $this->microtime_float() - $applyStart;
        $this->logger->info("--- Completed applying UNSPSC values in {$elapsed} seconds");
    }

    /**
     * Convert Sinch Product IDs to Product Entity IDs
     * 
     * @param int[] $sinch_prod_ids Sinch Product IDs
     * @return int[] Product Entity IDs
     */
    private function sinchToEntityIds($sinch_prod_ids)
    {
        $placeholders = implode(',', array_fill(0, count($sinch_prod_ids), '?'));
        $entIdQuery = $this->getConnection()->prepare(
            "SELECT entity_id FROM {$this->cpeTable} WHERE sinch_product_id IN ($placeholders)"
        );
        $entIdQuery->execute($sinch_prod_ids);
        return $entIdQuery->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    private function getConnection()
    {
        return $this->resourceConn->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
    }
}