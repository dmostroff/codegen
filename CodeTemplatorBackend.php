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
        TemplatorWriter::writeEntity($this->parentName, $className, $entity);
        $entity = $this->genEntityDTO($this->tableName);
        TemplatorWriter::writeEntityDTO($this->parentName, $className, $entity);
        $entity = $this->genMappings($this->tableName);
        TemplatorWriter::writeMapping($this->parentName, $className, $entity);
        $entity = $this->genTransformer($this->tableName);
        TemplatorWriter::writeTransformer($this->parentName, $className, $entity);
    }


    protected static function getTemplateFileName($filename): string
    {
        return implode(DIRECTORY_SEPARATOR, [self::BACKEND_TEMPLATE_DIR, $filename]);
    }

    public function getAppNamespace($appPart)
    {
        return sprintf('Domain\%s\%s', $this->parentName, $appPart);
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
    private function mapDataTypes($dataType)
    {
    }
    public function genProperties()
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

    /**
     * Entity
     */
    public function genEntity($tableName)
    {
        $patterns = [
            self::escapePattern('namespace'),
            self::escapePattern('entityName'),
            self::escapePattern('methods'),
            self::escapePattern('props'),
        ];

        $replacements = [
            $this->getAppNamespace('Entities'),
            $this->getEntityName($tableName),
            $this->genMethodsComments(),
            $this->genProperties()
        ];

        $templateFile = self::getTemplateFileName('entity.txt');
        return self::replaceTemplate($patterns, $replacements, $templateFile);
    }


    /**
     * Entity
     */
    public function genEntityDTO($tableName)
    {
        $patterns = [
            self::escapePattern('namespace'),
            self::escapePattern('entityNameDTO'),
            self::escapePattern('methods'),
            self::escapePattern('props'),
        ];

        $replacements = [
            $this->getAppNamespace('DTO'),
            $this->getEntityName($tableName) . 'DTO',
            $this->genMethodsComments(),
            $this->genProperties()
        ];

        $templateFile = self::getTemplateFileName('entityDTO.txt');
        return self::replaceTemplate($patterns, $replacements, $templateFile);
    }


    /**
     * Mappings
     */
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

    public function genMappings()
    {
        $patterns = [
            self::escapePattern('namespace'),
            self::escapePattern('className'),
            self::escapePattern('entityName'),
            self::escapePattern('useEntity'),
            self::escapePattern('mappingEntries')
        ];

        $replacements = [
            $this->getAppNamespace('Mappings'),
            self::getClassesName($this->tableName),
            $this->getEntityName($this->tableName),
            sprintf("Domain\%s\Entities\%s", $this->parentName, $this->getEntityName($this->tableName)),
            $this->getMappingEntries()
        ];

        $templateFile = self::getTemplateFileName('entityMapping.txt');
        return self::replaceTemplate($patterns, $replacements, $templateFile);
    }

    /**
     * Transformations
     */
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

    public function genTransformer()
    {
        $namespace = $this->getAppNamespace('Transformers');
        $entityName = $this->getEntityName($this->tableName);
        $className = $this->getClassName($this->tableName);
        $entityNameDTO = $entityName . 'DTO';
        $classNameDTO = $className . 'DTO';
        $tEntityToView = $this->getTransformEntityToView();
        $tRequestToDTO = $this->getTransformRequestToDTO($entityNameDTO);
        $tDTOToEntity = $this->getTransformDTOToEntity($entityNameDTO);

        $useEntity = sprintf("Domain\%s\Entities\%s", $this->parentName, $className);
        $useEntityDTO = sprintf("Domain\%s\DTO\%s", $this->parentName, $classNameDTO);
        $patterns = [
            self::escapePattern('namespace'),
            self::escapePattern('useEntity'),
            self::escapePattern('useEntityDTO'),
            self::escapePattern('className'),
            self::escapePattern('classNameDTO'),
            self::escapePattern('entityNameDTO'),
            self::escapePattern('tEntityToView'),
            self::escapePattern('tRequestToDTO'),
            self::escapePattern('tDTOToEntity')
        ];
        $replacements = [
            $namespace,
            $useEntity,
            $useEntityDTO,
            $className,
            $classNameDTO,
            $entityNameDTO,
            $tEntityToView,
            $tRequestToDTO,
            $tDTOToEntity
        ];

        $templateFile = self::getTemplateFileName('entityTransformer.txt');
        return self::replaceTemplate($patterns, $replacements, $templateFile);
    }
}
