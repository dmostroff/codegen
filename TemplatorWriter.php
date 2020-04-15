<?php

namespace GenerateEntity;

class TemplatorWriter
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
    public static function writeClassFile( string $parentName, $directory, string $className, string $entityRole, string $outString)
    {
        $fullpath = implode( DIRECTORY_SEPARATOR, [self::$projectRoot, self::$appRoot, 'Domain', $parentName, $directory]);
        if (!file_exists($fullpath)) { 
            mkdir($fullpath, 0777, true); 
        }
        $fileOut = implode( DIRECTORY_SEPARATOR, [$fullpath, $className . $entityRole . ".php"]);
        file_put_contents($fileOut, $outString);
    }

    public static function writeResourceFile( $directory, string $className, $viewName, string $outString)
    {
        $fullpath = implode( DIRECTORY_SEPARATOR, [self::$projectRoot, self::$resourceRoot, $directory, $className]);
        if (!file_exists($fullpath)) { 
            mkdir($fullpath, 0777, true); 
        }
        $fileOut = implode( DIRECTORY_SEPARATOR, [$fullpath, $viewName . ".vue"]);
        file_put_contents($fileOut, $outString);
    }
}