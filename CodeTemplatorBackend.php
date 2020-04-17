<?php

namespace GenerateEntity;

class CodeTemplatorBackend extends CodeTemplator
{
    const BACKEND_TEMPLATE_DIR = '.\Templates\Backend';

    const DATATYPES = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'int',
        'float' => 'float',
        'boolean' => 'boolean',
        'tinyint' => 'boolean',
        'datetime' => 'Carbon',
        'date' => 'Carbon',
        'text' => 'string',
        'longtext' => 'string'
    ];

    const DATATYPE_MAPPING = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'integer',
        'float' => 'float',
        'boolean' => 'boolean',
        'tinyint' => 'boolean',
        'datetime' => 'dateTime',
        'date' => 'date',
        'text' => 'text',
        'longtext' => 'text'
    ];

    const DATA_RELATION = [
        'hasOne',
        'hasMany',
        'belongsTo',
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
        $entity = $this->genActionSave($this->tableName);
        $this->writeAction($className, $entity);
        $entity = $this->genActionUpdate($this->tableName);
        $this->writeAction($className, $entity);
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

    private function getGetter($col)
    {
        return sprintf('get%s()', self::toCamelCase($col['COLUMN_NAME']));
    }

    private function getSetter($col, $arg)
    {
        $dataType = (in_array($col['EXTRA'] ?? '', self::DATA_RELATION)) ? "Collection" : self::DATATYPES[$col['DATA_TYPE']];
        return sprintf('set%s(%s)', self::toCamelCase($col['COLUMN_NAME']), $arg);
    }

    private function getColumnProperty($col)
    {
        if( $col['EXTRA'] == 'belongsTo') { return null; }
        // var_export($col);
        $map = [
            'hasOne' => $col['DATA_TYPE'],
            'hasMany' => 'Collection',
            'null' => in_array( $col['DATA_TYPE'], self::DATATYPES) ? self::DATATYPES[$col['DATA_TYPE']] : $col['DATA_TYPE'],
            'auto_increment' => 'int'
        ];
        return sprintf("%4sprotected %s \$%s;", '', $this->getDataType($col), self::toCamelCase($col['COLUMN_NAME'], true));
    }

    private function genProperties()
    {
        $props = array_map(fn ($col) => $this->getColumnProperty($col), $this->entitiesData);
        return implode("\n", $props);
    }

    private function getMethodColumnGetter($col)
    {
        return sprintf(" * @method %s %s", $this->getDataType($col), $this->getGetter($col));
    }

    private function getMethodColumnSetter($col)
    {
        $args = sprintf( "%s \$%s", $this->getDataType($col), self::toCamelCase($col['COLUMN_NAME'], true));
        return sprintf(" * @method %s %s", self::toCamelCase($this->tableName), $this->getSetter($col, $args));
    }

    private function genMethodsComments()
    {
        $getters = array_map(fn ($col) => $this->getMethodColumnGetter($col), $this->entitiesData);
        $setters = array_map(fn ($col) => $this->getMethodColumnSetter($col), $this->entitiesData);
        return implode("\n", array_merge($getters, $setters));
    }

    private function getDataType($col)
    {
        $defaultType = array_key_exists( $col['DATA_TYPE'], self::DATATYPES) ? self::DATATYPES[$col['DATA_TYPE']] : $col['DATA_TYPE'];
        $map = [
            'hasOne'         => $col['DATA_TYPE'],
            'hasMany'        => 'Collection',
            'auto_increment' => 'int'
        ];
        return array_key_exists($col['EXTRA'], $map) ? $map[$col['EXTRA']] : $defaultType;
    }

    private function getMappingDataType($col)
    {
        $defaultType = array_key_exists( $col['DATA_TYPE'], self::DATATYPE_MAPPING) ? self::DATATYPE_MAPPING[$col['DATA_TYPE']] : $col['DATA_TYPE'];
        $map = [
            'hasOne'         => $col['DATA_TYPE'],
            'hasMany'        => 'Collection',
            'auto_increment' => 'int'
        ];
        return array_key_exists($col['EXTRA'], $map) ? $map[$col['EXTRA']] : $defaultType;
    }

    private function getMappingEntries()
    {
        $line = array_map(fn ($col) => sprintf("%8s%s;", '', $this->getMappingEntry($col)), $this->entitiesData);
        return implode("\n", $line);
    }

    private function getMappingEntry($col)
    {
        $relations = [
            'hasOne',
            'hasMany'
        ];
        $retval = '$builder->';
        if ($col['COLUMN_NAME'] == 'id' || (isset($col['EXTRA']) && $col['EXTRA'] == 'auto_increment')) {
            $retval .= sprintf("%s('%s')", 'increments', $col['COLUMN_NAME']);
        }
        if( in_array( $col['EXTRA'], $relations)) {
            $retval .= sprintf( "%s(%s::class, '%s')->ownedBy('%s')",
                $col['EXTRA'],
                $col['DATA_TYPE'],
                self::toCamelCase( $col['COLUMN_NAME'], true),
                self::toCamelCase( $this->tableName, true)
            );
        } else if ( $col['EXTRA'] == 'belongsTo') {
            $retval .= sprintf( "belongsTo(%s::class)->inversedBy('%s')", self::toCamelCase($col['DATA_TYPE']), self::toCamelCase($col['COLUMN_NAME'], true));
        } else {
            $retval .= sprintf( "%s('%s')", $this->getMappingDataType($col), self::toCamelCase($col['COLUMN_NAME'], true));
        }
        if ($col['LENGTH'] && in_array($col['DATA_TYPE'], ['char', 'varchar'])) {
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
        $fmt = "%12s%-18s => \$entity->%s,";
        $line = array_map(fn ($col) => sprintf($fmt, '', $sq . self::toCamelCase($col['COLUMN_NAME'], true) . $sq, $this->getGetter($col)), $this->entitiesData);
        return trim(implode("\n", $line));
    }

    private function getTransformRequestToDTO($entity)
    {
        $line = array_map(function ($col) use ($entity) {
            $req = sprintf("\$request->get('%s')", $col['COLUMN_NAME']);
            return sprintf("%12s->%s", '', $this->getSetter($col, $req));
        }, $this->entitiesData);
        return trim(implode("\n", $line));
    }

    private function getTransformDTOToEntity($entityDTO)
    {
        $line = array_map(function ($col) use ($entityDTO) {
            $entityDTOGet = sprintf("\$%s->%s", $entityDTO, $this->getGetter($col));
            return sprintf("%12s->%s", '', $this->getSetter($col, $entityDTOGet));
        }, $this->entitiesData);
        return trim(implode("\n", $line));
    }

    public function writeEntity(string $className, string $outString)
    {
        TemplatorWriter::writeClassFile($this->parentName, "Entities", $className, "", $outString);
    }
    public function writeEntityDTO(string $className, string $outString)
    {
        TemplatorWriter::writeClassFile($this->parentName, "DTO", $className, "", $outString);
    }
    public function writeMapping(string $className, string $outString)
    {
        TemplatorWriter::writeClassFile($this->parentName, "Mappings", $className, "Mapping", $outString);
    }
    public function writeTransformer(string $className, string $outString)
    {
        TemplatorWriter::writeClassFile($this->parentName, "Transformers", $className, "Transformer", $outString);
    }
    public function writeAction(string $className, string $outString)
    {
        TemplatorWriter::writeClassFile($this->parentName, "Actions", $className, "", $outString);
    }
}
