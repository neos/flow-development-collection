BEGIN;

CREATE TABLE IF NOT EXISTS "cache" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "created" INTEGER NOT NULL,
  "lifetime" INTEGER DEFAULT '0' NOT NULL,
  "content" TEXT,
  PRIMARY KEY ("identifier", "cache", "context")
);

CREATE TABLE IF NOT EXISTS "tags" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "tag" VARCHAR(250) NOT NULL
);
CREATE INDEX IF NOT EXISTS "identifier" ON "tags" ("identifier", "cache", "context");
CREATE INDEX IF NOT EXISTS "tag" ON "tags" ("tag");

COMMIT;
