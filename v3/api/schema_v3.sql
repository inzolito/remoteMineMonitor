-- Database: monitoring_system

CREATE DATABASE IF NOT EXISTS monitoring_system;
USE monitoring_system;

-- Table: permissions (was permisos)
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(250) NOT NULL
);

-- Table: users (was usuarios)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  permission_id INT NOT NULL,
  first_name VARCHAR(30) NOT NULL,
  last_name VARCHAR(30) NOT NULL,
  username VARCHAR(30) NOT NULL,
  password VARCHAR(255) NOT NULL, -- Increased size for hashing
  email VARCHAR(100) NOT NULL,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cargo VARCHAR(100) DEFAULT NULL,
  salesforce_user_id VARCHAR(18) DEFAULT NULL,
  teams_webhook_url TEXT,
  turno_7x7 TINYINT DEFAULT NULL,
  turno_tipo VARCHAR(10) DEFAULT 'Día',
  FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Table: shift_config
CREATE TABLE IF NOT EXISTS shift_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  active_shift TINYINT NOT NULL DEFAULT 1,
  shift1_alias VARCHAR(100) DEFAULT 'Turno 1',
  shift2_alias VARCHAR(100) DEFAULT 'Turno 2',
  start_date DATE NOT NULL,
  start_hour TIME DEFAULT '08:00:00',
  end_hour TIME DEFAULT '20:00:00'
);


-- Table: sites (was faenas)
CREATE TABLE IF NOT EXISTS sites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  alias VARCHAR(12) NOT NULL,
  status INT NOT NULL,
  conglomerate VARCHAR(250),
  logo_url VARCHAR(250),
  contract_manager VARCHAR(500),
  dispatch_contact VARCHAR(500),
  dispatch_phone VARCHAR(250),
  onsite_engineers VARCHAR(500)
);

-- Table: servers (was servidores)
CREATE TABLE IF NOT EXISTS servers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  ip_address VARCHAR(20) NOT NULL,
  os VARCHAR(200) NOT NULL,
  ssh_user VARCHAR(100) NOT NULL,
  ssh_password VARCHAR(100) NOT NULL,
  protocol VARCHAR(100) NOT NULL,
  port INT NOT NULL,
  server_type VARCHAR(100) NOT NULL,
  db_name VARCHAR(50) NOT NULL,
  db_engine VARCHAR(50) NOT NULL,
  db_user VARCHAR(50) NOT NULL,
  db_password VARCHAR(50),
  status INT NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table: site_servers (was faenas_servidores)
CREATE TABLE IF NOT EXISTS site_servers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  server_id INT NOT NULL,
  site_id INT NOT NULL,
  FOREIGN KEY (server_id) REFERENCES servers(id),
  FOREIGN KEY (site_id) REFERENCES sites(id)
);

-- Table: metrics (was metricas)
CREATE TABLE IF NOT EXISTS metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  symbol VARCHAR(255) NOT NULL
);

-- Table: server_metrics (was metricas_servidores)
CREATE TABLE IF NOT EXISTS server_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_id INT NOT NULL,
  server_id INT NOT NULL,
  metric_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (site_id) REFERENCES sites(id),
  FOREIGN KEY (server_id) REFERENCES servers(id),
  FOREIGN KEY (metric_id) REFERENCES metrics(id)
);

-- Table: server_metric_values (was metricas_servidores_valor)
CREATE TABLE IF NOT EXISTS server_metric_values (
  id INT AUTO_INCREMENT PRIMARY KEY,
  server_metric_id INT NOT NULL,
  value TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (server_metric_id) REFERENCES server_metrics(id)
);
