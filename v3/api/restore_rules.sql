INSERT INTO alert_rules (id, metric_pattern, rule_type, warning_threshold, danger_threshold) VALUES 
(3, 'system.cpu.load', 'threshold', '3', '5'),
(4, 'backup.daily.file', 'freshness', '26h', '30h'),
(5, 'backup.hourly.file', 'freshness', '2h', '4h'),
(6, 'app.connectivity.trucks', 'threshold', NULL, '<= 0'),
(7, 'app.station.ping', 'regex', NULL, 'no response'),
(8, 'system.services.%', 'regex', 'pending', 'stopped|failed|error|offline'),
(9, 'app.jams.restarts', 'threshold', '> 3', '> 5'),
(10, 'db.idle_queries', 'threshold', '>15', '> 30'),
(11, 'app.ntp.service', 'regex', 'inactive|dead|failed', 'inactive|dead|failed|^$'),
(12, 'system.ntp.ping', 'regex', '100% packet loss', '100% packet loss|unreachable|errors'),
(13, 'app.scripts.running', 'regex', '0[1-9]:d{2}|[1-9]d+:d{2}', '([1-9][0-9]*:[0-9]{2}:[0-9]{2})|((0[1-9]|[1-9][0-9]+):[0-9]{2})'),
(14, 'app.jams.service', 'regex', NULL, '(?si)^(?=.{15,})(?!.*(JAMSRouter|JAMSCluster))'),
(15, 'db.schema.date', 'freshness', '4h', '6h'),
(16, 'db.integrity.diff', 'threshold', '> 15', '> 100'),
(17, '%information_Schema%', 'freshness', '4h', '6h'),
(18, '%information_schema%', 'freshness', '4h', '6h'),
(19, '%schemaInfo%', 'freshness', '4h', '6h'),
-- NEW: Shift Tables Max Diff (Virtual Metric computed in metrics.php Virtual Metric #9)
(81, 'db.shifts.max_diff', 'threshold', '> 100', '> 1000'),
-- NEW: Cluster inactivo/no detectado
(80, 'app.fms.cluster', 'threshold', NULL, '= None');
