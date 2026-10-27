SET GLOBAL event_scheduler = ON;

-- 1. Crear los índices compuestos para evitar el filesort en las vistas
CREATE INDEX idx_srv_met_date ON server_metric_values(server_id, metric_id, created_at);
CREATE INDEX idx_srv_date ON server_metric_values(server_id, created_at);

-- 2. Crear el evento de retención (50M max, borra de a 10M)
DELIMITER //

DROP EVENT IF EXISTS prune_server_metrics_history //

CREATE EVENT prune_server_metrics_history
ON SCHEDULE EVERY 1 DAY STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 3 HOUR)
DO
BEGIN
    DECLARE total_rows BIGINT;
    DECLARE max_records BIGINT DEFAULT 50000000;
    DECLARE chunk_size BIGINT DEFAULT 10000000;
    DECLARE deleted BIGINT DEFAULT 0;

    -- Obtener conteo aproximado rápido
    SELECT table_rows INTO total_rows 
    FROM information_schema.tables 
    WHERE table_schema = 'monitoring_system' AND table_name = 'server_metric_values';

    IF total_rows > max_records THEN
        -- Borrar en pequeños lotes de 20.000 para no bloquear la tabla en producción
        WHILE deleted < chunk_size DO
            DELETE FROM server_metric_values ORDER BY created_at ASC LIMIT 20000;
            SET deleted = deleted + 20000;
        END WHILE;
    END IF;
END //

DELIMITER ;
