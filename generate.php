<?php
use GenerateEntity\DbConn;
use GenerateEntity\DoctrineTemplate;
require_once __DIR__.'/vendor/autoload.php';


require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'dbConn.php');
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'DoctrineTemplate.php');

$dbConn = new DbConn();
 $dt = new DoctrineTemplate('fist');
 // $a = $dbConn->getTables( );
 $colData = $dbConn->getColumns('donors');
 $dt->setColData($colData);
 // $props = $dt->genProperties();
 $e = $dt->genEntity('donors');
    $t = $dt->genTransformer( "Donor", "donors");

 echo var_export( $t, true);