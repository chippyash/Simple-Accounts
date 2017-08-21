# Create a test organisation and chart of accounts
# Run this immediately after the db-support.sql script as it messes with auto increment
# values
# MariaDb 10

use simple_accounts;

INSERT INTO sa_org (id, name, crcyCode, rowDt, rowUid, rowSts) VALUES
  (1, 'Test', 'GBP', '2017-08-18 08:20:12', 0, 'active');

ALTER TABLE sa_org AUTO_INCREMENT=2;

INSERT INTO sa_coa (id, orgId, name, crcyCode) VALUES
  (1, 1, 'Test', 'GBP');

ALTER TABLE sa_coa AUTO_INCREMENT=2;

INSERT INTO sa_coa_ledger (id, chartId, nominal, type, name) VALUES
  (1, 1, '000000', 'REAL', 'COA'),
  (2, 1, '001000', 'REAL', 'Balance Sheet'),
  (3, 1, '005000', 'REAL', 'Profit And Loss'),
  (4, 1, '002000', 'REAL', 'Assets'),
(5, 1, '002100', 'BANK', 'At Bank'),
(6, 1, '002110', 'BANK', 'Current Account'),
(7, 1, '002120', 'BANK', 'Savings Account'),
(8, 1, '003000', 'LIABILITY', 'Liabilities'),
(9, 1, '003100', 'EQUITY', 'Equity'),
(10, 1, '003110', 'EQUITY', 'Opening Balance'),
(11, 1, '003200', 'LIABILITY', 'Loans'),
(12, 1, '003210', 'LIABILITY', 'Mortgage'),
(13, 1, '006000', 'INCOME', 'Income'),
(14, 1, '006100', 'INCOME', 'Salary'),
(15, 1, '006200', 'INCOME', 'Interest Received'),
(16, 1, '007000', 'EXPENSE', 'Expenses'),
(17, 1, '007100', 'EXPENSE', 'House'),
(18, 1, '007200', 'EXPENSE', 'Travel'),
(19, 1, '007300', 'EXPENSE', 'Insurance'),
(20, 1, '007400', 'EXPENSE', 'Food'),
(21, 1, '007500', 'EXPENSE', 'Leisure'),
(22, 1, '007600', 'EXPENSE', 'Interest Payments');

ALTER TABLE sa_coa_ledger AUTO_INCREMENT=23;

INSERT INTO sa_coa_link (prnt, child) VALUES
  (1,2),
  (1,3),
  (2,4),
  (4,5),
  (4,8),
  (5,6),
  (5,7),
  (8,9),
  (8,11),
  (9,10),
  (11,12),
  (3,13),
  (3,16),
  (13,14),
  (13,15),
  (16,17),
  (16,18),
  (16,19),
  (16,20),
  (16,21),
  (16,22);
