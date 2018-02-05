ALTER TABLE [smart_objectlocks] ADD 
  [ticketid] varchar(40) NOT NULL  default '';
UPDATE [smart_config] set [value] = '10.3' where [name] = 'version';
