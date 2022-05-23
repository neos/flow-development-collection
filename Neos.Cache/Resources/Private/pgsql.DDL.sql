BEGIN;

CREATE TABLE "cache" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "created" INTEGER NOT NULL,
  "lifetime" INTEGER DEFAULT '0' NOT NULL,
  "content" BYTEA,
  PRIMARY KEY ("identifier", "cache", "context")
);

CREATE TABLE "tags" (
  "identifier" VARCHAR(250) NOT NULL,
  "cache" VARCHAR(250) NOT NULL,
  "context" VARCHAR(150) NOT NULL,
  "tag" VARCHAR(250) NOT NULL
);
CREATE INDEX "identifier" ON "tags" ("identifier", "cache", "context");
CREATE INDEX "tag" ON "tags" ("tag");

COMMIT;
