<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mysql;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMigrationTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MysqlHelper;

/**
 * @group Mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MigrationTest extends AbstractMigrationTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new MysqlHelper())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('mysql_');

        parent::setUp();
    }
}
