CREATE TABLE [yii_source_message] (
	[id] int IDENTITY PRIMARY KEY,
	[category] nvarchar(255),
	[message_id] nvarchar(max),
	[comment] nvarchar(max)
);
CREATE TABLE [yii_message] (
	[id] int NOT NULL,
	[locale] nvarchar(16) NOT NULL,
	[translation] nvarchar(max),
    CONSTRAINT [PK_yii_message_id_locale] PRIMARY KEY ([id], [locale]),
    CONSTRAINT [FK_yii_source_message_yii_message] FOREIGN KEY ([id]) REFERENCES [yii_source_message] ([id]) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX [IDX_yii_source_message_category] ON [yii_source_message] ([category]);
CREATE INDEX [IDX_yii_message_locale] ON [yii_message] ([locale]);
