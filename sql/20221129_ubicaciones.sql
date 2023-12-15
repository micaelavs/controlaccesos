USE `{{{db_app}}}`; 

ALTER TABLE `ubicaciones` 
ADD COLUMN `id_locacion` INT(8) UNSIGNED NULL DEFAULT NULL AFTER `numero`,
CHANGE COLUMN `id` `id` INT(8) UNSIGNED NOT NULL ;

ALTER TABLE `ubicaciones` 
ADD COLUMN `id_edificio_api` INT(8) NULL DEFAULT NULL AFTER `id_locacion_api`,
ADD COLUMN `id_oficina_api` INT(8) NULL DEFAULT NULL AFTER `id_edificio_api`,
CHANGE COLUMN `id_locacion` `id_locacion_api` INT(8) UNSIGNED NULL DEFAULT NULL ;


/*quitamos la fk de relojes, para poder poner autoincrement al id de la tabla ubicaciones, que lo necesitamos que sea autoincrement*/
ALTER TABLE `relojes` 
DROP FOREIGN KEY `relojes_ibfk_2`;
ALTER TABLE `relojes` 
DROP INDEX `ubicacion_id` ;
;
ALTER TABLE `relojes` 
ADD CONSTRAINT `relojes_ibfk_2`
  FOREIGN KEY ()
  REFERENCES `ubicaciones` ();


ALTER TABLE `ubicaciones` 
CHANGE COLUMN `id` `id` INT(8) UNSIGNED NOT NULL AUTO_INCREMENT ;

/*Para que entre en el campo nombre - calle - numero - piso - oficina*/
ALTER TABLE `ubicaciones` 
CHANGE COLUMN `nombre` `nombre` VARCHAR(250) NOT NULL ;

/*Establecemos relaciones según especificó el usuario*/
UPDATE `ubicaciones` SET `id_locacion_api` = '1', `id_edificio_api` = '1', `id_oficina_api` = '259' WHERE (`id` = '28');
UPDATE `ubicaciones` SET `id_locacion_api` = '1', `id_edificio_api` = '1', `id_oficina_api` = '121' WHERE (`id` = '18');
UPDATE `ubicaciones` SET `id_locacion_api` = '1', `id_edificio_api` = '1', `id_oficina_api` = '353' WHERE (`id` = '19');
UPDATE `ubicaciones` SET `id_locacion_api` = '2', `id_edificio_api` = '2', `id_oficina_api` = '375' WHERE (`id` = '1');
UPDATE `ubicaciones` SET `id_locacion_api` = '3', `id_edificio_api` = '3', `id_oficina_api` = '135' WHERE (`id` = '8');
UPDATE `ubicaciones` SET `id_locacion_api` = '5', `id_edificio_api` = '5', `id_oficina_api` = '381' WHERE (`id` = '30');
UPDATE `ubicaciones` SET `id_locacion_api` = '7', `id_edificio_api` = '6', `id_oficina_api` = '382' WHERE (`id` = '13');
UPDATE `ubicaciones` SET `id_locacion_api` = '10', `id_edificio_api` = '10', `id_oficina_api` = '306' WHERE (`id` = '10');