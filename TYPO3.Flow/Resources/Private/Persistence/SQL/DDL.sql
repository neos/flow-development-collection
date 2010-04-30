BEGIN;

CREATE TABLE "entities" (
  "identifier" CHAR(36) PRIMARY KEY NOT NULL,
  "type" VARCHAR(250) NOT NULL,
  "parent" CHAR(36)
);
CREATE INDEX "elookup" ON "entities" ("identifier", "type");

CREATE TABLE "valueobjects" (
  "identifier" CHAR(40) PRIMARY KEY NOT NULL,
  "type" VARCHAR(250) NOT NULL
);
CREATE INDEX "vlookup" ON "valueobjects" ("identifier", "type");

CREATE TABLE "properties" (
  "parent" CHAR(40) NOT NULL,
  "name" VARCHAR(250) NOT NULL,
  "multivalue" INTEGER NOT NULL DEFAULT '0',
  "type" VARCHAR(250) NOT NULL,
  PRIMARY KEY ("parent", "name")
);

CREATE TABLE "properties_data" (
  "parent" CHAR(40) NOT NULL,
  "name" VARCHAR(250) NOT NULL,
  "index" VARCHAR(250),
  "type" VARCHAR(250) NOT NULL,
  "array" CHAR(24),
  "string" TEXT,
  "integer" INTEGER,
  "float" DECIMAL,
  "datetime" INTEGER,
  "boolean" CHAR(1),
  "object" VARCHAR(40)
);
CREATE UNIQUE INDEX "id" ON "properties_data" ("parent", "name", "index");
CREATE INDEX "id1" ON "properties_data" ("parent", "name");

COMMIT;
