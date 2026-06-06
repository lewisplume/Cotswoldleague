<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
header('Location: teamportal.php', true, 302);
exit;
