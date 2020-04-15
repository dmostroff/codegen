<?php

namespace GenerateEntity;

class CodeTemplator implements CodeTemplatorInterface
{
    protected $entityMetaData;
    protected $entitiesData;
    protected $parentName;
    protected $tableName;

    function __construct( $entityMetaData)
    {
        $this->entityMetaData = $entityMetaData;
    }

    public function instantiate()
    {
        foreach ($this->entityMetaData as $parentName => $tables) {
            $this->setParentName($parentName);
            foreach ($tables as $tableName => $entitiesData) {
                $this->setTableName($tableName);
                $this->setEntitiesData($entitiesData);
                $this->instantiateParts( );
            }
        }
    }

    protected function instantiateParts( )
    {
        return;
    }

    protected static function replaceTemplate( $patterns, $replacements, $templateFile)
    {
        $template = file_get_contents($templateFile);
        return preg_replace($patterns, $replacements, $template);
    }

    protected function substituteTemplate($templateFile, $aPatterns = null, $aReplacements = null)
    {
        $className = self::getClassName($this->tableName);
        $title = self::plural($className);
        $entityName = self::getEntityName($this->tableName);
        $entitiesName = self::plural($entityName);

        $basePattern = [
            self::escapePattern('parentName'),
            self::escapePattern('className'),
            self::escapePattern('entitiesName'),
            self::escapePattern('entityName'),
            self::escapePattern('title'),
        ];
        $patterns = array_merge($basePattern, $aPatterns??[]);
        $baseReplacements = [
            $this->parentName,
            $className,
            $entitiesName,
            $entityName,
            $title,
        ];
        $replacements = array_merge($baseReplacements, $aReplacements??[]);
        return self::replaceTemplate( $patterns, $replacements, $templateFile);
    }

    protected function getTemplateFileName($filename) : string
    {
        return '';
    }

    public function setEntitiesData( $entitiesData)
    {
        $this->entitiesData = $entitiesData;
    }

    public static function toCamelCase($word, $lowercasefirst = false)
    {
        $retval = str_replace(' ', '', ucwords(strtr($word, '_-', ' ')));
        if ($lowercasefirst) {
            $retval = lcfirst($retval);
        }
        return $retval;
    }

    protected static function plural($word)
    {
        return (substr($word, -1) == 'y') ? substr($word, 0, -1) . 'ies' : $word . 's';
    }

    protected static function getClassName($tableName)
    {
        return self::toCamelCase($tableName);
    }

    protected static function getClassesName($tableName)
    {
        return self::plural(self::getClassName($tableName));
    }

    protected static function getEntityName($tableName)
    {
        return self::toCamelCase($tableName, true);
    }

    protected static function getEntitiesName($tableName)
    {
        return self::plural(self::getEntityName($tableName));
    }

    protected static function escapePattern($searchString)
    {
        return sprintf('/\{\$%s\}/', $searchString);
    }

    protected function setParentName($parentName)
    {
        $this->parentName = $parentName;
    }
    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }
}