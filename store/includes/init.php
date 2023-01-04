<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$log = new Logger('log');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::INFO));