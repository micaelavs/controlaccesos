iNSERT INTO tipo_novedades (nombre) VALUES ('Comisión horaria');

ALTER TABLE `novedades` 
CHANGE COLUMN `fecha_desde` `fecha_desde` DATETIME NOT NULL ,
CHANGE COLUMN `fecha_hasta` `fecha_hasta` DATETIME NOT NULL ;

CREATE TABLE log_accesos ( id int(10) NOT NULL AUTO_INCREMENT UNIQUE, 
ubicacion_id int(10) NOT NULL UNIQUE, 
tipo_id int(10) NOT NULL UNIQUE, 
tipo_modelo tinyint(1) DEFAULT NULL, 
hora_ingreso TIMESTAMP NULL DEFAULT NULL, 
persona_id_ingreso int(10) NOT NULL UNIQUE, 
tipo_ingreso int(10) NOT NULL UNIQUE, 
hora_egreso TIMESTAMP NULL DEFAULT NULL,
persona_id_egreso int(10) UNIQUE DEFAULT NULL, 
tipo_egreso int(10) UNIQUE DEFAULT NULL, 
observaciones TEXT DEFAULT NULL, 
fecha_actual TIMESTAMP  DEFAULT CURRENT_TIMESTAMP, 
motivo TEXT DEFAULT NULL, 
PRIMARY KEY (id) ) 
ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO db_version VALUES('12', now());