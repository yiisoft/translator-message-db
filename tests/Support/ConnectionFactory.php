<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Support;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;

abstract class ConnectionFactory
{
    protected function createSchemaCache(): SchemaCache
    {
        return new SchemaCache(new ArrayCache());
    }
}
