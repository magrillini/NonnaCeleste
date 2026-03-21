<?php

declare(strict_types=1);

session_start();

define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/HomeHero.php';

$db = Database::connection();
Database::migrate($db);
Database::seed($db);
