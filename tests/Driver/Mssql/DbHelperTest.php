<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mssql;

use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractDbHelperTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MssqlFactory;

/**
 * @group Mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DbHelperTest extends AbstractDbHelperTest
{
    protected string $commentType = SchemaInterface::TYPE_STRING;
    protected string $messageIdType = SchemaInterface::TYPE_STRING;
    protected string $translationType = SchemaInterface::TYPE_STRING;

    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new MssqlFactory())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('mssql_');

        parent::setUp();
    }
}
