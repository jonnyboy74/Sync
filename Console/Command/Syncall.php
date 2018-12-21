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

namespace Onlinepromo\Sync\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Onlinepromo\Sync\Model\Import;

class Syncall extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @param \Magento\Framework\App\State $state
     */
    
    public function __construct(
        \Magento\Framework\App\State $state,
        \Onlinepromo\Sync\Model\Import $import
    ) {
        $this->state = $state;
        $this->import = $import;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $output->writeln("Starting Product Sync Operation");
        $import = $this->getImportModel();
        $result = $import->run();
        $output->writeln("Finished Product Sync Operation");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('onlinepromo_sync:syncall')
            ->setDescription('Sync Products from FTP to Magento')
            ->addOption('inventory_only', "i", InputOption::VALUE_OPTIONAL, "inventory_only");
        parent::configure();
    }

    /**
     * @return \Onlinepromo\Sync\Model\Import
     */
    protected function getImportModel()
    {
        return $this->import;
    }

}
