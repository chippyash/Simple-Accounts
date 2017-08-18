# Installation script for database support of ZendDB
# Mysql 5.6

DELIMITER //

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
  rowDt timestamp default CURRENT_TIMESTAMP null,
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
  rowDt timestamp default CURRENT_TIMESTAMP not null,
  rowUid int(10) unsigned default '0' not null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  constraint sa_org_name_uindex
  unique (name),
  constraint sa_org_sa_crcy_code_fk
  foreign key (crcyCode) references sa_crcy (code)
)
  comment 'An Organisation'
;

create table IF NOT EXISTS sa_coa
(
  id int(10) unsigned auto_increment
    primary key,
  prntId int(10) unsigned null,
  orgId int(10) unsigned null,
  nominal char(6) not null,
  type varchar(10) null,
  name varchar(30) not null,
  crcyCode char(3) default 'GBP' null,
  acDr bigint default '0' null,
  acCr bigint default '0' not null,
  rowDt timestamp default CURRENT_TIMESTAMP not null,
  rowUid int(10) unsigned default '0' null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  constraint sa_coa_sa_coa_id_fk
  foreign key (prntId) references sa_coa (id),
  constraint sa_coa_sa_ac_type_type_fk
  foreign key (type) references sa_ac_type (type)
)
  comment 'Chart of Account'
;

create table IF NOT EXISTS sa_journal
(
  id int(10) unsigned auto_increment
    primary key,
  orgId int(10) unsigned not null,
  note text not null,
  date DATETIME default CURRENT_TIMESTAMP null,
  rowDt timestamp default CURRENT_TIMESTAMP not null,
  rowUid int(10) unsigned default '0',
  rowSts enum('active', 'suspended', 'defunct') default 'active' null
)
  comment 'Transaction Journal'
;


create table IF NOT EXISTS sa_journal_entry
(
  id int(10) unsigned auto_increment
    primary key,
  jrnId int(10) unsigned null,
  nominal char(6) not null,
  crcyCode char(3) not null,
  acDr bigint default '0' null,
  acCr bigint default '0' null,
  rowDt timestamp default CURRENT_TIMESTAMP not null,
  rowUid int(10) unsigned default '0' null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null,
  constraint sa_journal_entry_sa_org_id_fk
  foreign key (jrnId) references sa_journal (id),
  constraint sa_journal_entry_sa_crcy_code_fk
  foreign key (crcyCode) references sa_crcy (code)
)
  comment 'Transaction Journal'
;

INSERT INTO simple_accounts.sa_org (name, crcyCode, rowDt, rowUid, rowSts) VALUES ('Test', 'GBP', '2017-08-18 08:20:12', 0, 'active');

INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('ASSET', 11);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('BANK', 27);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('CR', 5);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('CUSTOMER', 44);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('DR', 3);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('DUMMY', 0);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('EQUITY', 645);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('EXPENSE', 77);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('INCOME', 389);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('LIABILITY', 133);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('REAL', 1);
INSERT INTO simple_accounts.sa_ac_type (type, value) VALUES ('SUPPLIER', 1157);

INSERT INTO simple_accounts.sa_crcy (code, rowDt, rowUid, rowSts) VALUES ('GBP', '2017-08-18 10:03:31', 0, 'active');
INSERT INTO simple_accounts.sa_crcy (code, rowDt, rowUid, rowSts) VALUES ('EUR', '2017-08-18 10:04:10', 0, 'active');
INSERT INTO simple_accounts.sa_crcy (code, rowDt, rowUid, rowSts) VALUES ('USD', '2017-08-18 10:04:10', 0, 'active');

INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (null, 1, '000000', 'REAL', 'COA', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (1, 1, '001000', 'REAL', 'Balance Sheet', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (1, 1, '005000', 'REAL', 'Profit And Loss', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (2, 1, '002000', 'REAL', 'Assets', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (4, 1, '002100', 'BANK', 'At Bank', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (5, 1, '002110', 'BANK', 'Current Account', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (5, 1, '002120', 'BANK', 'Savings Account', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (4, 1, '003000', 'LIABILITY', 'Liabilities', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (8, 1, '003100', 'EQUITY', 'Equity', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (9, 1, '003110', 'EQUITY', 'Opening Balance', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (8, 1, '003200', 'LIABILITY', 'Loans', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (11, 1, '003210', 'LIABILITY', 'Mortgage', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (3, 1, '006000', 'INCOME', 'Income', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (13, 1, '006100', 'INCOME', 'Salary', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (13, 1, '006200', 'INCOME', 'Interest Received', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (3, 1, '007000', 'EXPENSE', 'Expenses', 'GBP', 0, 0, '2017-08-18 12:46:01', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007100', 'EXPENSE', 'House', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007200', 'EXPENSE', 'Travel', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007300', 'EXPENSE', 'Insurance', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007400', 'EXPENSE', 'Food', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007500', 'EXPENSE', 'Leisure', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');
INSERT INTO simple_accounts.sa_coa (prntId, orgId, nominal, type, name, crcyCode, acDr, acCr, rowDt, rowUid, rowSts) VALUES (16, 1, '007600', 'EXPENSE', 'Interest Payments', 'GBP', 0, 0, '2017-08-18 09:00:38', 0, 'active');


create index sa_org_sa_crcy_code_fk
  on sa_org (crcyCode)
;

create index sa_coa_sa_coa_id_fk
  on sa_coa (prntId)
;

create index sa_coa_sa_org_id_fk
  on sa_coa (orgId)
;

create index sa_coa_sa_ac_type_type_fk
  on sa_coa (type)
;

create index sa_coa_sa_crcy_code_fk
  on sa_coa (crcyCode)
;

create index sa_journal_sa_org_id_fk
  on sa_journal (orgId)
;

create index sa_journal_entry_sa_crcy_code_fk
  on sa_journal_entry (crcyCode)
;

create index sa_journal_entry_sa_org_id_fk
  on sa_journal_entry (jrnId)
;

alter table sa_coa
  add constraint sa_coa_sa_crcy_code_fk
foreign key (crcyCode) references sa_crcy (code)
;

alter table sa_coa
  add constraint sa_coa_sa_org_id_fk
foreign key (orgId) references sa_org (id)
;

alter table sa_journal
  add constraint sa_journal_sa_org_id_fk
foreign key (orgId) references sa_org (id)
;

create procedure update_coa (IN orgId int(10) unsigned, IN nominal char(6), IN dr bigint, IN cr bigint)
  BEGIN
    DECLARE parent BIGINT UNSIGNED;
    DECLARE prntNom char(6);

    # Update the nominal account entry
    UPDATE sa_coa c SET acDr = acDr + dr, acCr = acCr + cr
    WHERE c.orgId = orgId
          AND c.nominal = nominal;

    #
    SELECT prntId from sa_coa c
    WHERE c.orgId = orgId
          AND c.nominal = nominal
    INTO parent;

    IF parent != 0 THEN
      SELECT nominal FROM sa_coa c
      WHERE c.id = parent
      INTO prntNom;

      CALL update_coa(orgId, prntNom, dr, cr);

    END IF;
  END;

# drop TRIGGER journal_trigger;

SET @@GLOBAL.max_sp_recursion_depth = 255;
SET @@session.max_sp_recursion_depth = 10;

create TRIGGER journal_trigger
AFTER INSERT
  ON sa_journal_entry FOR EACH ROW

  BEGIN
    DECLARE vOrgId INT UNSIGNED;

    SELECT orgId from sa_journal j where NEW.jrnId = j.id INTO vOrgId;

    SET @@GLOBAL.max_sp_recursion_depth = 255;
    SET @@session.max_sp_recursion_depth = 10;
    call update_coa(vOrgId, NEW.nominal, NEW.acDr, NEW.acCr);

  END; //

DELIMITER ;