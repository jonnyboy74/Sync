<?php
/**
 * Module to import/update products
 * Copyright (C) 2018  John Park
 * 
 * This file is part of Onlinepromo/Sync.
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

namespace Onlinepromo\Sync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{

    const XML_PATH_FTP_HOST = 'inventory/sync/ftp_host';
    const XML_PATH_FTP_USER = 'inventory/sync/ftp_user';
    const XML_PATH_FTP_PASS = 'inventory/sync/ftp_password';
    const XML_PATH_FTP_FILE = 'inventory/sync/ftp_file';
    const XML_PATH_FTP_EXCLUDED_SKUS = 'inventory/sync/ftp_excuded_skus';
    const XML_PATH_FTP_LOG_FILENAME = 'inventory/sync/ftp_logfilename';
    const XML_PATH_FTP_IMPORT_PRODUCTS_PAGINATION_LIMIT = 'inventory/sync/import_products_pagination_limit';
    const XML_PATH_FTP_IMPORT_STOCK_PAGINATION_LIMIT = 'inventory/sync/import_stock_pagination_limit';
    const XML_PATH_FTP_DEFAULT_DESCRIPTION = 'inventory/sync/default_description';
    const XML_PATH_FTP_DEFAULT_PACKACGE_CONTENTS = 'inventory/sync/default_package_contents';
    

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function getFtpUser()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_USER, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpPass()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_PASS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpHost()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_HOST, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpFile()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_FILE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpExcludedSkus()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_EXCLUDED_SKUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpLogfilename()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_LOG_FILENAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpMaxProductImportChunk()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_IMPORT_PRODUCTS_PAGINATION_LIMIT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpMaxStockImportChunk()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_IMPORT_STOCK_PAGINATION_LIMIT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }

    public function getFtpDefaultDescription()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_DEFAULT_DESCRIPTION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }
    public function getFtpDefaultPackageContents()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FTP_DEFAULT_PACKACGE_CONTENTS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null);
    }


}
