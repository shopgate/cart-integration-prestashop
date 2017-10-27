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
class ShopgateDb
{
    const ENGINE_PDO    = 'DbPDO';
    const ENGINE_MYSQLI = 'DbMySQLi';

    /** @var Db | DbCore | DbPDOCore | MySQLCore | DbMySQLiCore $db */
    protected $db;

    /** @var PDO | MySQLi | Resource */
    protected $link;

    /**
     * @return Db | DbCore | DbPDOCore | MySQLCore
     */
    public function getInstance()
    {
        if (is_null($this->db)) {
            /** @noinspection PhpUndefinedClassInspection */
            $this->db = Db::getInstance();
        }

        return $this->db;
    }

    /**
     * @param Db | DbCore | DbPDOCore | MySQLCore $dbInstance
     *
     * @return $this
     */
    public function setInstance(Db $dbInstance)
    {
        $this->db = $dbInstance;

        return $this;
    }

    /**
     * Starts db transaction, base on used engine
     *
     * @throws ShopgateBeginTransactionFailedException
     */
    public function beginTransaction()
    {
        if (!$this->engineSupportsTransactions()) {
            ShopgateLogger::getInstance()->log(
                'Transactions not supported by DB engine.',
                ShopgateLogger::LOGTYPE_DEBUG
            );

            return;
        }

        $result = $this->isPDOUsed()
            ? $this->getLink()->beginTransaction()
            : ($this->isMysqliUsed()
                ? $this->getLink()->autocommit(false)
                : null);

        if ($result === false) {
            throw new ShopgateBeginTransactionFailedException();
        }
    }

    /**
     * Commit transaction if possible
     *
     * @throws ShopgateCommitTransactionFailedException
     */
    public function commit()
    {
        if (!$this->engineSupportsTransactions()) {
            return;
        }

        $result = $this->getLink()->commit();

        if ($result === false) {
            throw new ShopgateCommitTransactionFailedException();
        }
    }

    /**
     * Rollback transaction if possible
     *
     * @throws ShopgateRollbackTransactionFailedException
     */
    public function rollback()
    {
        if (!$this->engineSupportsTransactions()) {
            return;
        }

        $result = $this->getLink()->rollback();

        if ($result && $this->isMysqliUsed()) {
            $result &= $this->getLink()->autocommit(true);
        }

        if ($result === false) {
            throw new ShopgateRollbackTransactionFailedException();
        }
    }

    /**
     * @return bool
     */
    public function engineSupportsTransactions()
    {
        return $this->isPDOUsed() || $this->isMysqliUsed();
    }

    /**
     * @return bool
     */
    public function isMysqliUsed()
    {
        return get_class($this->getInstance()) == self::ENGINE_MYSQLI;
    }

    /**
     * @return bool
     */
    public function isPDOUsed()
    {
        return get_class($this->getInstance()) == self::ENGINE_PDO;
    }

    /**
     * @return PDO | MySQLi | Resource
     */
    private function getLink()
    {
        if (is_null($this->link)) {
            $this->link = $this->db->connect();
        }

        return $this->link;
    }

    /**
     * Executes SQL query based on selected type
     *
     * @param string $table
     * @param array  $data
     * @param string $type (INSERT, INSERT IGNORE, REPLACE, UPDATE).
     * @param string $where
     * @param int    $limit
     * @param bool   $use_cache
     * @param bool   $use_null
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function autoExecute($table, $data, $type, $where = '', $limit = 0, $use_cache = true, $use_null = false)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $type = strtoupper($type);
            switch ($type) {
                case 'INSERT':
                    return $this->getInstance()->insert($table, $data, $use_null, $use_cache, Db::INSERT, false);

                case 'INSERT IGNORE':
                    return $this->getInstance()->insert($table, $data, $use_null, $use_cache, Db::INSERT_IGNORE, false);

                case 'REPLACE':
                    return $this->getInstance()->insert($table, $data, $use_null, $use_cache, Db::REPLACE, false);

                case 'UPDATE':
                    return $this->getInstance()->update($table, $data, $where, $limit, $use_null, $use_cache, false);

                default:
                    throw new PrestaShopDatabaseException('Wrong argument (miss type) in Db::autoExecute()');
            }
        } else {
            $this->getInstance()->autoExecute($table, $data, $type, $where, $limit = 0, $use_cache, $use_null);
        }
    }

    /**
     * Filter SQL query within a blacklist
     *
     * @param string $table  Table where insert/update data
     * @param array  $values Data to insert/update
     * @param string $type   INSERT or UPDATE
     * @param string $where  WHERE clause, only for UPDATE (optional)
     * @param int    $limit  LIMIT clause (optional)
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function autoExecuteWithNullValues($table, $values, $type, $where = '', $limit = 0)
    {
        return $this->autoExecute($table, $values, $type, $where, $limit, 0, true);
    }
}
