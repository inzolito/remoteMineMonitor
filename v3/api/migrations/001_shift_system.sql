-- ============================================================
-- Migration 001: Shift Management System
-- Formalización del sistema de turnos 7x7
-- ============================================================

SET NAMES utf8mb4;

-- 1. Grupos de turno (Turno A, Turno B) — entidades permanentes
CREATE TABLE IF NOT EXISTS shift_groups (
    id          TINYINT     NOT NULL,
    alias       VARCHAR(50) NOT NULL,
    color_hex   VARCHAR(7)  NOT NULL DEFAULT '#3b82f6',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed groups from existing shift_config aliases
INSERT IGNORE INTO shift_groups (id, alias, color_hex)
SELECT 1, shift1_alias, '#3b82f6' FROM shift_config LIMIT 1;

INSERT IGNORE INTO shift_groups (id, alias, color_hex)
SELECT 2, shift2_alias, '#8b5cf6' FROM shift_config LIMIT 1;

-- 2. Ciclos de turno (cada período activo de 7 días)
CREATE TABLE IF NOT EXISTS shift_cycles (
    id              INT         NOT NULL AUTO_INCREMENT,
    active_group_id TINYINT     NOT NULL,
    start_datetime  DATETIME    NOT NULL,
    end_datetime    DATETIME    NULL DEFAULT NULL,   -- NULL = ciclo actual abierto
    created_by      INT         NULL DEFAULT NULL,
    created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_active  (active_group_id, end_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed first cycle from existing shift_config
INSERT IGNORE INTO shift_cycles (id, active_group_id, start_datetime, end_datetime)
SELECT 
    1,
    active_shift,
    CONCAT(start_date, ' ', COALESCE(start_hour, '08:00:00')),
    NULL
FROM shift_config
LIMIT 1;

-- 3. Miembros de cada grupo de turno
CREATE TABLE IF NOT EXISTS shift_group_members (
    id          INT         NOT NULL AUTO_INCREMENT,
    user_id     INT         NOT NULL,
    group_id    TINYINT     NOT NULL,
    sub_shift   ENUM('Día','Noche') NOT NULL DEFAULT 'Día',
    assigned_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user (user_id),
    KEY idx_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing turno_7x7 assignments from users table
INSERT IGNORE INTO shift_group_members (user_id, group_id, sub_shift)
SELECT id, turno_7x7, COALESCE(turno_tipo, 'Día')
FROM users
WHERE turno_7x7 IS NOT NULL;

-- 4. Asignación de tickets a ciclos de turno
CREATE TABLE IF NOT EXISTS shift_ticket_assignments (
    id              INT         NOT NULL AUTO_INCREMENT,
    case_id         VARCHAR(18) NOT NULL,
    case_number     VARCHAR(20) NOT NULL,
    cycle_id        INT         NOT NULL,
    group_id        TINYINT     NOT NULL,
    assigned_user_id INT        NULL DEFAULT NULL,  -- ingeniero asignado al momento
    sub_shift       ENUM('Día','Noche') NULL DEFAULT NULL,
    created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_case (case_id),               -- un ticket solo entra una vez
    KEY idx_cycle (cycle_id),
    KEY idx_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Historial de traspasos de tickets
CREATE TABLE IF NOT EXISTS shift_ticket_handoffs (
    id              INT         NOT NULL AUTO_INCREMENT,
    case_id         VARCHAR(18) NOT NULL,
    case_number     VARCHAR(20) NOT NULL,
    from_cycle_id   INT         NOT NULL,
    to_cycle_id     INT         NOT NULL,
    from_group_id   TINYINT     NOT NULL,
    to_group_id     TINYINT     NOT NULL,
    from_sub_shift  ENUM('Día','Noche') NOT NULL,
    to_sub_shift    ENUM('Día','Noche') NOT NULL,
    handoff_type    ENUM('A_to_B','B_to_A','Dia_to_Noche','Noche_to_Dia') NOT NULL,
    handoff_at      DATETIME    NOT NULL,
    notes           TEXT        NULL DEFAULT NULL,
    created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_case    (case_id),
    KEY idx_cycles  (from_cycle_id, to_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
