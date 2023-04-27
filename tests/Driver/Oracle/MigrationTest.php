<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Oracle;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMigrationTest;
use Yiisoft\Translator\Message\Db\Tests\Support\OracleHelper;

/**
 * @group Oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MigrationTest extends AbstractMigrationTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new OracleHelper())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('oci_');

        parent::setUp();
    }
}
