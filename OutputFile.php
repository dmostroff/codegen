<?php

namespace GenerateEntity;

class OutputFile
{
    public static function writeEntity( string $parentName, string $className, string $outString)
    {
        self::writeClassFile( $parentName, "Entites", $className, "", $outString);
    }
    public static function writeEntityDTO( string $parentName, string $className, string $outString)
    {
        self::writeClassFile( $parentName, "DTO", $className, "", $outString);
    }
    public static function writeTransformer( string $parentName, string $className, string $outString)
    {
        self::writeClassFile( $parentName, "Transformers", $className, "Tansformer", $outString);
    }
    public static function writeMapping( string $parentName, string $className, string $outString)
    {
        self::writeClassFile( $parentName, "Mappings", $className, "Mapping", $outString);
    }
    private static function writeClassFile( string $parentName, $directory, string $className, string $entityRole, string $outString)
    {
        $fullpath = implode( DIRECTORY_SEPARATOR, ['Domain', $parentName, $directory]);
        if (!file_exists($fullpath)) { 
            mkdir($fullpath, 0777, true); 
        }
        $fileOut = implode( DIRECTORY_SEPARATOR, [$fullpath, $className . $entityRole . ".php"]);
        file_put_contents($fileOut, $outString);
    }
}