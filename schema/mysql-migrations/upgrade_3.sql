CREATE TABLE ddolab_schema_migration (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time DATETIME NOT NULL,
  PRIMARY KEY(schema_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO ddolab_schema_migration
  (schema_version, migration_time)
  VALUES (3, NOW());
