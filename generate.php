<?php

use GenerateEntity\ConvertJson;
use GenerateEntity\DbConn;
use GenerateEntity\DoctrineTemplate;
use GenerateEntity\OutputFile;

require_once __DIR__ . '/vendor/autoload.php';


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'dbConn.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'DoctrineTemplate.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'ConvertJson.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'OutputFile.php');

const FILENAME = 'chesedcare_data.json';
const TARGETFILENAME = 'target.json';

$cmd = ($argv[1]) ? $argv[1] : '';
$jsonFilename = ($argv[2]) ? $argv[2] : FILENAME;

switch ($cmd) {
   case 'readDatabase':
      $json  = readDatabase($jsonFilename);
      break;
   case 'readMetaDataFile':
      $json = readMetaDataFile($jsonFilename);
      $colData = json_decode($json, true);
      $dt = new DoctrineTemplate('chesed');
      foreach( $colData as $parent)
      {
         foreach( $parent as $parentName => $tableData)
         {
            $dt->setParentName($parentName);
            foreach( $tableData as $tableName => $colData)
            {
               $dt->setTableName( $tableName);
               $dt->setColData($colData);
               $className = $dt->getClassName($tableName);
               $entity = $dt->genEntity($tableName);
               OutputFile::writeEntity($parentName, $className, $entity);
               $entity = $dt->genEntityDTO($tableName);
               OutputFile::writeEntityDTO($parentName, $className, $entity);
               $entity = $dt->genMappings($tableName);
               OutputFile::writeMapping($parentName, $className, $entity);
               $entity = $dt->genTransformer($tableName);
               OutputFile::writeTransformer($parentName, $className, $entity);
            }
         }
      }

      break;
   default:
      break;
}
file_put_contents(TARGETFILENAME, $json);
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

function readMetaDataFile($jsonFilename)
{
   $json = file_get_contents($jsonFilename);
   $convert = new ConvertJson($json);
   $jsonConverted = $convert->run();
   return $jsonConverted;
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
