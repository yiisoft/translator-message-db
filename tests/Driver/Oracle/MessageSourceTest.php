<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Oracle;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\OracleHelper;

/**
 * @group Oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new OracleHelper())->createConnection();

        Migration::ensureTable($this->db);

        parent::setup();
    }
}
