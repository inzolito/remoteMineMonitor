--- TABLE: alerta_solucion ---
CREATE TABLE `alerta_solucion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_alerta` int NOT NULL,
  `id_usuario` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `alerta_solucion` varchar(500) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_alertas_faenas_solucion_id_alerta_faena` (`id_alerta`),
  CONSTRAINT `fk_alertas_faenas_solucion_id_alerta_faena` FOREIGN KEY (`id_alerta`) REFERENCES `alertas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: alertas ---
CREATE TABLE `alertas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_alerta_sistema` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `id_faena` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `alerta` varchar(1000) COLLATE utf8mb3_bin NOT NULL,
  `vista` int NOT NULL,
  `estado` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_alertas_faenas_id_alerta_sistema` (`id_alerta_sistema`),
  KEY `fk_alertas_faenas_id_faena` (`id_faena`),
  KEY `fk_alertas_faenas_id_usuario` (`id_usuario`),
  CONSTRAINT `fk_alertas_faenas_id_alerta_sistema` FOREIGN KEY (`id_alerta_sistema`) REFERENCES `alertas_sistema` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_alertas_faenas_id_faena` FOREIGN KEY (`id_faena`) REFERENCES `faenas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_alertas_faenas_id_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=167571 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: alertas_sistema ---
CREATE TABLE `alertas_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_alerta` char(9) COLLATE utf8mb3_bin NOT NULL,
  `alerta_sistema` varchar(250) COLLATE utf8mb3_bin NOT NULL,
  `gravedad` varchar(25) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: areas ---
CREATE TABLE `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `area` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: caminos_conexiones ---
CREATE TABLE `caminos_conexiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servidor_salto` int NOT NULL,
  `id_conexion` int NOT NULL,
  `salto` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_caminos_conexiones_id_servidor` (`id_servidor_salto`) USING BTREE,
  KEY `fk_caminos_conexiones_id_conexion` (`id_conexion`),
  CONSTRAINT `fk_caminos_conexiones_id_conexion` FOREIGN KEY (`id_conexion`) REFERENCES `conexiones` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_caminos_conexiones_id_servidor` FOREIGN KEY (`id_servidor_salto`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: check_faena ---
CREATE TABLE `check_faena` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_faena` int NOT NULL,
  `id_usuario` int NOT NULL,
  `estado` varchar(15) COLLATE utf8mb3_bin NOT NULL,
  `fecha` datetime NOT NULL,
  `descripcion` varchar(150) COLLATE utf8mb3_bin DEFAULT NULL,
  `aprobado` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: comentarios_check_faena ---
CREATE TABLE `comentarios_check_faena` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_check_faena` int NOT NULL,
  `comentario` varchar(400) COLLATE utf8mb3_bin NOT NULL,
  `estado` int NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: conexiones ---
CREATE TABLE `conexiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servidor` int NOT NULL,
  `nombre_conexion` varchar(150) COLLATE utf8mb3_bin NOT NULL,
  `estado_conexion` int DEFAULT NULL,
  `estado` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_conexiones_id_servidor` (`id_servidor`),
  CONSTRAINT `fk_conexiones_id_servidor` FOREIGN KEY (`id_servidor`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: faenas ---
CREATE TABLE `faenas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faena` varchar(60) COLLATE utf8mb3_bin NOT NULL,
  `alias` varchar(12) COLLATE utf8mb3_bin NOT NULL,
  `estado` int NOT NULL,
  `conglomerado` varchar(250) COLLATE utf8mb3_bin DEFAULT NULL,
  `logo` varchar(250) COLLATE utf8mb3_bin DEFAULT NULL,
  `administrador_contrato` varchar(500) COLLATE utf8mb3_bin DEFAULT NULL,
  `despacho` varchar(500) COLLATE utf8mb3_bin DEFAULT NULL,
  `telefono_despacho` varchar(250) COLLATE utf8mb3_bin DEFAULT NULL,
  `ingenieros_onsite` varchar(500) COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: faenas_servidores ---
CREATE TABLE `faenas_servidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servidor` int NOT NULL,
  `id_faena` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_faenas_servidores_id_faena` (`id_faena`),
  KEY `fk_faenas_servidores_id_servidor` (`id_servidor`),
  CONSTRAINT `fk_faenas_servidores_id_faena` FOREIGN KEY (`id_faena`) REFERENCES `faenas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_faenas_servidores_id_servidor` FOREIGN KEY (`id_servidor`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: fotos_subprocesos_check_faena ---
CREATE TABLE `fotos_subprocesos_check_faena` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_subproceso_check_faena` int NOT NULL,
  `foto` varchar(60) COLLATE utf8mb3_bin NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: logs_servidores ---
CREATE TABLE `logs_servidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servidor` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre_log` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `log` text COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_servidores_id_servidor` (`id_servidor`),
  CONSTRAINT `fk_log_servidores_id_servidor` FOREIGN KEY (`id_servidor`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4057666 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: metricas ---
CREATE TABLE `metricas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `metrica` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  `simbolo` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: metricas_servidores ---
CREATE TABLE `metricas_servidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_faena` int NOT NULL,
  `id_servidor` int NOT NULL,
  `id_metrica` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_faena_metrica_servidor` (`id_faena`),
  KEY `id_servidor_metrica_servidor` (`id_servidor`),
  KEY `id_metricas_metrica_servidor` (`id_metrica`),
  CONSTRAINT `id_faena_metrica_servidor` FOREIGN KEY (`id_faena`) REFERENCES `faenas` (`id`),
  CONSTRAINT `id_metricas_metrica_servidor` FOREIGN KEY (`id_metrica`) REFERENCES `metricas` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `id_servidor_metrica_servidor` FOREIGN KEY (`id_servidor`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: metricas_servidores_valor ---
CREATE TABLE `metricas_servidores_valor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_metrica_servidor` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `valor` varchar(8000) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_metrica_fecha` (`id_metrica_servidor`,`updated_at` DESC),
  CONSTRAINT `id_metrica_servidor_metricas_servidores_valores` FOREIGN KEY (`id_metrica_servidor`) REFERENCES `metricas_servidores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3859217 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: permisos ---
CREATE TABLE `permisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permiso` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: permisos_faenas ---
CREATE TABLE `permisos_faenas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_permiso` int NOT NULL,
  `id_faena` int NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_permisos_faenas_id_faena` (`id_faena`),
  KEY `fk_permisos_faenas_id_permiso` (`id_permiso`),
  CONSTRAINT `fk_permisos_faenas_id_faena` FOREIGN KEY (`id_faena`) REFERENCES `faenas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_permisos_faenas_id_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: problemas ---
CREATE TABLE `problemas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_area` int NOT NULL,
  `id_usuario` int NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb3_bin NOT NULL,
  `descripcion` longtext COLLATE utf8mb3_bin NOT NULL,
  `fecha` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: procesos ---
CREATE TABLE `procesos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proceso` varchar(30) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: producto_servidores ---
CREATE TABLE `producto_servidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servidor` int NOT NULL,
  `id_producto` int NOT NULL,
  `version` varchar(10) COLLATE utf8mb3_bin NOT NULL,
  `parche` varchar(10) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_producto_servidores_id_producto` (`id_producto`),
  KEY `fk_producto_servidores_id_servidor` (`id_servidor`),
  CONSTRAINT `fk_producto_servidores_id_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_producto_servidores_id_servidor` FOREIGN KEY (`id_servidor`) REFERENCES `servidores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: productos ---
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb3_bin DEFAULT NULL,
  `alias` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `puerto` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: servidores ---
CREATE TABLE `servidores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `ip` varchar(20) COLLATE utf8mb3_bin NOT NULL,
  `sistema_operativo` varchar(200) COLLATE utf8mb3_bin NOT NULL,
  `usuario` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `password` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `protocolo` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `puerto` int NOT NULL,
  `tipo_servidor` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `database` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `motor_db` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `usuario_db` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `password_db` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `estado` int NOT NULL,
  `descripcion` varchar(1000) COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: soluciones ---
CREATE TABLE `soluciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_problema` int NOT NULL,
  `solucion` longtext COLLATE utf8mb3_bin NOT NULL,
  `fecha` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: subproceso_check_faena ---
CREATE TABLE `subproceso_check_faena` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_check_faena` int NOT NULL,
  `id_subproceso` int NOT NULL,
  `id_usuario` int NOT NULL,
  `estado` int DEFAULT NULL,
  `comentario` varchar(200) COLLATE utf8mb3_bin DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=361 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: subprocesos ---
CREATE TABLE `subprocesos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_proceso` int NOT NULL,
  `subproceso` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `comentario` varchar(300) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_proceso` (`id_proceso`),
  CONSTRAINT `subprocesos_ibfk_1` FOREIGN KEY (`id_proceso`) REFERENCES `procesos` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--- TABLE: usuarios ---
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_permiso` int NOT NULL,
  `nombre` varchar(30) COLLATE utf8mb3_bin NOT NULL,
  `apellido` varchar(30) COLLATE utf8mb3_bin NOT NULL,
  `usuario` varchar(30) COLLATE utf8mb3_bin NOT NULL,
  `password` varchar(20) COLLATE utf8mb3_bin NOT NULL,
  `mail` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_permiso` (`id_permiso`),
  CONSTRAINT `id_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

