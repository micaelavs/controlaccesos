CREATE TABLE novedades
(
    id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    id_empleado int NOT NULL,
    id_tipo_novedad int NOT NULL,
    fecha date NOT NULL,
    id_usuario_carga int NOT NULL,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

ALTER TABLE novedades ADD borrado tinyint(1) NOT NULL DEFAULT 0;

CREATE TABLE tipo_novedades
(
	id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
	nombre varchar(60) NOT NULL
);

ALTER TABLE  `personas` ADD  `genero` TINYINT(1) NULL DEFAULT NULL ;



CREATE TABLE IF NOT EXISTS lectores_reloj (
	id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	descripcion VARCHAR(64) NOT NULL,
	borrado     TINYINT(1)   DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tipos_reloj (
	id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	descripcion VARCHAR(64) NOT NULL,
	borrado     TINYINT(1)   DEFAULT 0
);

INSERT INTO tipos_reloj (descripcion)
VALUES ('Reloj'), ('Comedor'), ('Proximidad'), ('Lite'), ('Huella'), ('Estándar'), ('Otro');

CREATE TABLE IF NOT EXISTS lectore_x_tipo_reloj (
	tipo_reloj_id   INT UNSIGNED NULL,
	lector_reloj_id INT UNSIGNED NOT NULL,
	FOREIGN KEY (tipo_reloj_id) REFERENCES tipos_reloj (id),
	FOREIGN KEY (lector_reloj_id) REFERENCES lectores_reloj (id)
);

CREATE TABLE IF NOT EXISTS relojes (
	id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	ip           VARCHAR(15)       NOT NULL,
	puerto       SMALLINT UNSIGNED NOT NULL,
	numero_serie VARCHAR(64)       NOT NULL,
	marca        VARCHAR(64)       NULL,
	modelo       VARCHAR(64)       NULL,
	tipo_id      INT UNSIGNED      NOT NULL,
	nodo         INT UNSIGNED      NOT NULL,
	ubicacion_id INT UNSIGNED      NOT NULL,
	notas        TEXT              NULL,
	enrolador    TINYINT(1)   DEFAULT 0,
	borrado      TINYINT(1)   DEFAULT 0,
	FOREIGN KEY (tipo_id) REFERENCES tipos_reloj (id),
	FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones (id),
	INDEX (numero_serie, nodo)
);

CREATE TABLE templates
(
	persona_id INT UNSIGNED     NOT NULL,
	indice     TINYINT UNSIGNED NOT NULL,
	data       TEXT             NOT NULL,
	PRIMARY KEY (persona_id, indice)
)ENGINE = InnoDB;




INSERT INTO db_version VALUES('3', now());