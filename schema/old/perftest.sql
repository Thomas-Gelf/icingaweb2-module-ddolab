-- plain simple schema, used to debug duplicated events
-- not for productional use, extremely redundant and fat

CREATE TABLE object_state (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  host varchar(64) NOT NULL,
  service varchar(128) DEFAULT NULL,
  check_source varchar(64) NOT NULL,
  state tinyint(4) DEFAULT NULL,
  state_type tinyint(4) DEFAULT NULL,
  reachable tinyint(4) DEFAULT NULL,
  attempt tinyint(4) DEFAULT NULL,
  schedule_start bigint(20) unsigned NOT NULL,
  schedule_end bigint(20) unsigned NOT NULL,
  execution_start bigint(20) unsigned NOT NULL,
  execution_end bigint(20) unsigned NOT NULL,
  command text,
  exit_status tinyint(4) DEFAULT NULL,
  output text,
  performance_data text,
  former_state tinyint(4) DEFAULT NULL,
  former_state_type tinyint(4) DEFAULT NULL,
  former_reachable tinyint(4) DEFAULT NULL,
  former_attempt tinyint(4) DEFAULT NULL,
  severity tinyint(4) DEFAULT NULL,
  is_problem enum('y','n') DEFAULT NULL,
  state_change enum('y','n') DEFAULT NULL,
  hard_state_change enum('y','n') DEFAULT NULL,
  reachability_change enum('y','n') DEFAULT NULL,
  PRIMARY KEY (id),
  KEY host (host),
  KEY service (service),
  KEY object (host,service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE object_checkresult_history (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  host varchar(64) NOT NULL,
  service varchar(128) DEFAULT NULL,
  check_source varchar(64) NOT NULL,
  timestamp bigint(20) unsigned NOT NULL,
  active tinyint(3) unsigned DEFAULT NULL,
  schedule_start bigint(20) unsigned NOT NULL,
  schedule_end bigint(20) unsigned NOT NULL,
  execution_start bigint(20) unsigned NOT NULL,
  execution_end bigint(20) unsigned NOT NULL,
  exit_status tinyint(4) DEFAULT NULL,
  state tinyint(4) DEFAULT NULL,
  command text,
  output text,
  performance_data text,
  attempt_before tinyint(4) DEFAULT NULL,
  attempt_after tinyint(4) DEFAULT NULL,
  reachable_before tinyint(4) DEFAULT NULL,
  reachable_after tinyint(4) DEFAULT NULL,
  state_before tinyint(4) DEFAULT NULL,
  state_after tinyint(4) DEFAULT NULL,
  state_type_before tinyint(4) DEFAULT NULL,
  state_type_after tinyint(4) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY host (host),
  KEY service (service),
  KEY object (host,service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE object_state_history (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  host varchar(64) NOT NULL,
  service varchar(128) DEFAULT NULL,
  check_source varchar(64) NOT NULL,
  timestamp bigint(20) unsigned NOT NULL,
  active tinyint(3) unsigned DEFAULT NULL,
  schedule_start bigint(20) unsigned NOT NULL,
  schedule_end bigint(20) unsigned NOT NULL,
  execution_start bigint(20) unsigned NOT NULL,
  execution_end bigint(20) unsigned NOT NULL,
  exit_status tinyint(4) DEFAULT NULL,
  state tinyint(4) DEFAULT NULL,
  command text,
  output text,
  performance_data text,
  attempt_before tinyint(4) DEFAULT NULL,
  attempt_after tinyint(4) DEFAULT NULL,
  reachable_before tinyint(4) DEFAULT NULL,
  reachable_after tinyint(4) DEFAULT NULL,
  state_before tinyint(4) DEFAULT NULL,
  state_after tinyint(4) DEFAULT NULL,
  state_type_before tinyint(4) DEFAULT NULL,
  state_type_after tinyint(4) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY host (host),
  KEY service (service),
  KEY object (host,service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
