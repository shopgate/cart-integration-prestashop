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
namespace Shopgate\Tests\Unit\Classes\Items\Item;

class InputTest extends \PHPUnit_Framework_TestCase
{
    /** @var \ShopgateItemsInput $class */
    protected $class;

    public function setUp()
    {
        $this->class = new \ShopgateItemsInput(new \ShopgateDb());
    }

    /**
     * @dataProvider typeProvider
     */
    public function testGetSystemInputType()
    {
        $input = new \ShopgateOrderItemInput();
        $input->setType('text');
        $result = $this->class->getSystemInputType($input);

        $this->assertEquals($result, 1);
    }

    /**
     * Provides shopgate to Presta mapping
     *
     * @return array
     */
    public function typeProvider()
    {
        return array(
            'text field' => array(\Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT, 1),
            'file field' => array(\Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE, 2),
        );
    }
}
