<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$filename = (isset($argv[1])) ? $argv[1] : ".env";
$dotenv->load($filename);

echo $_ENV['DB_USERNAME'];
//$dotenv->load(__DIR__.'/.env');

// You can also load several files
// read .env file
// $filename = (isset($argv[1])) ? $argv[1] : ".env";
// $fp = fopen($filename, "r");
// if ($fp) {
//     while (!feof($fp)) {
//         $line = fgets($fp);
//         eval
//         echo $line;
//     }
//     fclose($fp);
// }
