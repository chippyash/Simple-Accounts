#
# Ensure that rowSts stays in sync between sa_org, sa_coa and sa_coa_ledger
# NB - once you set a rowSts == defunct, it cannot be reverted
#
DROP TRIGGER IF EXISTS ts_check_pre_sa_org;

CREATE TRIGGER ts_check_pre_sa_org BEFORE UPDATE ON sa_org
FOR EACH ROW
  BEGIN
    # Do not allow defunct records to be amended
    IF OLD.rowSts = 'defunct' THEN
      SIGNAL SQLSTATE 'XAE05'
      SET MESSAGE_TEXT = 'Record is defunct.  Cannot update', MYSQL_ERRNO = 1398;
    END IF;
  END;

DROP TRIGGER IF EXISTS ts_check_post_sa_org;

CREATE TRIGGER ts_check_post_sa_org AFTER UPDATE ON sa_org
FOR EACH ROW
  BEGIN
    # Cascade status change if necessary
    if OLD.rowSts != NEW.rowSts THEN
      update sa_coa set rowSts = NEW.rowSts WHERE orgId = NEW.id;
    END IF;
  END;

DROP TRIGGER IF EXISTS ts_check_pre_sa_coa;

CREATE TRIGGER ts_check_pre_sa_coa BEFORE UPDATE ON sa_coa
FOR EACH ROW
  BEGIN
    # Do not allow defunct records to be amended
    IF OLD.rowSts = 'defunct' THEN
      SIGNAL SQLSTATE 'XAE05'
      SET MESSAGE_TEXT = 'Record is defunct.  Cannot update', MYSQL_ERRNO = 1398;
    END IF;
  END;

DROP TRIGGER IF EXISTS ts_check_post_sa_coa;

CREATE TRIGGER ts_check_post_sa_coa AFTER UPDATE ON sa_coa
FOR EACH ROW
  BEGIN
    # Cascade status change if necessary
    if OLD.rowSts != NEW.rowSts THEN
      update sa_coa_ledger set rowSts = NEW.rowSts WHERE chartId = NEW.id;
    END IF;
  END;