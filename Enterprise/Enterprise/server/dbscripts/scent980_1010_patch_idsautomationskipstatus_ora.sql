ALTER TABLE SMART_STATES ADD (
  SKIPIDSA  varchar(2) default '');
INSERT INTO SMART_CONFIG (ID, NAME, VALUE) 
SELECT SMART_CONFIG_seq.nextval ,'idsautomationskipstatus', 'yes' FROM DUAL;
