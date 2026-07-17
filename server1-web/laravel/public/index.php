<?php

$page = $_GET['page'] ?? 'home';

$allowedPages = [
    'home',
    'map',
    'harbor',
];

if (!in_array($page, $allowedPages)) {
    $page = '404';
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navigation.php';
require __DIR__ . "/pages/{$page}.php";
require __DIR__ . '/includes/footer.php';
