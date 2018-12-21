<?php
/**
 * Module to import/update products
 * Copyright (C) 2018  John Park
 * 
 * This file is part of Onlinepromo/Sync and provides a way to download a csv file
 * from a FTP site and create or update products within magento.
 * 
 * Onlinepromo/Sync is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Onlinepromo\Sync\Model;

use Magento\ImportExport\Model\Import as MagentoImport;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Onlinepromo\Sync\Helper\Sync;

/**
 * Class Import
 * @package Onlinepromo\Sync\Model
 */
class Import
{
    /**
     * @var string name of the product data table for importing csv too.
     */
    const PRODUCTS_TMP_TABLE = 'sync_products';
    /**
     * @var string name of the product data row hash for caching product information.
     */    
    const PRODUCTS_UPDATED_CACHE = 'sync_control_products';
    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory
     */
    private $indexerCollectionFactory;
    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;
    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $scopeConfig;
    /**
     * @var \Onlinepromo\Sync\Helper\Config
     */    
    protected $config;
    /**
     * @var \Magento\Framework\Filesystem\Io\Ftp
     */    
    protected $ftp;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */    
    protected $encryptor;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */    
    protected $directoryList;
    /**
     * @var \Magento\Framework\Model\ResourceModel\Iterator
     */    
    protected $iterator;
    /**
     * @var \Onlinepromo\Sync\Logger\Logger
     */    
    protected $logger;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */    
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Model\Product
     */    
    protected $product;
    /**
     * @var integer
     */
    private $_stockChunkNo = 1;
    /**
     * @var integer
     */
    private $_chunkNo = 1;

    public function __construct(
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onlinepromo\Sync\Helper\Config $config,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Filesystem\Io\Ftp $ftp,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\Iterator $iterator,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productRepository,
        \Onlinepromo\Sync\Model\ResourceModel\Products\CollectionFactory $productOpRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productApiRepository,
        \Magento\Catalog\Model\Product $product,
        \Onlinepromo\Sync\Logger\Logger $logger,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry    
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->ftp = $ftp;
        $this->directory_list = $directoryList; 
        $this->resourceConnection = $resourceConnection;
        $this->iterator = $iterator;
        $this->logger = $logger;
        $this->productOpRepository = $productOpRepository;
        $this->productRepository = $productRepository;
        $this->productApiRepository = $productApiRepository;
        $this->product = $product;
        $this->indexerFactory = $indexerFactory;
        $this->directoryList = $directoryList;
        $this->stockRegistry = $stockRegistry;
        
        $this->ftp = new \FtpClient\FtpClient();
        $this->ftp->connect($this->config->getFtpHost(), false, 21, 6000);
        $this->ftp->login($this->config->getFtpUser(), $this->encryptor->decrypt($this->config->getFtpPass()));
        $this->ftp->pasv(true);

    }

    /**
     * Download file using FTP and update magento
     * 
     */
    public function run()
    {
        $this->logger->info("sync started  : ". date('H:i:s'));
        // download and import file data into the temp product table
        $this->logger->info("== Importing products into temporary table ==");
        $this->importProductsIntoTmpTable();
        // delete excluded products set in system config.
        $this->logger->info("== Deleting excluded products");
        $this->deleteExcludedProductsFromTmpTable();
        // check hash of the rows and delete if the products are the same
        $this->logger->info("== Delete unchanged products from tmp table ==");
        $this->deleteUnchangedProductsFromTmpTable();
        // update the products
        $this->importProducts();
        // run indexes
        $this->runIndexers();
        $this->logger->info("sync finished  : ". date('H:i:s'));
	}    

    /**
     * Import csv into temp sync product table
     * 
     * @return boolean
     */
    protected function importProductsIntoTmpTable()
    {
        $path = $this->getFile($this->config->getFtpFile());
        $query = sprintf(
            'LOAD DATA LOCAL INFILE "%s" REPLACE INTO TABLE %s FIELDS TERMINATED BY "|" LINES TERMINATED BY "\r\n" IGNORE 1 LINES (%s);',
            $path,
            self::PRODUCTS_TMP_TABLE,
            'sku,product_name,short_description,long_description,attribute_set_id,status,visibility,tax_class_id,price,qty,weight,package_contents'
        );
        try {
            $this->logger->error("> Importing into: ".self::PRODUCTS_TMP_TABLE);
            $this->_getWriteConnection()->query(sprintf("TRUNCATE table %s", self::PRODUCTS_TMP_TABLE));            
            $this->_getWriteConnection()->query($query);
        } catch (Exception $e) {
            $this->logger->error("importProductsIntoTmpTable error " .$e->getMessage());
            die;
        }
        return true;
    }

    /**
     * Delete excluded products set in systme config from temp sync product table
     *
     */
    protected function deleteExcludedProductsFromTmpTable()
    {
        if ($this->config->getFtpExcludedSkus() != '') {
            $query = sprintf('DELETE FROM %1$s WHERE `sku` in ("%2$s")',
                self::PRODUCTS_TMP_TABLE,
                $this->config->getFtpExcludedSkus()
            );
            try {
                $this->_getWriteConnection()->query($query);
            } catch (Exception $e) {
                $this->logger->error("deleteExcludedSkuproducts error " .$e->getMessage());
                die;            
            }
        }
        return true;
    }

    /**
     * Delete unchanged products using hash from temp sync product table
     *
     */
    public function deleteUnchangedProductsFromTmpTable()
    {
        try {
            $joinCondition = sprintf('bicp.sku=bip.sku AND bicp.control_hash = %s',
                $this->_getRowHashQuery('bip'));

            $conditionQuery = $this->_getReadConnection()->select()
                ->from(array('bip' => self::PRODUCTS_TMP_TABLE))
                ->join(array('bicp' => self::PRODUCTS_UPDATED_CACHE), $joinCondition)
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(array('bip.sku'));
            $this->_getWriteConnection()->delete(
                self::PRODUCTS_TMP_TABLE,
                $this->_getWriteConnection()->quoteInto('sku IN(SELECT tbl1.sku FROM (?) as tbl1)', new \Zend_Db_Expr($conditionQuery))
            );
        } catch (Exception $e) {
                $this->logger->error("deleteUnchangedProductsFromTmpTable error " .$e->getMessage());
                die;              
        }
    }

    /**
     * Create product collection from temp sync product table
     *
     */
    public function importProducts()
    {
        $this->logger->info("== Sync Products ==");

        $collection = $this->productOpRepository->create();
        $rowHashQuery = $this->_getRowHashQuery('main_table', 'rowhash');
        $collection->getSelect()->columns($rowHashQuery);
        $countCollection = $this->productOpRepository->create();
        $productsCount = $countCollection->getSize();

        $this->logger->info("> count: " . $productsCount);

        $this->iterator->walk(
            $collection->getSelect(),
            array(array($this, 'importProductCallback')),
            array('productCount' => $productsCount)
        );
    }

    /**
     * Create product chunck
     *
     * @param array $args
     * @SuppressWarnings(PHPMD)
     */
    public function importProductCallback($args)
    {
        $row = $args['row'];
        $idx = $args['idx'];
        $totalCount = $args['productCount'];

        $short_description = $this->_parseRowValue($row, 'short_description', $this->config->getFtpDefaultDescription());
        $long_description = $this->_parseRowValue($row, 'long_description', $this->config->getFtpDefaultDescription());

        //  TODO : do we want some way to bulk discount?
        $price = $this->_parseRowValue($row, 'price', "99999999");

        // set the products rows array up here with defaults if not set.
        $product = array(
            'description' => empty($long_description) ? $this->config->getFtpDefaultDescription() : $long_description,
            'attribute_set_id' => $this->_parseRowValue($row, 'attribute_set_id'),
            'short_description' => empty($short_description) ? $this->config->getFtpDefaultDescription() : $short_description,
            'product_websites' => 'base',
            'status' => $this->_parseRowValue($row, 'status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED),
            'visibility' => $this->_parseRowValue($row, 'visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH),
            'tax_class_id' => $this->_parseRowValue($row, 'tax_class_id', 2),
            'sku' => $this->_parseRowValue($row, 'sku'),
            'type' => 'simple',
            'name' => $this->_parseRowValue($row, 'product_name'),
            'price' => $price,
            'qty' => $this->_parseRowValue($row, 'qty', 0),
            'weight' => $this->_parseRowValue($row, 'weight', 1),
            'rowhash' => $this->_parseRowValue($row, 'rowhash', '123'),
            'package_contents' => $this->_parseRowValue($row, 'package_contents', $this->config->getFtpDefaultPackageContents()),
        );

        $this->_productsForImportTmp[] = $product;
 
        if ((count($this->_productsForImportTmp) >= $this->config->getFtpMaxProductImportChunk())||($idx == $totalCount - 1)) {
            $this->logger->info(sprintf('Starting import chunk %s', $this->_chunkNo) . PHP_EOL);
            $this->_chunkNo++;
            //Send data to importer and clear array
            $this->importProductsChunk($this->_productsForImportTmp);
            $this->_productsForImportTmp = array();
        }
    }

    /**
     * Take an array from the product chunk and update/ create the product
     * @SuppressWarnings(PHPMD)
     *
     * @param array $products
     */
    public function importProductsChunk($products)
    {
        if ((!$products)||(!is_array($products)||(!count($products)))) {
            return;
        }

        try {

            $productsCreated = 0;
            $productsFound = 0;
            $productsCreationErrors = 0;
            foreach ($products as $item) {
                try {
                    $product = $this->productApiRepository->get($item['sku'], true, 0);
                    $productsFound++;

                    $this->logger->info("[importProductsChunk] try to update : ".$item['sku']);

                    // check the item array from csv against the exisiting product data and update if required.
                    $productModified = false;
                    foreach ($item as $attCodeToCheck => $productArrayValue) {
                        
                        $attCodeToCheck = ltrim($attCodeToCheck, '_');

                        switch ($attCodeToCheck) {
                            // lets not update the description if its shorter
                            case 'long_description':
                                 if ($product->getData('long_description') != $item['long_description']) {
                                    if ( strlen($product->getData($attCodeToCheck)) < strlen($item['long_description']) ) {
                                        $product->setDescription($item['long_description']);
                                        $this->logger->info('== changing '.$attCodeToCheck);
                                        $productModified = true;
                                    }
                                 } 
                                break;
                            // data we dont want to update                             
                            case 'product_websites':
                            case 'type':
                            case 'rowhash':
                            case 'qty': 
                                break;
                            // check all other attributes here. never update with blank.
                            default:
                                if (($product->getData($attCodeToCheck) != $productArrayValue)&&($productArrayValue!='')) {
                                    $this->logger->info('== changing "'.$attCodeToCheck. '" from "'.$product->getData($attCodeToCheck).'" to "'.$productArrayValue);
                                    $product->setData($attCodeToCheck,$productArrayValue);
                                    $productModified = true;
                                }
                                break;
                        }
                    }

                    // save the product if its been changed and update the row hash in cache table
                    if ($productModified) {
                        try {
                            $product->setUrlKey($this->createUrlKey($item['name'], $item['sku']));
                            $product->save();
                            $this->logger->info("[importProductsChunk] Product updated: ".$item['sku']." mage_id ".$product->getId()); 
                            // save the row hash so its skipped next time.
                            $this->_getWriteConnection()->insertOnDuplicate(
                                $this->_getTableName(self::PRODUCTS_UPDATED_CACHE),
                                array(
                                    'sku' => $item['sku'],
                                    'control_hash' => $item['rowhash'],
                                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                                )
                            );                            
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $this->logger->info('[importProductsChunk] could not add product : ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
                            $this->logger->error(" ** ". $e->getMessage());
                        }                        
                    } else {
                        echo "= product not saved (nothing modified) " . $item['sku'] ."\n";
                        $this->_getWriteConnection()->insertOnDuplicate(
                            $this->_getTableName(self::PRODUCTS_UPDATED_CACHE),
                            array(
                                'sku' => $item['sku'],
                                'control_hash' => $item['rowhash'],
                                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                            )
                        );                          
                    }

                    // stock updates
                    $stockItem = $this->stockRegistry->getStockItem($product->getId());
                    try {
                        $stockItem->setQty($item['qty']);
                        $oos = $item['qty'] > 0 ? 1 : 0;
                        $stockItem->setIsInStock($oos);
                        $this->stockRegistry->updateStockItemBySku($item['sku'], $stockItem);
                        $this->logger->info("[importStockChunk] ". $item['sku'] . " saved");
                        echo "[importStockChunk] ". $item['sku'] . " saved\n";                          
                    } catch (Exception $e) {
                        $this->logger->info( " * [importStockChunk] cant save stock item ". $e->getMessage());
                    }

                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {

                    // we cant find the product so add it here.
                    if ($item['sku']) {

                        try {
                            $this->logger->info("[importProductsChunk] Creating Product : ".$item['sku']);
                            $oos = $item['qty'] > 0 ? 1 : 0;
                            $product = $this->product;
                            $product->setId(null);
                            $product->setSku($item['sku']); 
                            $product->setName($item['name']); 
                            $product->setAttributeSetId($item['attribute_set_id']); 
                            $product->setStatus($item['status']); 
                            $product->setUrlKey($item['sku']);
                            $product->setWeight($item['weight']); 
                            $product->setVisibility($item['visibility']); 
                            $product->setTaxClassId($item['tax_class_id']);
                            $product->setTypeId('simple'); 
                            $product->setPrice($item['price']);
                            $product->setWebsiteIds([1]);
                            $product->setPackageContents($item['package_contents']);

                            $product->setStockData(
                                                    array(
                                                        'use_config_manage_stock' => 0,
                                                        'manage_stock' => 1,
                                                        'is_in_stock' => $oos,
                                                        'qty' => $item['qty']
                                                    )
                                                );

                            $errors = $product->validate();
                            if (is_array($errors)) { 
                                $this->logger->error($errors);
                            } 
                            $product->save();
                            $this->logger->info("[importProductsChunk] Saved : ".$item['sku']);
                            $productsCreated++;  
                            $this->_getWriteConnection()->insertOnDuplicate(
                                $this->_getTableName(self::PRODUCTS_UPDATED_CACHE),
                                array(
                                    'sku' => $item['sku'],
                                    'control_hash' => $item['rowhash'],
                                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                                )
                            );                             
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $this->logger->error('[importProductsChunk]  ---: ' . $e->getMessage());
                        }
                    }
                }
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->info('[importProductsChunk]  ---: ' . $e->getMessage());
            $productsCreationErrors++;
        }
        $this->logger->info("Products Found: ".$productsFound);
        $this->logger->info("Products Created: ".$productsCreated);
        $this->logger->info("Products Creation Errors: ".$productsCreationErrors);
    }

    /**
     * Create product url path from name and sku
     *
     * @param   string $title
     * @param   string $sku  
     * @return  string url_path
     */
    public function createUrlKey($title, $sku) 
    {
        $url = preg_replace('#[^0-9a-z]+#i', '-', $title);
        $urlKey = strtolower($url);
        return $urlKey . '-' . $sku;
    }

    /**
     * Download file to server by name and retrun file path
     *
     * @param   string $file filename  
     * @return  string file path
     */
    private function getFile($file) {
        $this->logger->info("> Downloading: ".$file);
        $this->ftp->chdir('products');
        $this->ftp->get($this->directory_list->getPath('var').'/sync/'.$file, $file, FTP_BINARY);
        return $this->directory_list->getPath('var') .'/sync/'.$file;
    }

    /**
     * Get database read connection
     *
     * @return  Magento\Framework\App\ResourceConnection
     */
    protected function _getWriteConnection()
    {
        return $this->resourceConnection->getConnection('core_write');
    }

    /**
     * Get database read connection
     *
     * @return  Magento\Framework\App\ResourceConnection
     */
    protected function _getReadConnection()
    {
        return $this->resourceConnection->getConnection('core_read');
    }

    /**
     * Get resource table name
     *
     * @param   string $name table name
     * @return  string
     */
    protected function _getTableName($name)
    {
        return $this->resourceConnection->getTableName($name); 
    }

    /**
     * Return value from row by key or default.
     *    
     * @param array $row
     * @param string $key 
     * @param string $default          
     * @return string value for key in row or defaut
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function _parseRowValue($row, $key, $default = '')
    {
        return isset($row[$key]) ? $row[$key] : $default;
    }

    /**
     * Get existing row hash for product and return it
     *    
     * @param string $tableAlias
     * @param string $resultAlias 
     * @return string $rowHashQuery
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getRowHashQuery($tableAlias = null, $resultAlias = null)
    {
        $rowHashColumns = $this->_getRowHashColumns($tableAlias, $entity);
        $rowHashQuery = sprintf('MD5(concat(%s)) AS %s', implode(',', $rowHashColumns), $resultAlias);
        return $rowHashQuery;
    }

    /**
     * Get row hash colums in table for product and return it
     *    
     * @param string $tableAlias
     * @param string $entity     
     * @return string $rowHashColumns
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getRowHashColumns($tableAlias = null, $entity = 'products')
    {
        if ($entity == 'products') {
            $rowHashColumns = array(
                'sku', 'product_name', 'short_description', 'long_description', 'attribute_set_id', 'status', 
                'visibility', 'tax_class_id', 'price', 'qty', 'weight', 'package_contents'
            );
        } else if ($entity == 'stock') {
            $rowHashColumns = array(
                'sku', 'qty', 'status', 'status_date'
            );
        }

        foreach ($rowHashColumns as $key => $val) {
            if ($tableAlias) {
                $rowHashColumns[$key] = $this->_getWriteConnection()->getIfNullSql(sprintf('%s.%s', $tableAlias, $val), '""');
            } else {
                $rowHashColumns[$key] = $this->_getWriteConnection()->getIfNullSql($val, '""');
            }
        }

        return $rowHashColumns;
    }

    /**
     * Reindex indexes
     *    
     * @param array $products
     * @return boolean
     */
    public function runIndexers() {
        $indexer = $this->indexerFactory->create();
        $indexerCollection = $this->indexerCollectionFactory->create();
        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id){
            $idx = $indexer->load($id);
                 $idx->reindexRow($id);
                 $this->logger->info("# reindexed index id " . $id);
        }
        return true;
    }

}
