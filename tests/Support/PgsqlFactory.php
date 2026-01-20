<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Support;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;

final class PgsqlFactory extends ConnectionFactory
{
    public function createConnection(): ConnectionInterface
    {
        $database = getenv('YII_PGSQL_DATABASE');
        $host = getenv('YII_PGSQL_HOST');
        $port = getenv('YII_PGSQL_PORT');
        $user = getenv('YII_PGSQL_USER');
        $password = getenv('YII_PGSQL_PASSWORD');

        $pdoDriver = new Driver("pgsql:host=$host;dbname=$database;port=$port", $user, $password);
        $pdoDriver->charset('UTF8');

        return new Connection($pdoDriver, $this->createSchemaCache());
    }
}
