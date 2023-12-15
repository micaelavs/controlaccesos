ALTER TABLE `empleados` 
ADD COLUMN `planilla_reloj` TINYINT(1) NOT NULL DEFAULT '1' AFTER `email`;

ALTER TABLE `empleado_contrato` 
CHANGE COLUMN `cargo` `cargo` INT(11) NULL DEFAULT '1' ;

INSERT INTO db_version VALUES('5', now());