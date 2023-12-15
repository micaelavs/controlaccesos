ALTER TABLE `credenciales` 
ADD COLUMN `tipo_acceso` INT(8) NULL DEFAULT 0 AFTER `acceso_id`;

INSERT INTO db_version VALUES('21', now());