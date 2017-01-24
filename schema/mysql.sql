--
-- MySQL schema
-- ============
--
-- You should normally not be required to care about schema handling.
-- DDOlab does all the migrations for you and guides you either in
-- the frontend or provides everything you need for automated migration
-- handling. Please find more related information in our documentation.

SET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,PIPES_AS_CONCAT,ANSI_QUOTES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER';

CREATE TABLE ddolab_schema_migration (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time DATETIME NOT NULL,
  PRIMARY KEY(schema_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE host_state (
  checksum VARBINARY(20) NOT NULL, -- sha1(hostname)
  host VARCHAR(255) NOT NULL,
  state TINYINT NOT NULL,
  hard_state TINYINT DEFAULT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  attempt TINYINT NOT NULL,
  severity INT UNSIGNED NOT NULL,
  problem ENUM('y', 'n') NOT NULL,
  reachable ENUM('y', 'n') NOT NULL,
  acknowledged ENUM('y', 'n') NOT NULL,
  in_downtime ENUM('y', 'n') NOT NULL,
  last_update BIGINT NOT NULL,
  last_state_change BIGINT NOT NULL,
  last_comment_checksum VARBINARY(20) DEFAULT NULL,
  check_source_checksum VARBINARY(20) DEFAULT NULL,
  PRIMARY KEY (checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE service_state (
  checksum VARBINARY(20) NOT NULL, -- sha1(hostname)
  host VARCHAR(255) NOT NULL,
  service VARCHAR(255) NOT NULL,
  state TINYINT NOT NULL,
  hard_state TINYINT DEFAULT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  attempt TINYINT NOT NULL,
  severity INT UNSIGNED NOT NULL,
  problem ENUM('y', 'n') NOT NULL,
  reachable ENUM('y', 'n') NOT NULL,
  acknowledged ENUM('y', 'n') NOT NULL,
  in_downtime ENUM('y', 'n') NOT NULL,
  last_update BIGINT NOT NULL,
  last_state_change BIGINT NOT NULL,
  last_comment_checksum VARBINARY(20) DEFAULT NULL,
  check_source_checksum VARBINARY(20) DEFAULT NULL,
  PRIMARY KEY (checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ddo_host (
  checksum VARBINARY(20) NOT NULL COMMENT 'sha1(name)',

  name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  name_ci VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

  address VARCHAR(255),
  address6 VARCHAR(255),
  address_bin BINARY(4),
  address6_bin BINARY(16),

  active_checks_enabled ENUM('y', 'n'),
  event_handler_enabled ENUM('y', 'n'),
  flapping_enabled ENUM('y', 'n'),
  notifications_enabled ENUM('y', 'n'),
  passive_checks_enabled ENUM('y', 'n'),
  perfdata_enabled ENUM('y', 'n'),

  check_command VARCHAR(255), -- TODO: -> id
  check_interval INT(10) UNSIGNED,
  check_retry_interval INT(10) UNSIGNED,

--  ctime BIGINT NOT NULL,
--  mtime BIGINT NOT NULL,

  action_url VARCHAR(2083),
  notes_url VARCHAR(2083),

  PRIMARY KEY (checksum),
  UNIQUE KEY idx_host_name (name),
  INDEX idx_host_name_ci (name_ci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ddo_host_group (
  checksum VARBINARY(20) NOT NULL COMMENT 'sha1(name)',

  name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  name_ci VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

--  ctime BIGINT NOT NULL,
--  mtime BIGINT NOT NULL,

  PRIMARY KEY (checksum),
  UNIQUE KEY idx_host_group_name (name),
  INDEX idx_host_group_name_ci (name_ci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ddo_host_group_member (
  host_group_checksum VARBINARY(20) NOT NULL,
  host_checksum VARBINARY(20) NOT NULL,

--  ctime BIGINT NOT NULL,
--  mtime BIGINT NOT NULL,

  PRIMARY KEY (host_group_checksum, host_checksum),

  CONSTRAINT fk_host_group
  FOREIGN KEY (host_group_checksum)
  REFERENCES ddo_host_group (checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT fk_host
  FOREIGN KEY (host_checksum)
  REFERENCES ddo_host (checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ddo_note (
  checksum varbinary(20) NOT NULL COMMENT 'sha1(content)',
  content text NOT NULL,
  PRIMARY KEY (`checksum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ddo_host_note (
  host_checksum varbinary(20) NOT NULL,
  note_checksum varbinary(20) NOT NULL,
  PRIMARY KEY (host_checksum, note_checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO ddolab_schema_migration
  (schema_version, migration_time)
  VALUES (3, NOW());
