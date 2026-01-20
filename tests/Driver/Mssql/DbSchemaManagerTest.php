<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mssql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractDbSchemaManagerTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MssqlFactory;

/**
 * @group Mssql
 */
final class DbSchemaManagerTest extends AbstractDbSchemaManagerTest
{
    protected string $commentType = ColumnType::STRING;
    protected string $messageIdType = ColumnType::STRING;
    protected string $translationType = ColumnType::STRING;

    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new MssqlFactory())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('mssql_');

        parent::setUp();
    }
}
