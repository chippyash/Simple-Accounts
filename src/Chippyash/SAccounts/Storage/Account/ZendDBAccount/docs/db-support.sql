# Installation script for database support of ZendDB
# MariaDb 10

create DATABASE IF NOT EXISTS simple_accounts;

use simple_accounts;

create table IF NOT EXISTS sa_ac_type
(
  type varchar(10) not null
    primary key,
  value smallint not null
)
  comment 'Account type enumeration'
;

create table IF NOT EXISTS sa_crcy
(
  id int(10) unsigned auto_increment
    primary key,
  code char(3) not null,
  rowDt timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP null,
  rowUid int(10) unsigned default '0' null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  constraint sa_crcy_code_uindex
  unique (code)
)
  comment 'Currencies'
;

create table IF NOT EXISTS sa_org
(
  id int(10) unsigned auto_increment
    primary key,
  name varchar(30) not null,
  crcyCode char(3) default 'GBP' null,
  rowDt timestamp default CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
  rowUid int(10) unsigned default '0' not null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  constraint sa_org_name_uindex
  unique (name),
  constraint sa_org_sa_crcy_code_fk
  foreign key (crcyCode) references sa_crcy (code)
)
  comment 'An Organisation'
;
create index sa_org_sa_crcy_code_fk
  on sa_org (crcyCode)
;


create table IF NOT EXISTS sa_coa (
  id INT(10) UNSIGNED auto_increment primary key,
  orgId INT(10) UNSIGNED not null,
  name VARCHAR (20) not null,
  crcyCode char(3) default 'GBP' not null,
  rowDt timestamp default CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
  rowUid int(10) unsigned default '0' not null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  CONSTRAINT sa_coa_sa_org_id_fk
  FOREIGN KEY (orgId) REFERENCES sa_org (id),
  CONSTRAINT sa_coa_sa_crcy_code_fk
  FOREIGN KEY (crcyCode) REFERENCES sa_crcy (code),
  CONSTRAINT sa_coa_orgId_name_idx UNIQUE (orgId, name)
)
  comment 'A Chart of Account for an Organisation'
;
create index sa_coa_sa_org_id_fk
  on sa_coa (orgId)
;
create index sa_coa_sa_crcy_code_fk
  on sa_coa (crcyCode)
;

create table IF NOT EXISTS sa_coa_ledger
(
  id int(10) unsigned auto_increment
    primary key,
  chartId int(10) unsigned,
  nominal char(6) not null,
  type varchar(10) null,
  name varchar(30) not null,
  acDr bigint default '0' not null,
  acCr bigint default '0' not null,
  rowDt timestamp default CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
  rowUid int(10) unsigned default '0',
  rowSts enum('active', 'suspended', 'defunct') default 'active',
  CONSTRAINT sa_coa_ledger_sa_ac_type_type_fk
  FOREIGN KEY (type) REFERENCES sa_ac_type (type),
  CONSTRAINT sa_coa_ledger_sa_coa_fk
  FOREIGN KEY (chartId) REFERENCES sa_coa (id)
)
  comment 'Chart of Account structure'
;
create index sa_coa_ledger_sa_ac_type_type_fk
  on sa_coa_ledger (type)
;
create index sa_coa_ledger_sa_coa_fk
  on sa_coa_ledger (chartId)
;
CREATE UNIQUE INDEX sa_coa_ledger_chartId_nominal_index
  ON sa_coa_ledger (chartId, nominal)
;

create table IF NOT EXISTS sa_journal
(
  id int(10) unsigned auto_increment
    primary key,
  chartId int(10) unsigned not null,
  note text not null,
  date DATETIME default CURRENT_TIMESTAMP null,
  ref int UNSIGNED null,
  CONSTRAINT sa_journal_sa_coa_id_fk
  FOREIGN KEY (chartId) REFERENCES sa_coa (id)
)
  comment 'Transaction Journal'
;
create index sa_journal_sa_coa_id_fk
  on sa_journal (chartId)
;

create table IF NOT EXISTS sa_journal_entry
(
  id int(10) unsigned auto_increment
    primary key,
  jrnId int(10) unsigned null,
  nominal char(6) not null,
  acDr bigint default '0' null,
  acCr bigint default '0' null,
  constraint sa_journal_entry_sa_jrn_id_fk
  foreign key (jrnId) references sa_journal (id)
)
  comment 'Transaction Journal'
;
create index sa_journal_entry_sa_org_id_fk
  on sa_journal_entry (jrnId)
;

create table if not exists sa_coa_link
(
  prnt int(10) unsigned default '0' not null,
  child int(10) unsigned default '0' not null,
  rowDt timestamp default CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
  rowUid int(10) unsigned default '0' null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  primary key (prnt, child)
)
  comment 'Graph link backing table for sa_coa'
;
create index sa_coa_link_child_index
  on sa_coa_link (child)
;

CREATE TABLE sa_coa_graph (
  latch VARCHAR(32) NULL,
  origid BIGINT UNSIGNED NULL,
  destid BIGINT UNSIGNED NULL,
  weight DOUBLE NULL,
  seq BIGINT UNSIGNED NULL,
  linkid BIGINT UNSIGNED NULL,
  KEY (latch, origid, destid) USING HASH,
  KEY (latch, destid, origid) USING HASH
)
  ENGINE=OQGRAPH data_table='sa_coa_link' origid='prnt' destid='child'
;

INSERT INTO sa_ac_type (type, value) VALUES
  ('ASSET', 11),
  ('BANK', 27),
  ('CR', 5),
  ('CUSTOMER', 44),
  ('DR', 3),
  ('DUMMY', 0),
  ('EQUITY', 645),
  ('EXPENSE', 77),
  ('INCOME', 389),
  ('LIABILITY', 133),
  ('REAL', 1),
  ('SUPPLIER', 1157);

INSERT INTO sa_crcy (code) VALUES ('GBP'), ('EUR'), ('USD');

drop TRIGGER IF EXISTS journal_trigger;

create TRIGGER journal_trigger
AFTER INSERT
  ON sa_journal_entry FOR EACH ROW

  BEGIN
    DECLARE vCoa INT UNSIGNED;

    SELECT l.id FROM sa_coa_ledger l
      LEFT JOIN sa_journal j ON l.chartId = j.chartId
      WHERE j.id = NEW.jrnId
      AND l.nominal = NEW.nominal
      INTO vCoa;

    update sa_coa_ledger set
      acDr = acDr + NEW.acDr,
      acCr = acCr + NEW.acCr,
      rowUid = NEW.rowUid
    where id IN (
      SELECT linkid
      FROM sa_coa_graph
      WHERE
        latch = 'breadth_first'
        AND origid = 1
        AND destid = vCoa
    );
  END;
