ALTER TABLE SMART_ACTIONPROPERTIES ADD (
  MULTIPLEOBJECTS  varchar(2) default '');
ALTER TABLE SMART_PLACEMENTS ADD (
  FRAMETYPE varchar(20) default '',
  SPLINEID varchar(200) default '');

CREATE TABLE SMART_INDESIGNARTICLES (
  OBJID int default 0,
  ARTUID varchar(40) default '',
  NAME varchar(200) default '',
  CODE int default '0',
  PRIMARY KEY (OBJID, ARTUID)
);

CREATE TABLE SMART_IDARTICLESPLACEMENTS (
  OBJID int default 0,
  ARTUID varchar(40) default '',
  PLCID int default 0,
  PRIMARY KEY (OBJID, ARTUID, PLCID)
);

CREATE TABLE SMART_OBJECTOPERATIONS (
  ID int ,
  OBJID int default 0,
  GUID varchar(40) default '',
  TYPE varchar(200) default '',
  NAME varchar(200) default '',
  PARAMS  clob,
  PRIMARY KEY (ID)
);

CREATE SEQUENCE SMART_OBJECTOPERATIONS_SEQ START WITH 100;
CREATE  INDEX OBJID_OBJECTOPERATIONS on SMART_OBJECTOPERATIONS(OBJID) ;
ALTER TABLE SMART_PUBLICATIONS ADD (
  CALCULATEDEADLINES  varchar(2) default '');
ALTER TABLE SMART_ROUTING MODIFY (  ROUTETO varchar(255) default '' );
ALTER TABLE SMART_STATES ADD (
  PHASE varchar(40) default 'Production',
  SKIPIDSA  varchar(2) default '');
ALTER TABLE SMART_TICKETS ADD (
  MASTERTICKETID varchar(40) default '');
CREATE  INDEX MTID_TICKETS on SMART_TICKETS(MASTERTICKETID) ;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_TERMS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
ALTER TABLE SMART_USERS ADD (
  IMPORTONLOGON  varchar(2) default '');
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_MTP_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_MTPSENTOBJECTS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_OBJECTFLAGS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
ALTER TABLE SMART_ISSUES ADD (
  CALCULATEDEADLINES  varchar(2) default '');
ALTER TABLE SMART_PUBLISHHISTORY ADD (
  USERNAME varchar(255) default '');
ALTER TABLE SMART_PUBLISHEDOBJECTSHIST ADD (
  OBJECTNAME varchar(255) default '',
  OBJECTTYPE varchar(40) default '',
  OBJECTFORMAT varchar(128) default '');
ALTER TABLE SMART_INDESIGNSERVERS ADD (
  PRIO1  varchar(2) default 'on',
  PRIO2  varchar(2) default 'on',
  PRIO3  varchar(2) default 'on',
  PRIO4  varchar(2) default 'on',
  PRIO5  varchar(2) default 'on',
  LOCKTOKEN varchar(40) default '');
ALTER TABLE SMART_INDESIGNSERVERJOBS ADD (
  JOBID varchar(40) default '',
  OBJECTMAJORVERSION int default '0',
  OBJECTMINORVERSION int default '0',
  LOCKTOKEN varchar(40) default '',
  JOBSTATUS int default 0,
  JOBCONDITION int default 0,
  JOBPROGRESS int default 0,
  ATTEMPTS int default 0,
  MAXSERVERMAJORVERSION int default '0',
  MAXSERVERMINORVERSION int default '0',
  PRIO int default '3',
  TICKETSEAL varchar(40) default '',
  TICKET varchar(40) default '',
  ACTINGUSER varchar(40) default '',
  INITIATOR varchar(40) default '',
  SERVICENAME varchar(32) default '',
  CONTEXT varchar(64) default '');
ALTER TABLE SMART_INDESIGNSERVERJOBS MODIFY (  ERRORMESSAGE varchar(1024) default '' );
ALTER TABLE SMART_INDESIGNSERVERJOBS RENAME COLUMN SERVERMAJORVERSION TO SERVERMAJORVERSION_OLD;
ALTER TABLE SMART_INDESIGNSERVERJOBS ADD (
  MINSERVERMAJORVERSION int default '0');
UPDATE SMART_INDESIGNSERVERJOBS SET MINSERVERMAJORVERSION = SERVERMAJORVERSION_OLD;
ALTER TABLE SMART_INDESIGNSERVERJOBS DROP (SERVERMAJORVERSION_OLD) CASCADE CONSTRAINTS;
ALTER TABLE SMART_INDESIGNSERVERJOBS RENAME COLUMN SERVERMINORVERSION TO SERVERMINORVERSION_OLD;
ALTER TABLE SMART_INDESIGNSERVERJOBS ADD (
  MINSERVERMINORVERSION int default '0');
UPDATE SMART_INDESIGNSERVERJOBS SET MINSERVERMINORVERSION = SERVERMINORVERSION_OLD;
ALTER TABLE SMART_INDESIGNSERVERJOBS DROP (SERVERMINORVERSION_OLD) CASCADE CONSTRAINTS;
CREATE  INDEX OBJID_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(OBJID) ;
CREATE  INDEX PRID_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(PRIO, JOBID) ;
CREATE  INDEX TS_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(TICKETSEAL) ;
CREATE  INDEX TTJTSTRT_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(TICKET, JOBTYPE, STARTTIME, READYTIME) ;
CREATE  INDEX JP_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(JOBPROGRESS) ;
CREATE  INDEX JSPR_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(JOBSTATUS, PRIO, QUEUETIME) ;
CREATE  INDEX LT_INDESIGNSERVERJOBS on SMART_INDESIGNSERVERJOBS(LOCKTOKEN) ;
ALTER TABLE SMART_INDESIGNSERVERJOBS DROP PRIMARY KEY ;
ALTER TABLE SMART_INDESIGNSERVERJOBS ADD PRIMARY KEY (JOBID) ;
ALTER TABLE SMART_INDESIGNSERVERJOBS DROP (ID) CASCADE CONSTRAINTS;
ALTER TABLE SMART_INDESIGNSERVERJOBS DROP (EXCLUSIVELOCK) CASCADE CONSTRAINTS;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_INDESIGNSERVERJOBS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
ALTER TABLE SMART_SERVERJOBS ADD (
  JOBID varchar(40) default '',
  ATTEMPTS int default 0,
  ERRORMESSAGE varchar(1024) default '',
  JOBDATA  clob,
  DATAENTITY varchar(20) default '');
ALTER TABLE SMART_SERVERJOBS MODIFY (  QUEUETIME varchar(30) default '' );
CREATE  INDEX JOBINFO on SMART_SERVERJOBS(LOCKTOKEN, JOBSTATUS, JOBPROGRESS) ;
CREATE  INDEX ASLT_SERVERJOBS on SMART_SERVERJOBS(ASSIGNEDSERVERID, LOCKTOKEN) ;
CREATE  INDEX PAGED_RESULTS on SMART_SERVERJOBS(QUEUETIME, SERVERTYPE, JOBTYPE, JOBSTATUS, ACTINGUSER) ;
ALTER TABLE SMART_SERVERJOBS DROP PRIMARY KEY ;
ALTER TABLE SMART_SERVERJOBS ADD PRIMARY KEY (JOBID) ;
ALTER TABLE SMART_SERVERJOBS DROP (ID) CASCADE CONSTRAINTS;
ALTER TABLE SMART_SERVERJOBS DROP (OBJID) CASCADE CONSTRAINTS;
ALTER TABLE SMART_SERVERJOBS DROP (MINORVERSION) CASCADE CONSTRAINTS;
ALTER TABLE SMART_SERVERJOBS DROP (MAJORVERSION) CASCADE CONSTRAINTS;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_SERVERJOBS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;

CREATE TABLE SMART_SERVERJOBTYPESONHOLD (
  GUID varchar(40) default '',
  JOBTYPE varchar(32) default '',
  RETRYTIMESTAMP varchar(20) default '',
  PRIMARY KEY (GUID)
);
CREATE  INDEX JOBTYPE on SMART_SERVERJOBTYPESONHOLD(JOBTYPE) ;
CREATE  INDEX RETRYTIME on SMART_SERVERJOBTYPESONHOLD(RETRYTIMESTAMP) ;
ALTER TABLE SMART_SERVERJOBCONFIGS ADD (
  USERCONFIGNEEDED  varchar(1) default 'Y',
  SELFDESTRUCTIVE  varchar(1) default 'N');
ALTER TABLE SMART_SEMAPHORES ADD (
  LIFETIME int default '0');
ALTER TABLE SMART_OBJECTLABELS MODIFY (  NAME varchar(250) default '' );
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_OBJECTRELATIONLABELS_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE SMART_CHANNELDATA_SEQ'; EXCEPTION WHEN OTHERS THEN NULL; END;
UPDATE SMART_CONFIG set VALUE = '10.0' where NAME = 'version';

grant select,insert,update,delete on SMART_INDESIGNARTICLES to woodwing;
grant select,insert,update,delete on SMART_IDARTICLESPLACEMENTS to woodwing;
grant select on SMART_OBJECTOPERATIONS_SEQ to woodwing;
grant select,insert,update,delete on SMART_OBJECTOPERATIONS to woodwing;
grant select,insert,update,delete on SMART_SERVERJOBTYPESONHOLD to woodwing;
