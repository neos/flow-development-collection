BEGIN;

CREATE TABLE "###CACHE_TABLE_NAME###" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "created" INTEGER UNSIGNED NOT NULL,
  "lifetime" INTEGER UNSIGNED DEFAULT '0' NOT NULL,
  "content" MEDIUMBLOB,
  PRIMARY KEY ("identifier", "cache", "context")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE "###TAGS_TABLE_NAME###" (
  "pk" INT NOT NULL AUTO_INCREMENT,
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "tag" VARCHAR(250) NOT NULL,
  PRIMARY KEY ("pk")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX "identifier" ON ###TAGS_TABLE_NAME### ("identifier", "cache", "context");
CREATE INDEX "tag" ON "###TAGS_TABLE_NAME###" ("tag");

COMMIT;
