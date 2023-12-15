-- MySQL dump 10.13  Distrib 5.7.17, for Win64 (x86_64)
--
-- Host: localhost    Database: control_accesos
-- ------------------------------------------------------
-- Server version	5.5.59-0ubuntu0.14.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accesos`
--

DROP TABLE IF EXISTS `accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ubicacion_id` int(10) unsigned NOT NULL,
  `tipo_id` int(10) unsigned NOT NULL,
  `tipo_modelo` tinyint(1) DEFAULT NULL,
  `hora_ingreso` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `persona_id_ingreso` int(10) unsigned NOT NULL,
  `tipo_ingreso` int(10) unsigned NOT NULL,
  `hora_egreso` timestamp NULL DEFAULT NULL,
  `persona_id_egreso` int(10) unsigned DEFAULT NULL,
  `tipo_egreso` int(10) unsigned DEFAULT NULL,
  `observaciones` text,
  PRIMARY KEY (`id`),
  KEY `accesos_tipo_id_index` (`tipo_id`),
  KEY `accesos_tipo_modelo_index` (`tipo_modelo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accesos_contratistas`
--

DROP TABLE IF EXISTS `accesos_contratistas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos_contratistas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` int(10) unsigned NOT NULL,
  `credencial_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accesos_empleados`
--

DROP TABLE IF EXISTS `accesos_empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos_empleados` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accesos_visitas`
--

DROP TABLE IF EXISTS `accesos_visitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos_visitas` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(8) unsigned NOT NULL,
  `autorizante_id` int(8) unsigned DEFAULT NULL,
  `credencial_id` int(8) unsigned DEFAULT NULL,
  `origen` varchar(64) NOT NULL,
  `destino` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `advertencias`
--

DROP TABLE IF EXISTS `advertencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `advertencias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(10) unsigned NOT NULL,
  `ubicacion_id` int(10) unsigned DEFAULT NULL,
  `solicitante_id` int(10) unsigned DEFAULT NULL,
  `texto` text NOT NULL,
  `borrado` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `advertencias_genericas`
--

DROP TABLE IF EXISTS `advertencias_genericas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `advertencias_genericas` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `texto` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contratista_personal`
--

DROP TABLE IF EXISTS `contratista_personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratista_personal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contratista_id` int(10) unsigned NOT NULL,
  `autorizante_id` int(10) unsigned DEFAULT NULL,
  `persona_id` int(10) unsigned NOT NULL,
  `art_inicio` datetime DEFAULT NULL,
  `art_fin` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contratista_x_ubicacion`
--

DROP TABLE IF EXISTS `contratista_x_ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratista_x_ubicacion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `personal_id` int(10) unsigned NOT NULL,
  `ubicacion_id` int(10) unsigned NOT NULL,
  `acceso_inicio` timestamp NULL DEFAULT NULL,
  `acceso_fin` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contratistas`
--

DROP TABLE IF EXISTS `contratistas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratistas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(64) NOT NULL,
  `cuit` varchar(64) NOT NULL,
  `direccion` varchar(64) NOT NULL,
  `provincia_id` int(10) unsigned DEFAULT NULL,
  `localidad_id` int(10) unsigned DEFAULT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cuit` (`cuit`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credenciales`
--

DROP TABLE IF EXISTS `credenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credenciales` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(6) NOT NULL,
  `estatus` tinyint(1) DEFAULT '0',
  `ubicacion_id` int(8) unsigned NOT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `codigo` (`codigo`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `direcciones_organismo`
--

DROP TABLE IF EXISTS `direcciones_organismo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direcciones_organismo` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `codep` varchar(10) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direcciones_organismo`
--

LOCK TABLES `direcciones_organismo` WRITE;
/*!40000 ALTER TABLE `direcciones_organismo` DISABLE KEYS */;
INSERT INTO `direcciones_organismo` VALUES (1,'DIS','Dirección de Integración de Sistemas',0),(2,'CIET','Control de Ingresos y egresos',0);
/*!40000 ALTER TABLE `direcciones_organismo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleado_x_usuario`
--

DROP TABLE IF EXISTS `empleado_x_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleado_x_usuario` (
  `usuario_id` int(8) unsigned NOT NULL,
  `empleado_id` int(8) unsigned NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  KEY `empleado_x_usuario_usuario_id_index` (`usuario_id`),
  KEY `empleado_x_usuario_empleado_id_index` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(8) unsigned NOT NULL,
  `id_codep` varchar(10) DEFAULT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empleados_x_ubicacion`
--

DROP TABLE IF EXISTS `empleados_x_ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados_x_ubicacion` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` int(8) unsigned NOT NULL,
  `ubicacion_id` int(8) unsigned NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `localidades`
--

DROP TABLE IF EXISTS `localidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localidades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provincia_id` int(10) unsigned NOT NULL,
  `nombre` varchar(64) NOT NULL,
  `informacion` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localidades`
--

LOCK TABLES `localidades` WRITE;
/*!40000 ALTER TABLE `localidades` DISABLE KEYS */;
INSERT INTO `localidades` VALUES (1,1,'Trenque Lauquen',NULL),(2,1,'Junín',NULL),(3,1,'La Matanza',NULL),(4,1,'Mercedes',NULL),(5,1,'Bahía Blanca',NULL),(6,1,'Mar del Plata',NULL),(7,1,'Quilmes',NULL),(8,1,'Azul',NULL),(9,1,'Zárate - Campana',NULL),(10,1,'Lomas de Zamora',NULL),(11,1,'Morón',NULL),(12,1,'La Plata',NULL),(13,1,'San Martín',NULL),(14,1,'San Nicolás',NULL),(15,1,'San Isidro',NULL),(16,1,'Necochea',NULL),(17,1,'Pergamino',NULL),(18,1,'Dolores',NULL);
/*!40000 ALTER TABLE `localidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organismos`
--

DROP TABLE IF EXISTS `organismos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organismos` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(64) NOT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organismos`
--

LOCK TABLES `organismos` WRITE;
/*!40000 ALTER TABLE `organismos` DISABLE KEYS */;
INSERT INTO `organismos` VALUES (1,'Ministerio de Transporte',0);
/*!40000 ALTER TABLE `organismos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personas` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `documento` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(64) DEFAULT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `documento` (`documento`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provincias`
--

DROP TABLE IF EXISTS `provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provincias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(64) NOT NULL,
  `abreviatura` varchar(4) DEFAULT NULL,
  `informacion` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provincias`
--

LOCK TABLES `provincias` WRITE;
/*!40000 ALTER TABLE `provincias` DISABLE KEYS */;
INSERT INTO `provincias` VALUES (1,'Buenos Aires','PBA',NULL),(2,'Catamarca','CT',NULL),(3,'Chaco','CHC',NULL),(4,'Chubut','CHB',NULL),(5,'Ciudad de Buenos Aires','CABA',NULL),(6,'Córdoba','CD',NULL),(7,'Corrientes','CR',NULL),(8,'Entre Ríos','ER',NULL),(9,'Formosa','FM',NULL),(10,'Jujuy','JJ',NULL),(11,'La Pampa','LP',NULL),(12,'La Rioja','LR',NULL),(13,'Mendoza','MZ',NULL),(14,'Misiones','MS',NULL),(15,'Neuquén','NQ',NULL),(16,'Río Negro','RN',NULL),(17,'Salta','ST',NULL),(18,'San Juan','SJ',NULL),(19,'San Luis','SL',NULL),(20,'Santa Cruz','SC',NULL),(21,'Santa Fe','SF',NULL),(22,'Santiago del Estero','SE',NULL),(23,'Tierra del Fuego, Antártida e Islas del Atlántico Sur','TF',NULL),(24,'Tucumán','TC',NULL);
/*!40000 ALTER TABLE `provincias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicaciones` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `organismo_id` int(8) DEFAULT '1',
  `calle` varchar(64) NOT NULL,
  `numero` int(11) NOT NULL,
  `borrado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (1,'PC 315 Piso 2',1,'Paseo Colón',315,0),(2,'PC 315 Piso 3',1,'Paseo Colón',315,0),(3,'HACIENDA',1,'Hipólito Yrigoyen',250,0);
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'control_accesos'
--

--
-- Dumping routines for database 'control_accesos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-04-16 11:31:33

CREATE TABLE `db_version` (
  `version` MEDIUMINT(5) UNSIGNED NOT NULL,
  `fecha` DATETIME NOT NULL,
  PRIMARY KEY (`version`));

INSERT INTO db_version VALUES('1.0', now());