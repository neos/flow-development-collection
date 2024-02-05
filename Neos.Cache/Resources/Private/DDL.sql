BEGIN;

CREATE TABLE "###CACHE_TABLE_NAME###" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "created" INTEGER UNSIGNED NOT NULL,
  "lifetime" INTEGER UNSIGNED DEFAULT '0' NOT NULL,
  "content" MEDIUMTEXT,
  PRIMARY KEY ("identifier", "cache", "context")
);

CREATE TABLE "###TAGS_TABLE_NAME###" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "tag" VARCHAR(250) NOT NULL
);
CREATE INDEX "identifier" ON "###TAGS_TABLE_NAME###" ("identifier", "cache", "context");
CREATE INDEX "tag" ON "###TAGS_TABLE_NAME###" ("tag");

COMMIT;
