<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Translator\MessageInterface;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\MessageWriterInterface;
use function array_key_exists;
use function is_string;

final class MessageSource implements MessageReaderInterface, MessageWriterInterface
{
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

        return $this->messages[$category][$locale][$id] ?? null;
    }

    private function read(string $category, string $locale): array
    {
        if ($this->cache !== null) {
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

    private function readFromDb(string $category, string $locale): array
    {
        $query = (new Query($this->db))
            ->select(['message_id', 'translation'])
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
        $messages = $query->all();

        return ArrayHelper::map($messages, 'message_id', 'translation');
    }

    public function write(string $category, string $locale, array $messages): void
    {
        $this->validateMessages($messages);

        $sourceMessages = (new Query($this->db))
            ->select(['id', 'message_id'])
            ->from($this->sourceMessageTable)
            ->where(['category' => $category])
            ->all();

        $sourceMessages = ArrayHelper::map($sourceMessages, 'message_id', 'id');

        $translatedMessages = $this->readFromDb($category, $locale);

        foreach ($messages as $messageId => $message) {
            if (!isset($sourceMessages[$messageId])) {
                $comment = '';

                if (array_key_exists('comment', $message->meta())) {
                    if (!is_string($message->meta()['comment'])) {
                        throw new InvalidArgumentException("Message comment is not a string for ID \"$messageId\".");
                    }
                    $comment = $message->meta()['comment'];
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
                $sourceMessages[$messageId] = $result['id'];
            }

            $needUpdate = false;
            if (isset($translatedMessages[$messageId]) && $translatedMessages[$messageId] !== $message->translation()) {
                $this->db->createCommand()->delete($this->messageTable, ['id' => $sourceMessages[$messageId]])->execute();
                $needUpdate = true;
            }

            if ($needUpdate || !isset($translatedMessages[$messageId])) {
                $result = $this->db->getSchema()->insert(
                    $this->messageTable,
                    [
                        'id' => $sourceMessages[$messageId],
                        'locale' => $locale,
                        'translation' => $message->translation(),
                    ]
                );
                if ($result === false) {
                    throw new \RuntimeException("Failed to write a message with \"$messageId\" ID.");
                }
            }
        }
    }

    private function validateMessages(array $messages): void
    {
        foreach ($messages as $key => $message) {
            if (!$message instanceof MessageInterface) {
                $realType = gettype($message);
                throw new InvalidArgumentException("Messages should contain \"\Yiisoft\Translator\MessageInterface\" instances only. \"$realType\" given for \"$key\".");
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
