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

require_once(dirname(__FILE__) . '/classes/ShopgatePrestashopVersion.php');
require_once(dirname(__FILE__) . '/classes/Shipping.php');
require_once(dirname(__FILE__) . '/classes/items/Abstract.php');
require_once(dirname(__FILE__) . '/classes/items/order/Order.php');
require_once(dirname(__FILE__) . '/classes/helpers/Context.php');
require_once(dirname(__FILE__) . '/classes/helpers/coupon/CartRuleFactory.php');
require_once(dirname(__FILE__) . '/classes/helpers/coupon/Validation.php');
require_once(dirname(__FILE__) . '/classes/items/item/Input.php');
require_once(dirname(__FILE__) . '/classes/items/item/BasePrice.php');
require_once(dirname(__FILE__) . '/classes/database/Db.php');
