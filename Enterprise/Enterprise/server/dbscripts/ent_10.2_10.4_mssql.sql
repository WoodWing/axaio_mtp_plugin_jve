ALTER TABLE [smart_deletedobjects] ADD 
  [masterid] bigint NOT NULL  default '0';
ALTER TABLE [smart_objects] ADD 
  [masterid] bigint NOT NULL  default '0';
UPDATE [smart_config] set [value] = '10.4' where [name] = 'version';
