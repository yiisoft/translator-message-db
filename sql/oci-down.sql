/* STATEMENTS */
ALTER TABLE "yii_message" DROP CONSTRAINT "FK_yii_source_message_yii_message";
DROP TRIGGER "yii_message_TRG";
DROP SEQUENCE "yii_message_SEQ";
DROP TABLE "yii_message";
DROP TRIGGER "yii_source_message_TRG";
DROP SEQUENCE "yii_source_message_SEQ";
DROP TABLE "yii_source_message";
