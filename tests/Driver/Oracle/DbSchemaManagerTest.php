<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Oracle;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractDbSchemaManagerTest;
use Yiisoft\Translator\Message\Db\Tests\Support\OracleFactory;

/**
 * @group Oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DbSchemaManagerTest extends AbstractDbSchemaManagerTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new OracleFactory())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('oci_');

        parent::setUp();
    }
}
