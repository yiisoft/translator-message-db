/**
 * Database schema required by yiisoft/translator-message-db for Oracle.
 */
CREATE TABLE "source_message"
(
    "id" NUMBER(10) NOT NULL PRIMARY KEY,
    "category" VARCHAR2(255),
    "message_id" CLOB,
    "comment" CLOB
);

CREATE INDEX "IDX_source_message-category" on "source_message" ("category");

CREATE TRIGGER "source_message_TRG"
    BEFORE INSERT
    ON "source_message"
    FOR EACH ROW
BEGIN <<COLUMN_SEQUENCES>> BEGIN
IF INSERTING AND :NEW."id" IS NULL THEN SELECT "source_message_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/

CREATE TABLE "message"
(
    "id" NUMBER(10) NOT NULL CONSTRAINT "FK_source_message_message" REFERENCES "source_message" ON DELETE CASCADE,
    "locale" VARCHAR2(16) NOT NULL,
    "translation" CLOB,
    CONSTRAINT "PK_oci_message-id-locale" PRIMARY KEY ("id", "locale")
);

CREATE INDEX "IDX_message-locale" on "message" ("locale");

CREATE TRIGGER "message_TRG"
    BEFORE INSERT
    ON "message"
    FOR EACH ROW
BEGIN <<COLUMN_SEQUENCES>> BEGIN
IF INSERTING AND :NEW."id" IS NULL THEN SELECT "message_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
