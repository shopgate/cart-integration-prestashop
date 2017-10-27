<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * temporary class until Prestashop can be bootstrapped
 */
class Order
{
    public $id_carrier;

    public $total_paid;

    public $total_paid_real;

    public $total_shipping;

    /**
     * @param int   $id_carrier
     * @param float $total_paid
     * @param float $total_paid_real
     * @param float $total_shipping
     */
    public function __construct($id_carrier, $total_paid, $total_paid_real, $total_shipping)
    {
        $this->id_carrier      = $id_carrier;
        $this->total_paid      = $total_paid;
        $this->total_paid_real = $total_paid_real;
        $this->total_shipping  = $total_shipping;
    }
}
