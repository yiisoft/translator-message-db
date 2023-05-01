<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Common;

use JsonException;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Translator\Message\Db\MessageSource;
use Yiisoft\Translator\Message\Db\DbHelper;

abstract class AbstractMessageSourceTest extends TestCase
{
    protected CacheInterface $cache;
    protected ConnectionInterface $db;

    protected function setup(): void
    {
        $this->cache = new Cache(new ArrayCache());

        parent::setup();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function tearDown(): void
    {
        // drop table
        DbHelper::dropTables($this->db);

        $this->db->close();

        unset($this->db, $this->cache);

        parent::tearDown();
    }

    /**
     * @dataProvider generateTranslationsData
     *
     * @psalm-param array<string, array<string, string>> $data
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testWrite(string $category, string $locale, array $data): void
    {
        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);

        foreach ($data as $messageId => $messageData) {
            $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
        }
    }

    /**
     * @dataProvider generateFailTranslationsData
     *
     * @psalm-param array<string, array<string, string>> $data
     *
     * @throws Exception
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testWriteWithFailData(string $category, string $locale, array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testMultiWrite(): void
    {
        $allData = self::generateTranslationsData();

        $messageSource = new MessageSource($this->db);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }


        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            foreach ($data as $messageId => $messageData) {
                $this->assertEquals(
                    $messageData['message'],
                    $messageSource->getMessage($messageId, $category, $locale)
                );
            }
        }
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testUpdate(): void
    {
        $updatedData = [
            'app',
            'de',
            [
                'test.id1' => [
                    'message' => 'Need to update',
                ],
                'test.id2' => [
                    'message' => 'app: Test 2 on the (de-DE)',
                ],
            ],
        ];

        $allData = [
            [
                'app',
                'de',
                [
                    'test.id1' => [
                        'message' => 'app: Test 1 on the (de)',
                        'comment' => 'Translate wisely!',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de)',
                    ],
                    'test.id3' => [
                        'message' => 'app: Test 3 on the (de)',
                    ],
                ],
            ],
            $updatedData,
        ];

        $messageSource = new MessageSource($this->db);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }

        [$category, $locale, $data] = $updatedData;

        foreach ($data as $messageId => $messageData) {
            $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
        }
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testMultiWriteWithCache(): void
    {
        $allData = self::generateTranslationsData();

        $messageSource = new MessageSource($this->db, $this->cache);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            foreach ($data as $messageId => $messageData) {
                $this->assertEquals(
                    $messageData['message'],
                    $messageSource->getMessage($messageId, $category, $locale),
                );
            }
        }
    }

    /**
     * @dataProvider generateTranslationsData
     *
     * @psalm-param array<string, array<string, string>> $data
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testReadMessages(string $category, string $locale, array $data): void
    {
        $messageSource = new MessageSource($this->db);

        $messageSource->write($category, $locale, $data);
        $messages = $messageSource->getMessages($category, $locale);

        $this->assertEquals($messages, $data);
    }

    /**
     * @psalm-return array<array{0: string, 1: string, 2: array<string, array<string, string>>}>
     */
    public static function generateTranslationsData(): array
    {
        return [
            [
                'app',
                'de',
                [
                    'test.id1' => [
                        'comment' => 'Translate wisely!',
                        'message' => 'app: Test 1 on the (de)',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de)',
                    ],
                    'test.id3' => [
                        'message' => 'app: Test 3 on the (de)',
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => 'app: Test 1 on the (de-DE)',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de-DE)',
                    ],
                ],
            ],
        ];
    }

    /**
     * @psalm-return array<array{0: string, 1: string, 2: array<string, array<string, string|int>>}>
     */
    public static function generateFailTranslationsData(): array
    {
        return [
            [
                'app',
                'de',
                [
                    'test.id1' => [
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => 1,
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => '',
                        'comment' => 1,
                    ],
                ],
            ],
        ];
    }
}
