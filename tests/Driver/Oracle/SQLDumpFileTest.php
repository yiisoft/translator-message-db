<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Oracle;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractSQLDumpFileTest;
use Yiisoft\Translator\Message\Db\Tests\Support\OracleFactory;

/**
 * @group Oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SQLDumpFileTest extends AbstractSQLDumpFileTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new OracleFactory())->createConnection();

        parent::setUp();
    }
}
