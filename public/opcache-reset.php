<?php
// Security: Only allow opcache reset from localhost/internal IPs
$allowedIps = ['127.0.0.1', '::1'];

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
    http_response_code(403);
    exit('Forbidden');
}

if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "OK";
} else {
    echo "NO_OPCACHE";
}
