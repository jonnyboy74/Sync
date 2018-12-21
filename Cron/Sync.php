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

namespace Onlinepromo\Sync\Cron;

class Sync
{

    protected $logger;

    /**
     * Constructor
     *
     * @param \Onlinepromo\Sync\Logger\Logger $logger
     * @param Onlinepromo\Sync\Model\Import $import
     */
    public function __construct(
        \Onlinepromo\Sync\Logger\Logger $logger,
        \Onlinepromo\Sync\Model\Import $import
        )
    {
        $this->logger = $logger;
        $this->import = $import;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        
        $this->logger->addInfo("Cronjob Sync is starting.");
        $this->import->run();
        $this->logger->addInfo("Cronjob Sync is executed.");
    }
}
