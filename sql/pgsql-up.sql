CREATE TABLE "yii_source_message" (
	"id" serial NOT NULL PRIMARY KEY,
	"category" varchar(255),
	"message_id" text,
	"comment" text
);
CREATE TABLE "yii_message" (
	"id" integer NOT NULL,
	"locale" varchar(16) NOT NULL,
	"translation" text,
    CONSTRAINT "PK_yii_message_id_locale" PRIMARY KEY ("id", "locale"),
    CONSTRAINT "FK_yii_source_message_yii_message" FOREIGN KEY ("id") REFERENCES "yii_source_message" ("id") ON DELETE CASCADE ON UPDATE RESTRICT
);
CREATE INDEX "IDX_yii_source_message_category" ON "yii_source_message" ("category");
CREATE INDEX "IDX_yii_message_locale" ON "yii_message" ("locale");
