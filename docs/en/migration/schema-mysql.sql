/**
 * Database schema required by yiisoft/translator-message-db for MySQL.
 */
CREATE TABLE `source_message`
(
    [id] INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    [category] VARCHAR(255) NULL,
    [message_id] TEXT NULL,
    [comment] TEXT NULL
);

CREATE INDEX `IDX_source_message-id` ON `source_message` (`id`);

CREATE TABLE `message` (
(
    `id` INT NOT NULL,
    `locale` VARCHAR(16) NOT NULL,
    `translation` TEXT NULL,
    PRIMARY KEY (`id`, `locale`),
    CONSTRAINT `FK_source_message_message` FOREIGN KEY (`id`) REFERENCES `source_message` (`id`) ON DELETE CASCADE
);

CREATE INDEX `IDX_message-locale` ON `message` (`locale`);
