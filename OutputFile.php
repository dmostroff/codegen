<?php

namespace GenerateEntity;

class OutputFile
{
    private static $projectRoot;
    private static $appRoot;
    private static $resourceRoot;

    public static function getProjectRoot( ) {
        return self::$projectRoot;
    }

    public static function setProjectRoot( $projectRoot) {
        self::$projectRoot = $projectRoot;
    }

    public static function getAppRoot( ) {
        return self::$appRoot;
    }

    public static function setAppRoot( $appRoot) {
        self::$appRoot = $appRoot;
    }

    public static function getResourceRoot( ) {
        return self::$resourceRoot;
    }

    public static function setResourceRoot( $resourceRoot) {
        self::$resourceRoot = $resourceRoot;
    }

    /**
     * Backend
     */
    public static function writeEntity( string $parentName, string $className, string $outString)
    {
        self::writeClassFile( $parentName, "Entities", $className, "", $outString);
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
        $fullpath = implode( DIRECTORY_SEPARATOR, [self::$projectRoot, self::$appRoot, 'Domain', $parentName, $directory]);
        if (!file_exists($fullpath)) { 
            mkdir($fullpath, 0777, true); 
        }
        $fileOut = implode( DIRECTORY_SEPARATOR, [$fullpath, $className . $entityRole . ".php"]);
        file_put_contents($fileOut, $outString);
    }

    /**
     * Vue templates
     */
    public static function writeVuePage( $parentName, string $className, string $viewName, string $outString)
    {
        self::writeResourceFile( $parentName, "Page", $className, $viewName, $outString);
    }
    public static function writeVueModel( $parentName, string $className, string $outString)
    {
        self::writeResourceFile( $parentName, "Model", $className, $className, $outString);
    }


    private static function writeResourceFile( string $parentName, $directory, string $className, $viewName, string $outString)
    {
        $fullpath = implode( DIRECTORY_SEPARATOR, [self::$projectRoot, self::$resourceRoot, 'js', $directory, $className]);
        if (!file_exists($fullpath)) { 
            mkdir($fullpath, 0777, true); 
        }
        $fileOut = implode( DIRECTORY_SEPARATOR, [$fullpath, $viewName . ".vue"]);
        file_put_contents($fileOut, $outString);
    }
}