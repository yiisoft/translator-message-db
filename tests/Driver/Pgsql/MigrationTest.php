<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Pgsql;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMigrationTest;
use Yiisoft\Translator\Message\Db\Tests\Support\PgsqlHelper;

/**
 * @group Pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MigrationTest extends AbstractMigrationTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new PgsqlHelper())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('pgsql_');

        parent::setUp();
    }
}
