ALTER TABLE `direcciones_organismo` 
CHANGE COLUMN `nombre` `nombre` VARCHAR(80) NOT NULL AFTER `id`,
CHANGE COLUMN `borrado` `id_padre` INT(8) NOT NULL ,
ADD COLUMN `fecha_desde` DATE NULL DEFAULT NULL AFTER `id_padre`,
ADD COLUMN `fecha_hasta` DATE NULL DEFAULT NULL AFTER `fecha_desde`,
ADD COLUMN `visible` TINYINT(1) NULL DEFAULT '0' AFTER `fecha_hasta`, RENAME TO `dependencias` ;

ALTER TABLE `dependencias` 
CHANGE COLUMN `codep` `codep` VARCHAR(10) NULL ;

ALTER TABLE `dependencias` 
CHANGE COLUMN `nombre` `nombre` VARCHAR(255) NOT NULL ;


ALTER TABLE empleados ADD cuit decimal(11) NULL;
ALTER TABLE empleados ADD email varchar(60) NOT NULL;
ALTER TABLE empleados DROP COLUMN id_codep;
ALTER TABLE empleados DROP COLUMN `direccion_de_organismo_id`, CHANGE COLUMN `borrado` `borrado` TINYINT(1) NULL DEFAULT '0' AFTER `email`;

CREATE TABLE empleado_dependencia_directa
(
    id int NOT NULL AUTO_INCREMENT,
    id_empleado int NOT NULL,
    id_dependencia_directa int NOT NULL,
	fecha_desde DATE NOT NULL,
	fecha_hasta DATE NULL DEFAULT NULL,
	borrado TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (id)

);

CREATE TABLE empleado_dependencia_principal
(
    id int NOT NULL AUTO_INCREMENT,
    id_empleado int NOT NULL,
    id_dependencia_principal int NOT NULL,        
	fecha_desde DATE NOT NULL,
	fecha_hasta DATE NULL DEFAULT NULL,
	borrado TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
);

CREATE TABLE empleado_contrato
(
    id int NOT NULL AUTO_INCREMENT,
    id_empleado int NOT NULL,
    id_tipo_contrato int NOT NULL,
    cargo VARCHAR(100) NULL DEFAULT NULL,
	fecha_desde DATE NOT NULL,
	fecha_hasta DATE NULL DEFAULT NULL,
	borrado TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
);

INSERT INTO db_version VALUES('2', now());