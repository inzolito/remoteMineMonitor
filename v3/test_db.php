<?php
require_once 'api/db.php';
$db = new DB();
$mysqli = $db->getConnection();
require_once 'api/shifts.php';
// wait, shifts.php includes auth_helper and exits.
