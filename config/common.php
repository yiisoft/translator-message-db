<?php

declare(strict_types=1);

use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\Message\Db\MessageSource;

return [
    MessageReaderInterface::class => [
        'class' => MessageSource::class,
    ],
];
