<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

defined('HOST')       || define('HOST', $_SERVER['HTTP_HOST'] ?? 'localhost');
defined('ROOT')       || define('ROOT', dirname(__FILE__, 3));
defined('DIR_VIEWS')  || define('DIR_VIEWS', ROOT . '/App/Views');
defined('EXT_VIEWS')  || define('EXT_VIEWS', '.html');
defined('SECRET_KEY') || define('SECRET_KEY', '58ae142d-afae-4443-994a-43f2bef0e366');