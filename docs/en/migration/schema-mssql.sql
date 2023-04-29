/**
 * Database schema required by yiisoft/translator-message-db for MSSQL.
 */
CREATE TABLE [dbo].[source_message]
(
    [id] INT IDENTITY NOT NULL,
    [category] NVARCHAR(255),
    [message_id] NVARCHAR(max),
    [comment] NVARCHAR(max)
);

CREATE UNIQUE CLUSTERED INDEX [PK_source_message] ON [dbo].[source_message] ([id]);
CREATE INDEX [IDX_source_message-category] ON [dbo].[source_message] ([category]);

CREATE TABLE [dbo].[message]
(
    [id] INT NOT NULL,
    [locale] NVARCHAR(16) NOT NULL,
    [translation] NVARCHAR(max)
);

CREATE UNIQUE CLUSTERED INDEX [PK_message-id-locale] ON [dbo].[message] ([id], [locale]);
CREATE INDEX [IDX_message-locale] ON [dbo].[message] ([locale]);
