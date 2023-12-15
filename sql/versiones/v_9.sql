CREATE TABLE `solicitud_comentario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `empleado_logueado_id` int(11) NOT NULL COMMENT 'Id del usuario logueado creador del comentario',
  `texto` text COLLATE utf8_unicode_ci NOT NULL,
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `comentario_FI_1` (`solicitud_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `solicitud_notificacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `empleado_logueado_id` int(11) DEFAULT NULL COMMENT 'Id del usuario logueado creador de la notificacion.',
  `asunto` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `texto` text COLLATE utf8_unicode_ci NOT NULL,
  `tipo` int(11) NOT NULL COMMENT 'Tipo de la notificacion, 1 = Sistema y 2 = Manual',
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `notificacion_FI_1` (`solicitud_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `solicitud_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `empleado_logueado_id` int(11) NOT NULL COMMENT 'Id del usuario logueado creador del comentario',
  `estado_anterior` int(11) NOT NULL COMMENT 'Estado que es modificado.',
  `estado_nuevo` int(11) NOT NULL COMMENT 'Estado que pasa a tener la solicitud.',
  PRIMARY KEY (`id`),
  KEY `solicitud_log_FI_1` (`solicitud_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `tipo_solicitud_id` int(11) NOT NULL,
  `requiere_firma_certificada` tinyint(1) DEFAULT '0',
  `entidad_destino` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `observacion` text COLLATE utf8_unicode_ci,
  `estado` int(11) NOT NULL DEFAULT '1' COMMENT 'Estado de la solicitud: 1 = Nueva, 2 = En curso y 3 = Resuelta',
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `solicitud_FI_1` (`tipo_solicitud_id`),
  KEY `solicitud_FI_2` (`empleado_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO db_version VALUES('9', now());