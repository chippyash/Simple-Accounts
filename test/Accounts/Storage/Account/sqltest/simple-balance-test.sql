use simple_accounts;
update sa_coa_ledger SET acCr = 0, acDr = 0;
delete from sa_journal_entry;
delete from sa_journal;
ALTER TABLE sa_journal AUTO_INCREMENT = 1;
ALTER TABLE sa_journal_entry AUTO_INCREMENT = 1;

INSERT INTO sa_journal (chartId, note, rowUid)
VALUES (1, 'foo bar', 12);
INSERT INTO sa_journal_entry (jrnId, nominal, acDr, acCr, rowUid)
VALUES (1, '002120', 10000, 0, 12);
INSERT INTO sa_journal_entry (jrnId, nominal, acDr, acCr, rowUid)
VALUES (1, '007100', 0, 10000, 12);

INSERT INTO sa_journal (chartId, note, rowUid)
VALUES (1, 'foo bar reversal', 12);
INSERT INTO sa_journal_entry (jrnId, nominal, acDr, acCr, rowUid)
VALUES (2, '007100', 10000, 0, 12);
INSERT INTO sa_journal_entry (jrnId, nominal, acDr, acCr, rowUid)
VALUES (2, '002120', 0, 10000, 12);

select
  if(acDr = acCr, true, false) as correctlyBalanced,
  if(acDr = 20000, true, false) as correctTotal,
  if(rowUid = 12, true, false) as correctRowId
from sa_coa_ledger
where id = 1;
