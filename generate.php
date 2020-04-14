<?php

use GenerateEntity\ConvertJson;
use GenerateEntity\DbConn;
use GenerateEntity\DoctrineTemplate;
use GenerateEntity\GeneratorService;
use GenerateEntity\OutputFile;

require_once __DIR__ . '/vendor/autoload.php';

$required = [
   'dbConn',
   'MetaDataReader',
   'GeneratorService',
   'CodeTemplatorInterface',
   'CodeTemplator',
   'TemplatorWriter'

];

foreach( $required as $filebase)
{
   require_once(__DIR__ . DIRECTORY_SEPARATOR . $filebase . '.php'   );
}

const FILENAME = 'chesedcare_data.json';
const TARGETFILENAME = 'target.json';

$cmd = ($argv[1]) ? $argv[1] : '';
$jsonFilename = ($argc > 2) ? $argv[2] : FILENAME;

switch ($cmd) {
   case 'readDatabase':
      $json  = readDatabase($jsonFilename);
      file_put_contents(TARGETFILENAME, $json);
      break;
   case 'backend':
      $service = (new GeneratorService())
         ->init($jsonFilename)
         ->runBackend();
      break;
   case 'vue':
      $service = (new GeneratorService())
         ->init($jsonFilename)
         ->runVue();
      break;
   default:
      break;
}
return;

function readDatabase($jsonFilename)
{
   $dbConn = new DbConn();
   $dt = new DoctrineTemplate('chesed');
   $tables = $dbConn->getTables();
   $tableColData = array_map(function ($table) use ($dbConn, $dt) {
      $colData = $dbConn->getColumns($table);
      return [$table => $dt->filterColumns($colData)];
   }, $tables);
   return json_encode($tableColData, JSON_PRETTY_PRINT);
}



// $colData = $dbConn->getColumns('donors');
// $dt->setColData($colData);
// $dt->setParentName('Donor');
// // $props = $dt->genProperties();
// $e = $dt->genEntity('donors');
// //  $t = $dt->genTransformer( );
// //  $t = $dt->genMappings( );
// $t = $dt->setToJson();

// echo var_export($t, true);
