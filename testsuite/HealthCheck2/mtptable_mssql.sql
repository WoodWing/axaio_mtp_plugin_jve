CREATE TABLE [{DBPREFIX}axaio_mtp_trigger] (
  [publication_id] int not null ,
  [issue_id] int not null  default 0,
  [state_trigger_layout] int not null ,
  [state_trigger_article] int not null  default 0,
  [state_trigger_image] int not null  default 0,
  [state_after_layout] int not null  default 0,
  [state_after_article] int not null  default 0,
  [state_after_image] int not null  default 0,
  [mtp_jobname] text not null  default '',
  [state_error_layout] int NOT NULL DEFAULT 0,
  [quiet] tinyint DEFAULT 0,
  [prio] tinyint DEFAULT 2,

  PRIMARY KEY ([publication_id], [issue_id], [state_trigger_layout],[state_trigger_article],[state_trigger_image])
);
CREATE  INDEX [ii_mtp] on [{DBPREFIX}axaio_mtp_trigger]([issue_id]) ;

CREATE TABLE [{DBPREFIX}axaio_mtp_sentobjects] (
  [objid] int not null  default 0,
  [publication_id] int not null ,
  [issue_id] int not null  default 0,
  [state_trigger_layout] int not null ,
  [printstate] int not null ,
  PRIMARY KEY ([objid], [publication_id], [issue_id], [state_trigger_layout], [printstate])
);
CREATE  INDEX [ii_mtp_sentobjects] on [{DBPREFIX}axaio_mtp_sentobjects]([issue_id]) ;
CREATE  INDEX [ls_mtp_sentobjects] on [{DBPREFIX}axaio_mtp_sentobjects]([state_trigger_layout]) ;

CREATE TABLE [{DBPREFIX}axaio_mtp_process_options] (
  [id] int not null  IDENTITY(1,1),
  [option_name] text not null ,
  [option_value]  text not null  default '',
  PRIMARY KEY ([id])
);
