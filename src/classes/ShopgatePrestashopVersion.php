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
class ShopgatePrestashopVersion
{
    /** @var string */
    private $version;

    /**
     * @param string $prestashopVersion
     */
    public function __construct($prestashopVersion)
    {
        $this->version = $prestashopVersion;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function isAtLeast($version)
    {
        return $this->compare($version, '>=');
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function isBelow($version)
    {
        return $this->compare($version, '<');
    }

    /**
     * @param string $version
     * @param string $operator
     *
     * @return bool
     */
    public function compare($version, $operator)
    {
        return version_compare($this->version, $version, $operator);
    }
}
