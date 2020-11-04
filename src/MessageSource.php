<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

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

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
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
        $query = (new Query($this->db))
            ->select(['message_id', 'translation'])
            ->from(['ts' => $this->sourceMessageTable])
            ->innerJoin(['td' => $this->messageTable],
                [
                    'td.id' => new Expression('[[ts.id]]'),
                    'ts.category' => $category
                ])
            ->where([
                'locale' => $locale,
            ]);
        $messages = $query->all();

        return ArrayHelper::map($messages, 'message_id', 'translation');
    }

    public function setSourceMessageTable(string $sourceMessageTable): void
    {
        $this->sourceMessageTable = $sourceMessageTable;
    }

    public function setMessageTable(string $messageTable): void
    {
        $this->messageTable = $messageTable;
    }

    public function write(string $category, string $locale, array $messages): void
    {
        $sourceMessages = (new Query($this->db))
            ->select(['id', 'message_id'])
            ->from($this->sourceMessageTable)
            ->where(['category' => $category])
            ->all();

        $sourceMessages = ArrayHelper::map($sourceMessages, 'message_id', 'id');

        $translateMessages = $this->read($category, $locale);

        foreach ($messages as $message_id => $translation) {
            if (!isset($sourceMessages[$message_id])) {
                $result = $this->db->getSchema()->insert($this->sourceMessageTable, ['category' => $category, 'message_id' => $message_id]);
                if ($result===false)
                    throw new \RuntimeException('Can not create source message with id ' . $message_id);
                $sourceMessages[$message_id] = $result['id'];
            }

            $needUpdate = false;
            if (isset($translateMessages[$message_id]) && $translateMessages[$message_id] !== $translation) {
                $this->db->createCommand()->delete($this->messageTable, ['id' => $sourceMessages[$message_id]])->execute();
                $needUpdate = true;
            }

            if ($needUpdate || !isset($translateMessages[$message_id])) {
                $result = $this->db->getSchema()->insert($this->messageTable, ['id' => $sourceMessages[$message_id], 'locale' => $locale, 'translation' => $translation]);
                if ($result===false)
                    throw new \RuntimeException('Can not create source message with id ' . $message_id);
            }
        }
    }
}
