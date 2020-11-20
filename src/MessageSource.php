<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\MessageWriterInterface;

final class MessageSource implements MessageReaderInterface, MessageWriterInterface
{
    private array $messages = [];
    private ConnectionInterface $db;
    private string $sourceMessageTable = '{{%source_message}}';
    private string $messageTable = '{{%message}}';

    private ?CacheInterface $cache = null;
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
        if ($this->cache instanceof CacheInterface) {
            $key = $this->getCacheKey($category, $locale);

            $messages = $this->cache->get($key);

            if ($messages === null) {
                $messages = $this->readFromDb($category, $locale);
                $this->cache->set($key, $messages, $this->cachingDuration);
            }

            return $messages;
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
        $sourceMessages = (new Query($this->db))
            ->select(['id', 'message_id'])
            ->from($this->sourceMessageTable)
            ->where(['category' => $category])
            ->all();

        $sourceMessages = ArrayHelper::map($sourceMessages, 'message_id', 'id');

        $translatedMessages = $this->readFromDb($category, $locale);

        foreach ($messages as $messageId => $translation) {
            if (!isset($sourceMessages[$messageId])) {
                $result = $this->db->getSchema()->insert($this->sourceMessageTable, ['category' => $category, 'message_id' => $messageId]);
                if ($result === false) {
                    throw new \RuntimeException('Can not create source message with id ' . $messageId);
                }
                $sourceMessages[$messageId] = $result['id'];
            }

            $needUpdate = false;
            if (isset($translatedMessages[$messageId]) && $translatedMessages[$messageId] !== $translation) {
                $this->db->createCommand()->delete($this->messageTable, ['id' => $sourceMessages[$messageId]])->execute();
                $needUpdate = true;
            }

            if ($needUpdate || !isset($translatedMessages[$messageId])) {
                $result = $this->db->getSchema()->insert($this->messageTable, ['id' => $sourceMessages[$messageId], 'locale' => $locale, 'translation' => $translation]);
                if ($result === false) {
                    throw new \RuntimeException('Can not create source message with id ' . $messageId);
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
