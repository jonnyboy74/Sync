<?php
/**
 * Module to import/update products
 * Copyright (C) 2018  John Park.
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

namespace Onlinepromo\Sync\Model;

class Products extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init(\OnlinePromo\Sync\Model\ResourceModel\Products::class);
    }
}
