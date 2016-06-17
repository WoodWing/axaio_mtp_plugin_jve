CREATE TABLE [smart_claro] (
[id] int not null IDENTITY(1,1),
[oid] int NOT NULL default '0' ,
[cropx] real NOT NULL default '0',
[cropy] real NOT NULL default '0',
[rotate] real NOT NULL default '0',
[width] real NOT NULL default '0',
[height] real NOT NULL default '0'
);

CREATE INDEX [oid_claro] on [smart_claro] ([oid]) ;
