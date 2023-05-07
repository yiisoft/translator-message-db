<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use JsonException;
use RuntimeException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\MessageWriterInterface;

use function array_key_exists;
use function is_string;

/**
 * Allows using a database as a message source for `yiisoft/translator`.
 *
 * Use the {@see DbSchemaManager::ensureTables()} to initialize database schema.
 */
final class MessageSource implements MessageReaderInterface, MessageWriterInterface
{
    /**
     * @psalm-var array<string, array<string, array<string, array<string, string>>>>
     */
    private array $messages = [];

    public function __construct(
        private ConnectionInterface $db,
        private CacheInterface|null $cache = null,
        private string $sourceMessageTable = '{{%yii_source_message}}',
        private string $messageTable = '{{%yii_message}}',
        private int $cachingDuration = 3600
    ) {
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function getMessage(string $id, string $category, string $locale, array $parameters = []): string|null
    {
        if (!isset($this->messages[$category][$locale])) {
            $this->messages[$category][$locale] = $this->read($category, $locale);
        }

        return $this->messages[$category][$locale][$id]['message'] ?? null;
    }

    /**
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function getMessages(string $category, string $locale): array
    {
        if (!isset($this->messages[$category][$locale])) {
            $this->messages[$category][$locale] = $this->read($category, $locale);
        }

        return $this->messages[$category][$locale] ?? [];
    }

    /**
     * @psalm-param array<string, array<string, string>> $messages
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function write(string $category, string $locale, array $messages): void
    {
        /** @psalm-var array<int, array<string, string|null>> $sourceMessages */
        $sourceMessages = (new Query($this->db))
            ->select(['id', 'message_id'])
            ->from($this->sourceMessageTable)
            ->where(['category' => $category])
            ->all();

        $sourceMessages = ArrayHelper::map($sourceMessages, 'message_id', 'id');
        $translatedMessages = $this->readFromDb($category, $locale);

        foreach ($messages as $messageId => $messageData) {
            if (!array_key_exists('message', $messageData)) {
                throw new InvalidArgumentException("Message is not valid for ID \"$messageId\". \"message\" key is missing.");
            }

            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_string($messageData['message'])) {
                throw new InvalidArgumentException("Message is not a string for ID \"$messageId\".");
            }

            if (!isset($sourceMessages[$messageId])) {
                $comment = '';

                if (array_key_exists('comment', $messageData)) {
                    /** @psalm-suppress DocblockTypeContradiction */
                    if (!is_string($messageData['comment'])) {
                        throw new InvalidArgumentException("Message comment is not a string for ID \"$messageId\".");
                    }
                    $comment = $messageData['comment'];
                }

                /** @psalm-var array<string,string>|false $result */
                $result = $this->db
                    ->createCommand()
                    ->insertWithReturningPks(
                        $this->sourceMessageTable,
                        [
                            'category' => $category,
                            'message_id' => $messageId,
                            'comment' => $comment,
                        ],
                    );

                if ($result === false) {
                    throw new RuntimeException("Failed to write source message with \"$messageId\" ID.");
                }

                $sourceMessages[$messageId] = $result['id'];
            }

            $needUpdate = false;
            if (isset($translatedMessages[$messageId]) && $translatedMessages[$messageId]['message'] !== $messageData['message']) {
                $this->db
                    ->createCommand()
                    ->delete($this->messageTable, ['id' => $sourceMessages[$messageId]])
                    ->execute();
                $needUpdate = true;
            }

            if ($needUpdate || !isset($translatedMessages[$messageId])) {
                $result = $this->db
                    ->createCommand()
                    ->insertWithReturningPks(
                        $this->messageTable,
                        [
                            'id' => $sourceMessages[$messageId],
                            'locale' => $locale,
                            'translation' => $messageData['message'],
                        ]
                    );

                if ($result === false) {
                    throw new RuntimeException("Failed to write message with \"$messageId\" ID.");
                }
            }
        }
    }

    /**
     * @psalm-return array<string, array<string, string>>
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function read(string $category, string $locale): array
    {
        if ($this->cache !== null) {
            /** @psalm-var array<string, array<string, string>> */
            return $this->cache->getOrSet(
                $this->getCacheKey($category, $locale),
                fn () => $this->readFromDb($category, $locale),
                $this->cachingDuration
            );
        }

        return $this->readFromDb($category, $locale);
    }

    /**
     * @psalm-return array<string, array<string, string>>
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function readFromDb(string $category, string $locale): array
    {
        $query = (new Query($this->db))
            ->select(['message_id', 'translation', 'comment'])
            ->from(['ts' => $this->sourceMessageTable])
            ->innerJoin(
                ['td' => $this->messageTable],
                [
                    'td.id' => new Expression('[[ts.id]]'),
                    'ts.category' => $category,
                ]
            )
            ->where(['locale' => $locale]);

        /** @psalm-var array<int, array<string, string>> $messages */
        $messages = $query->all();

        /** @psalm-var array<string, array<string, string>> */
        return ArrayHelper::map($messages, 'message_id', static fn (array $message): array => array_merge(
            ['message' => $message['translation']],
            !empty($message['comment']) ? ['comment' => $message['comment']] : []
        ));
    }

    /**
     * @throws JsonException
     */
    private function getCacheKey(string $category, string $locale): string
    {
        $key = [self::class, $category, $locale];
        $jsonKey = json_encode($key, JSON_THROW_ON_ERROR);

        return md5($jsonKey);
    }
}
