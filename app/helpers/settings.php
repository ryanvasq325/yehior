<?php

declare(strict_types=1);

session_start();

define('HOST', $_SERVER['HTTP_HOST']);

define('ROOT', dirname(__FILE__, 3));
#DIRETÓRIO DAS VIEWS
define('DIR_VIEWS', ROOT . '/App/views');
#EXTENSÃO PADRÃO DAS VIEWS
define('EXT_VIEWS', '.html');
#Chave secreta para geração de tokens
define('SECRET_KEY', '58ae142d-afae-4443-994a-43f2bef0e366');