<?php
$_GET['site_id'] = 19; // capcnn
$_SERVER['REQUEST_METHOD'] = 'GET';
// We need to bypass auth. Let's look at api/metrics.php top lines.
$content = file_get_contents('metrics.php');
// comment out require_once 'auth_helper.php' and requireAuth()
$content = str_replace("require_once 'auth_helper.php';", "", $content);
$content = str_replace("\$user = requireAuth();", "\$user = ['id' => 1];", $content);
file_put_contents('metrics_test.php', $content);
require_once 'metrics_test.php';
