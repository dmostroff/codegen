<?php

namespace GenerateEntity;

class CodeTemplatorBackend extends CodeTemplator
{
    const BACKEND_TEMPLATE_DIR = '.\Templates\Backend';

    const DATATYPES = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'int',
        'datetime' => 'Carbon',
        'date' => 'Carbon',
        'tinyint' => 'boolean',
        'text' => 'string',
        'longtext' => 'string'
    ];

    const DATATYPE_MAPPING = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'integer',
        'datetime' => 'dateTime',
        'date' => 'date',
        'tinyint' => 'boolean',
        'text' => 'text',
        'longtext' => 'text'
    ];

    public function instantiateParts()
    {
        $className = $this->getClassName($this->tableName);
        $entity = $this->genEntity($this->tableName);
        $this->writeEntity($className, $entity);
        $entity = $this->genEntityDTO($this->tableName);
        $this->writeEntityDTO($className, $entity);
        $entity = $this->genMappings($this->tableName);
        $this->writeMapping($className, $entity);
        $entity = $this->genTransformer($this->tableName);
        $this->writeTransformer($className, $entity);
        $entity = $this->genTransformer($this->tableName);
        $this->writeTransformer( $className, $entity);
        $entity = $this->genTransformer($this->tableName);
        $this->writeTransformer($className, $entity);
    }

    public function genEntity($tableName)
    {
        echo __FUNCTION__ . "\n";
        $patterns = [
            self::escapePattern('methods'),
            self::escapePattern('props'),
        ];

        $replacements = [
            $this->genMethodsComments(),
            $this->genProperties()
        ];

        return $this->substituteTemplate($this->getTemplateFileName('entity.txt'), $patterns, $replacements);
    }

    public function genEntityDTO($tableName)
    {
        echo __FUNCTION__ . "\n";
        $patterns = [
            self::escapePattern('entityNameDTO'),
            self::escapePattern('methods'),
            self::escapePattern('props'),
        ];

        $replacements = [
            $this->getEntityName($tableName) . 'Dto',
            $this->genMethodsComments(),
            $this->genProperties()
        ];

        return $this->substituteTemplate($this->getTemplateFileName('entityDTO.txt'), $patterns, $replacements);
    }

    public function genMappings()
    {
        echo __FUNCTION__ . "\n";
        $patterns = [
            self::escapePattern('mappingEntries')
        ];

        $replacements = [
            $this->getMappingEntries()
        ];

        return $this->substituteTemplate($this->getTemplateFileName('entityMapping.txt'), $patterns, $replacements);
    }

    public function genTransformer()
    {
        echo __FUNCTION__ . "\n";
        $classNameDTO = $this->getClassName($this->tableName) . 'DTO';
        $entityNameDTO = $this->getEntityName($this->tableName) . 'Dto';
        $tEntityToView = $this->getTransformEntityToView();
        $tRequestToDTO = $this->getTransformRequestToDTO($entityNameDTO);
        $tDTOToEntity = $this->getTransformDTOToEntity($entityNameDTO);

        $patterns = [
            self::escapePattern('classNameDTO'),
            self::escapePattern('entityNameDTO'),
            self::escapePattern('tEntityToView'),
            self::escapePattern('tRequestToDTO'),
            self::escapePattern('tDTOToEntity')
        ];
        $replacements = [
            $classNameDTO,
            $entityNameDTO,
            $tEntityToView,
            $tRequestToDTO,
            $tDTOToEntity
        ];

        return $this->substituteTemplate($this->getTemplateFileName('entityTransformer.txt'), $patterns, $replacements);
    }

    public function genActionSave()
    {
        echo __FUNCTION__ . "\n";
        $patterns = [
            self::escapePattern('entityNameDto'),
        ];
        $replacements = [
            $this->getEntityName($this->tableName) . 'Dto'
        ];

        return $this->substituteTemplate($this->getTemplateFileName('SaveEntity.txt'), $patterns, $replacements);
    }

    public function genActionUpdate()
    {
        echo __FUNCTION__ . "\n";
        $patterns = [
            self::escapePattern('entityNameDto'),
        ];
        $replacements = [
            $this->getEntityName($this->tableName) . 'Dto'
        ];

        return $this->substituteTemplate($this->getTemplateFileName('UpdateEntity.txt'), $patterns, $replacements);
    }

    protected function getTemplateFileName($filename): string
    {
        return implode(DIRECTORY_SEPARATOR, [self::BACKEND_TEMPLATE_DIR, $filename]);
    }

    public function filterColumns($entitiesData)
    {
        $keys = ['COLUMN_NAME', 'DATA_TYPE', 'DEFAULT', 'IS_NULLABLE', 'LENGTH'];
        $cols = array_map(fn ($d) => array_values(array_filter($d, fn ($k) => in_array($k, $keys), ARRAY_FILTER_USE_KEY)), $entitiesData);
        return $cols;
    }

    private function getGetter($colName)
    {
        return sprintf('get%s()', self::toCamelCase($colName));
    }

    private function getSetter($colName, $arg)
    {
        return sprintf('set%s(%s)', self::toCamelCase($colName), $arg);
    }

    private function genProperties()
    {
        $fmt = "%4sprotected %s \$%s;";
        $props = array_map(fn ($col) => sprintf($fmt, '', self::DATATYPES[$col['DATA_TYPE']], self::toCamelCase($col['COLUMN_NAME'], true)), $this->entitiesData);
        return implode("\n", $props);
    }

    private function genMethodsComments()
    {
        $fmt = " * @method %s %s";
        $methods = array_map(fn ($col) => sprintf($fmt, $col['DATA_TYPE'], $this->getGetter($col['COLUMN_NAME'])), $this->entitiesData);
        return implode("\n", $methods);
    }

    private function getMappingEntries()
    {
        $line = array_map(fn ($col) => sprintf("%8s%s;", '', $this->getMappingEntry($col)), $this->entitiesData);
        return implode("\n", $line);
    }

    private function getMappingEntry($col)
    {
        $retval = '$builder->';
        if ($col['COLUMN_NAME'] == 'id' || (isset($col['AUTO_INCREMENT']) && $col['AUTO_INCREMENT'] == 'auto_increment')) {
            $retval .= sprintf("%s('%s')", 'increments', $col['COLUMN_NAME']);
        } else {
            $retval .= sprintf("%s('%s')", self::DATATYPE_MAPPING[$col['DATA_TYPE']], $col['COLUMN_NAME']);
        }
        if ($col['LENGTH'] && !in_array($col['DATA_TYPE'], ['longtext', 'text'])) {
            $retval .= "->length({$col['LENGTH']})";
        }
        if ($col['DEFAULT']) {
            $retval .= "->default('{$col['DEFAULT']}')";
        }
        if ($col['IS_NULLABLE'] == 'YES') {
            $retval .= "->nullable()";
        }
        return $retval;
    }

    private function getTransformEntityToView()
    {
        $sq = "'";
        $fmt = "%12s%-15s => \$entity->%s,";
        $line = array_map(fn ($col) => sprintf($fmt, '', $sq . self::toCamelCase($col['COLUMN_NAME'], true) . $sq, $this->getGetter($col['COLUMN_NAME'])), $this->entitiesData);
        return trim(implode("\n", $line));
    }

    private function getTransformRequestToDTO($entity)
    {
        $line = array_map(function ($col) use ($entity) {
            $req = sprintf("\$request->get('%s')", $col['COLUMN_NAME']);
            return sprintf("%12s->%s", '', $this->getSetter($col['COLUMN_NAME'], $req));
        }, $this->entitiesData);
        return trim(implode("\n", $line));
    }

    private function getTransformDTOToEntity($entityDTO)
    {
        $line = array_map(function ($col) use ($entityDTO) {
            $entityDTOGet = sprintf("\$%s->%s", $entityDTO, $this->getGetter($col['COLUMN_NAME']));
            return sprintf("%12s->%s", '', $this->getSetter($col['COLUMN_NAME'], $entityDTOGet));
        }, $this->entitiesData);
        return trim(implode("\n", $line));
    }

    public function writeEntity( string $className, string $outString)
    {
        TemplatorWriter::writeClassFile( $this->parentName, "Entities", $className, "", $outString);
    }
    public function writeEntityDTO( string $className, string $outString)
    {
        TemplatorWriter::writeClassFile( $this->parentName, "DTO", $className, "", $outString);
    }
    public function writeMapping( string $className, string $outString)
    {
        TemplatorWriter::writeClassFile( $this->parentName, "Mappings", $className, "Mapping", $outString);
    }
    public function writeTransformer( string $className, string $outString)
    {
        TemplatorWriter::writeClassFile( $this->parentName, "Transformers", $className, "Tansformer", $outString);
    }
    public function writeAction( string $className, string $outString)
    {
        TemplatorWriter::writeClassFile( $this->parentName, "Actions", $className, "", $outString);
    }
}
