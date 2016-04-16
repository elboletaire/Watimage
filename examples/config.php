<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('FILES', PATH . 'files' . DIRECTORY_SEPARATOR);
define('OUTPUT', PATH . 'output' . DIRECTORY_SEPARATOR);

require realpath(PATH . '../vendor/autoload.php');
