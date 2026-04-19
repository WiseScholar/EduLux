<?php
// student/actions/heartbeat.php
require_once __DIR__ . '/../../../includes/config.php';
// Just by including config (which starts the session), the "Last Activity" timestamp is updated.
echo json_encode(['status' => 'alive', 'time' => date('H:i:s')]);