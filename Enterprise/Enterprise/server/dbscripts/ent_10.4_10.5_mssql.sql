CREATE  INDEX [tmid_messagelog] ON [smart_messagelog]([threadmessageid]) ;
UPDATE [smart_config] set [value] = '10.5' where [name] = 'version';
