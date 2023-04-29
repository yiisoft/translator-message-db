/**
 * Database schema required by yiisoft/translator-message-db for SQLite.
 */
CREATE TABLE "source_message"
(
    id INTEGER NOT NULL CONSTRAINT "PK_source_message" PRIMARY KEY,
    category VARCHAR(255),
    message_id TEXT,
    comment TEXT
);

CREATE INDEX "IDX_source_message-category" on "source_message" ("category");

CREATE TABLE "message"
(
    id INTEGER NOT NULL CONSTRAINT "FK_source_message_message" REFERENCES "source_message" ON DELETE CASCADE,
    locale VARCHAR(16) NOT NULL,
    translation TEXT,
    PRIMARY KEY ("id", "locale")
);

CREATE INDEX "IDX_message-locale" on "message" ("locale");
