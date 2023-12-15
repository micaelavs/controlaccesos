ALTER TABLE `relojes`
ADD COLUMN `dns` varchar(20) AFTER `puerto`;


INSERT INTO db_version VALUES('16', now());