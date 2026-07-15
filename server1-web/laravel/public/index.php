<?php
header('Content-Type: application/json');
echo json_encode([
    "status" => "success",
    "message" => "Welcome to the High-Availability Platform",
    "layer" => "Server 1 (Application Layer)",
    "php_version" => PHP_VERSION,
    "interface" => php_sapi_name()
], JSON_PRETTY_PRINT);
