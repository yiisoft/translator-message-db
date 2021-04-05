<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\MessageWriterInterface;
use function array_key_exists;
use function is_string;

final class MessageSource implements MessageReaderInterface, MessageWriterInterface
{
    /** @psalm-var array<string, array<string, array<string, array<string, string>>>>  */
    private array $messages = [];
    private ConnectionInterface $db;
    private string $sourceMessageTable = '{{%source_message}}';
    private string $messageTable = '{{%message}}';

    private ?CacheInterface $cache;
    private int $cachingDuration = 3600;

    public function __construct(ConnectionInterface $db, ?CacheInterface $cache = null, ?int $cacheDuration = null)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->cachingDuration = $cacheDuration ?? $this->cachingDuration;
    }

    public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
    {
        if (!isset($this->messages[$category][$locale])) {
            $this->messages[$category][$locale] = $this->read($category, $locale);
        }

        return $this->messages[$category][$locale][$id]['message'] ?? null;
    }

    public function getMessages(string $category, string $locale): array
    {
        if (!isset($this->messages[$category][$locale])) {
            $this->messages[$category][$locale] = $this->read($category, $locale);
        }

        return $this->messages[$category][$locale] ?? [];
    }

    /** @psalm-return array<string, array<string, string>> */
    private function read(string $category, string $locale): array
    {
        if ($this->cache !== null) {
            /** @psalm-var array<string, array<string, string>> */
            return $this->cache->getOrSet(
                $this->getCacheKey($category, $locale),
                function () use ($category, $locale) {
                    return $this->readFromDb($category, $locale);
                },
                $this->cachingDuration
            );
        }

        return $this->readFromDb($category, $locale);
    }

    /** @psalm-return array<string, array<string, string>> */
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
            ->where([
                'locale' => $locale,
            ]);
        /** @psalm-var array<array-key, array<string, string>>*/
        $messages = $query->all();

        /** @psalm-var array<string, array<string, string>> */
        return ArrayHelper::map($messages, 'message_id', function (array $message): array {
            return array_merge(['message' => $message['translation']], !empty($message['comment']) ? ['comment' => $message['comment']] : []);
        });
    }

    /**
     * @psalm-param array<string, array<string, string>> $messages
     */
    public function write(string $category, string $locale, array $messages): void
    {
        /** @psalm-var array<array-key, array<string, string>> $sourceMessages */
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

            /** @psalm-suppress DocblockTypeContradiction */
            if (!isset($sourceMessages[$messageId])) {
                $comment = '';

                if (array_key_exists('comment', $messageData)) {
                    if (!is_string($messageData['comment'])) {
                        throw new InvalidArgumentException("Message comment is not a string for ID \"$messageId\".");
                    }
                    $comment = $messageData['comment'];
                }

                $result = $this->db->getSchema()->insert(
                    $this->sourceMessageTable,
                    [
                        'category' => $category,
                        'message_id' => $messageId,
                        'comment' => $comment,
                    ],
                );
                if ($result === false) {
                    throw new \RuntimeException("Failed to write source message with \"$messageId\" ID.");
                }
                /** @psalm-var string */
                $sourceMessages[$messageId] = $result['id'];
            }

            $needUpdate = false;
            if (isset($translatedMessages[$messageId]) && $translatedMessages[$messageId]['message'] !== $messageData['message']) {
                $this->db->createCommand()->delete($this->messageTable, ['id' => $sourceMessages[$messageId]])->execute();
                $needUpdate = true;
            }

            if ($needUpdate || !isset($translatedMessages[$messageId])) {
                $result = $this->db->getSchema()->insert($this->messageTable, ['id' => $sourceMessages[$messageId], 'locale' => $locale, 'translation' => $messageData['message']]);
                if ($result === false) {
                    throw new \RuntimeException("Failed to write message with \"$messageId\" ID.");
                }
            }
        }
    }

    private function getCacheKey(string $category, string $locale): string
    {
        $key = [
            __CLASS__,
            $category,
            $locale,
        ];

        $jsonKey = json_encode($key);

        return md5($jsonKey);
    }
}
