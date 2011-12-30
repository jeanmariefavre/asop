SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ALL_SocialObject;
CREATE TABLE ALL_SocialObject (
  soid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kind CHAR(20) NOT NULL,
  PRIMARY KEY (soid)
)ENGINE=InnoDB;

-- CREATE TABLE Tag (...)

-- DIRECTORY
DROP TABLE IF EXISTS DIR_Actor;
CREATE TABLE DIR_Actor (
  soid BIGINT UNSIGNED NOT NULL,
  name VARCHAR(64) NOT NULL,
  owner BIGINT UNSIGNED NULL,
  PRIMARY KEY(soid),
  UNIQUE KEY(name),
  FOREIGN KEY(soid) REFERENCES ALL_SocialObject(soid),
  FOREIGN KEY(owner) REFERENCES DIR_Actor(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS DIR_Perspective;
CREATE TABLE DIR_Perspective (
  perspective_url VARCHAR(100) NOT NULL,
  owner BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(perspective_url),
  FOREIGN KEY(owner) REFERENCES DIR_Actor(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS DIR_Membership;
CREATE TABLE DIR_Membership (
  groop BIGINT UNSIGNED NOT NULL,
  member BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(groop,member),
  FOREIGN KEY(groop) REFERENCES DIR_Actor(soid),
  FOREIGN KEY(member) REFERENCES DIR_Actor(soid)
)ENGINE=InnoDB;

-- REPOSITORY
DROP TABLE IF EXISTS REP_Perspective;
CREATE TABLE REP_Perspective (
  soid BIGINT UNSIGNED NOT NULL,
  name VARCHAR(64) NOT NULL,
  owner VARCHAR(16) NOT NULL,
  description TEXT,
  PRIMARY KEY(soid),
  UNIQUE KEY(name),
  FOREIGN KEY(soid) REFERENCES ALL_SocialObject(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_ClassFragment;
CREATE TABLE REP_ClassFragment (
  soid BIGINT UNSIGNED NOT NULL,
  perspective BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  PRIMARY KEY(soid),
  UNIQUE KEY(perspective,name),
  FOREIGN KEY(soid) REFERENCES ALL_SocialObject(soid),
  FOREIGN KEY(perspective) REFERENCES REP_Perspective(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_Attribute;
CREATE TABLE REP_Attribute (
  soid BIGINT UNSIGNED NOT NULL,
  classFragment BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  type VARCHAR(80) NOT NULL,
  positionInLabel TINYINT NOT NULL,
  PRIMARY KEY(soid),
  UNIQUE KEY(classFragment,name),
  FOREIGN KEY(soid) REFERENCES ALL_SocialObject(soid),
  FOREIGN KEY(classFragment) REFERENCES REP_ClassFragment(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_ClassExtension;
CREATE TABLE REP_ClassExtension (
  child BIGINT UNSIGNED NOT NULL,
  parent VARCHAR(100) NOT NULL,
  PRIMARY KEY(child,parent),
  FOREIGN KEY(child) REFERENCES REP_ClassFragment(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_ImportDeclaration;
CREATE TABLE REP_ImportDeclaration (
  perspective BIGINT UNSIGNED NOT NULL,
  classFragment VARCHAR(100) NOT NULL,
  import BOOL NOT NULL,
  PRIMARY KEY(perspective,classFragment),
  FOREIGN KEY(perspective) REFERENCES REP_Perspective(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_InstanceFragment;
CREATE TABLE REP_InstanceFragment (
  soid BIGINT UNSIGNED NOT NULL,
  classFragment BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(soid,classFragment),
  FOREIGN KEY(soid) REFERENCES ALL_SocialObject(soid),
  FOREIGN KEY(classFragment) REFERENCES REP_ClassFragment(soid)
)ENGINE=InnoDB;

DROP TABLE IF EXISTS REP_Value;
CREATE TABLE REP_Value (
  soid BIGINT UNSIGNED NOT NULL,
  attribute BIGINT UNSIGNED NOT NULL,
  value VARCHAR(256) NOT NULL,
  PRIMARY KEY(soid,attribute),
  FOREIGN KEY(soid) REFERENCES REP_InstanceFragment(soid),
  FOREIGN KEY(attribute) REFERENCES REP_Attribute(soid)
)ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;