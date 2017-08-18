DELIMITER //

create table sa_ac_type
(
  type varchar(10) not null
    primary key,
  value smallint not null
)
  comment 'Account type enumeration'
;

create table sa_coa
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

create table sa_crcy
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

alter table sa_coa
  add constraint sa_coa_sa_crcy_code_fk
foreign key (crcyCode) references sa_crcy (code)
;

create table sa_journal
(
  id int(10) unsigned auto_increment
    primary key,
  orgId int(10) unsigned null,
  note text not null,
  date datetime default CURRENT_TIMESTAMP null,
  rowDt timestamp default CURRENT_TIMESTAMP not null,
  rowUid int(10) unsigned default '0' null,
  rowSts enum('active', 'suspended', 'defunct') default 'active' null
)
  comment 'Transaction Journal'
;

create index sa_journal_sa_org_id_fk
  on sa_journal (orgId)
;

create table sa_journal_entry
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

create index sa_journal_entry_sa_crcy_code_fk
  on sa_journal_entry (crcyCode)
;

create index sa_journal_entry_sa_org_id_fk
  on sa_journal_entry (jrnId)
;

create trigger journal_trigger
after INSERT on sa_journal_entry
for each row
  BEGIN
    DECLARE vOrgId INT UNSIGNED;

    SELECT orgId from sa_journal j where NEW.jrnId = j.id INTO vOrgId;

    call update_coa(vOrgId, NEW.nominal, NEW.acDr, NEW.acCr);

  END;

create table sa_org
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

create index sa_org_sa_crcy_code_fk
  on sa_org (crcyCode)
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

drop TRIGGER journal_trigger;

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
DELIMITER;