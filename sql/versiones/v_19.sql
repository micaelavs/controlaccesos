ALTER TABLE `empleados` ADD `oficina_contacto` VARCHAR(25)  NULL AFTER `planilla_reloj`;

ALTER TABLE `empleados` ADD `oficina_interno` VARCHAR(15) NULL AFTER `oficina_contacto`;

ALTER TABLE empleados ADD observacion VARCHAR(255) NULL;

INSERT INTO db_version VALUES('19', now());