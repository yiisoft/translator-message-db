<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Translator\Message\Db\MessageSource;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractMessageSourceTest extends TestCase
{
    protected CacheInterface $cache;
    protected ConnectionInterface $db;

    /**
     * @dataProvider generateTranslationsData
     *
     * @psalm-param array<string, array<string, string>> $data
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
     */
    public function testWriteWithFailData(string $category, string $locale, array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);
    }

    public function testMultiWrite(): void
    {
        $allData = $this->generateTranslationsData();

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

    public function testMultiWriteWithCache(): void
    {
        $allData = $this->generateTranslationsData();

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
