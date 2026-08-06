<?php
$_GET['site_id'] = 19;
$_SERVER['REQUEST_METHOD'] = 'GET';
// bypass auth check by defining a constant or just modifying metrics.php temporarily.
// Actually, let's include metrics.php after setting a dummy token or session if it uses session.
