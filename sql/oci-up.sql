/* STATEMENTS */
CREATE TABLE "yii_source_message" (
	"id" NUMBER(10) NOT NULL PRIMARY KEY,
	"category" VARCHAR2(255),
	"message_id" CLOB,
	"comment" CLOB
);
CREATE TABLE "yii_message" (
	"id" NUMBER(10) NOT NULL,
	"locale" VARCHAR2(16) NOT NULL,
	"translation" CLOB,
	CONSTRAINT "PK_yii_message_id_locale" PRIMARY KEY ("id", "locale"),
	CONSTRAINT "FK_yii_source_message_yii_message" FOREIGN KEY ("id") REFERENCES "yii_source_message" ("id") ON DELETE CASCADE
);
CREATE INDEX "IDX_yii_source_message_category" ON "yii_source_message" ("category");
CREATE INDEX "IDX_yii_message_locale" ON "yii_message" ("locale");
CREATE SEQUENCE "yii_source_message_SEQ" START WITH 1 INCREMENT BY 1 NOMAXVALUE;
CREATE SEQUENCE "yii_message_SEQ" START WITH 1 INCREMENT BY 1 NOMAXVALUE;

/* TRIGGERS */
CREATE TRIGGER "yii_source_message_TRG" BEFORE INSERT ON "yii_source_message" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
IF INSERTING AND :NEW."id" IS NULL THEN SELECT "yii_source_message_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
CREATE TRIGGER "yii_message_TRG" BEFORE INSERT ON "yii_message" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
IF INSERTING AND :NEW."id" IS NULL THEN SELECT "yii_message_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
