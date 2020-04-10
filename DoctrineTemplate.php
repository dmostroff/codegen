<?php
namespace GenerateEntity;

use GenerateEntity\AdminUtils;

class DoctrineTemplate
{ 
    private $colData;
    private $parentName;
    private $tableName;

    const DATATYPES = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'integer',
        'datetime' => 'Carbon',
        'date' => 'Carbon',
        'tinyint' => 'boolean',
        'longtext' => 'string'
    ];

    const DATATYPE_MAPPING = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'integer',
        'datetime' => 'dateTime',
        'date' => 'date',
        'tinyint' => 'boolean',
        'longtext' => 'text'
    ];
    function __construct( $parentName) {
        $this->parentName = $parentName;
    }

    public function setParentName( $parentName)
    {
        $this->parentName = $parentName;
    }
    public function setTableName( $tableName)
    {
        $this->tableName = $tableName;
    }

    public function getClassName( $tableName)
    {
        return self::toCamelCase($tableName);
    }

    public function setColData( $colData)
    {
        $this->colData = $colData;
    }

    public function filterColumns( $colData) {
        $keys = ['COLUMN_NAME', 'DATA_TYPE', 'COLUMN_DEFAULT', 'IS_NULLABLE', 'CHARACTER_MAXIMUM_LENGTH'];
        $cols = array_map( fn($d) => array_values(array_filter( $d, fn($k) => in_array($k, $keys), ARRAY_FILTER_USE_KEY)), $colData);
        var_dump($cols);
        return $cols;
    }

    private function getGetter( $colName)
    {
        return sprintf( 'get%s()', self::toCamelCase($colName));
    }

    private function getSetter( $colName, $arg)
    {
        return sprintf( 'set%s(%s)', self::toCamelCase($colName), $arg);
    }
    private function mapDataTypes( $dataType)
    {

    }
    public function genProperties()
    {
        $fmt = "%4sprotected %s \$%s;";
        $props = array_map( fn($col) => sprintf( $fmt, '', self::DATATYPES[$col['DATA_TYPE']], self::toCamelCase( $col['COLUMN_NAME'], true)) , $this->colData);
        return implode( "\n", $props);
    }

    private function genMethodsComments()
    {
        $fmt = " * @method %s %s";
        $methods = array_map( fn($col) => sprintf( $fmt, self::DATATYPES[$col['DATA_TYPE']], $this->getGetter( $col['COLUMN_NAME'])) , $this->colData);
        return implode( "\n", $methods);
    }

    /**
     * Entity
     */
    public function genEntity( $tableName)
    {
        $parentName = $this->parentName;
        $entityName = self::toCamelCase($tableName);
        $methods = $this->genMethodsComments();
        $props = $this->genProperties();

        $template = <<<EOT
<?php

namespace Domain\$parentName\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;

/**
{$methods}
*/
class $entityName
{
    use GettersAndSetters;
    use TimestampableEntity;

{$props}
}

EOT;
        $bs = chr(92);
        return str_replace( "$bs$bs", $bs, $template);
    }


    /**
     * Entity
     */
    public function genEntityDTO( $tableName)
    {
        $parentName = $this->parentName;
        $entityName = self::toCamelCase($tableName) . 'DTO';
        $methods = $this->genMethodsComments();
        $props = $this->genProperties();

        $template = <<<EOT
<?php

namespace Domain\$parentName\DTO;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;

/**
{$methods}
*/
class $entityName
{
    use GettersAndSetters;
    use TimestampableEntity;

{$props}
}

EOT;
        $bs = chr(92);
        return str_replace( "$bs$bs", $bs, $template);
    }


    /**
     * Mappings
     */
    private function getMappingEntries()
    {
        $line = array_map( fn($col) => sprintf( "%8s%s;", '', $this->getMappingEntry( $col)) , $this->colData);
        return implode( "\n", $line);
    }

    private function getMappingEntry( $col)
    {
        $retval = '$builder->';
        // if( $col['COLUMN_NAME'] == 'id' || 
        if( $col['EXTRA'] == 'auto_increment') {
            $retval .= 'increments';
        } else {
            $retval .= self::DATATYPE_MAPPING[$col['DATA_TYPE']];
        }
        if( $col['CHARACTER_MAXIMUM_LENGTH']) {
            $retval .= "->length({$col['CHARACTER_MAXIMUM_LENGTH']})";
        }
        if( $col['COLUMN_DEFAULT']) {
            $retval .= "->default('{$col['COLUMN_DEFAULT']}')";
        }
        if( $col['IS_NULLABLE'] == 'YES') {
            $retval .= "->nullable()";
        }
        return $retval;
    }

    public function genMappings()
    {
        $mappingEntries = $this->getMappingEntries();
        $className = self::toCamelCase($this->colData[0]['TABLE_NAME']);
        $template =<<<EOT
<?php

namespace Domain\{$this->parentName}\Mappings;

use LaravelDoctrine\Fluent\EntityMapping;
use LaravelDoctrine\Fluent\Fluent;

class ContactAddressMapping extends EntityMapping
{
    /**
     * @return string
     */
    public function mapFor()
    {
        return {$className}::class;
    }

    /**
     * @param Fluent \$builder
     */
    public function map(Fluent \$builder)
    {
        {$mappingEntries}
    }
}
EOT;
        return $template;
}
    /**
     * Transformations
     */
    private function getTransformEntityToView( )
    {
        $sq = "'";
        $fmt = "%12s%-15s => \$entity->%s,";
        $line = array_map( fn($col) => sprintf( $fmt, '', $sq . self::toCamelCase( $col['COLUMN_NAME'], true) . $sq, $this->getGetter($col['COLUMN_NAME'])) , $this->colData);
        return implode( "\n", $line);
    }

    private function getTransformRequestToDTO( $entity) {
        $line = array_map( function($col) use ($entity) {
            $req = sprintf("\$request->get('%s')", $col['COLUMN_NAME']);
            return sprintf( "%12s->%s", '', $this->getSetter( $col['COLUMN_NAME'], $req));
        }, $this->colData);
        return implode( "\n", $line);
    }

    private function getTransformDTOToEntity($entityDTO) {
        $line = array_map( function( $col) use( $entityDTO) {
            $entityDTOGet = sprintf("\$%s->%s", $entityDTO, $this->getGetter($col['COLUMN_NAME']));
            return sprintf( "%12s->%s", '', $this->getSetter($col['COLUMN_NAME'], $entityDTOGet));
        }, $this->colData);
        return implode( "\n", $line);
    }

    public function genTransformer( )
    {
        $tableName = $this->colData[0]['TABLE_NAME'];
        $className = self::toCamelCase($tableName);
        $className = self::toCamelCase($tableName);
        $entityName = self::toCamelCase($tableName, true);
        $entityNameDTO = self::toCamelCase($tableName, true) . 'DTO';
        $classNameDTO = self::toCamelCase($tableName) . 'DTO';
        $tEntityToView = $this->getTransformEntityToView();
        $tRequestToDTO = $this->getTransformRequestToDTO($entityNameDTO);
        $tDTOToEntity = $this->getTransformDTOToEntity($entityNameDTO);
        $template = <<<EOT
<?php


namespace Domain\Donor\Transformers;


use Carbon\Carbon;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Domain\{$this->parentName}\DTO\{$classNameDTO};
use Domain\{$this->parentName}}\Entities\{$className};
use Illuminate\Foundation\Http\FormRequest;
use Support\DTO\DTO;
use Support\Entities\BaseEntity;
use Support\GlobalUtility;
use Support\Transformers\BaseTransformer;

class DonorTransformer extends BaseTransformer
{
    public static function transformEntityToView (?BaseEntity \$entity): array
    {
        if (is_null(\$entity)) {
            return null;
        }

        return [
{$tEntityToView}
        ];
    }

    public static function transformRequestToDTO (FormRequest \$request): DTO
    {
        \$entityDTO = new {$classNameDTO}();
        \$entityDTO{$tRequestToDTO}
            ;

        return \$entityDTO;
    }

    public static function transformDTOToEntity (DTO \$entityDTO, {$className} \${$entityName} = null): BaseEntity
    {
        \$entity = \${$entityName} ?? new {$className}();
        \$entity{$tDTOToEntity}
            ;

        return \$entity;
    }
}
EOT;
        return $template;
    }

    public static function toCamelCase($word, $lowercasefirst = false)
    {
        $retval = str_replace(' ', '', ucwords(strtr($word, '_-', ' ')));
        if( $lowercasefirst)
        {
            $retval = lcfirst($retval);
        }
        return $retval;
    }

}