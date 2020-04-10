<?php
namespace GenerateEntity;

use GenerateEntity\AdminUtils;

class DoctrineTemplate
{ 
    private $colData;
    const DATATYPES = [
        'varchar' => 'string',
        'char' => 'string',
        'int' => 'integer',
        'datetime' => 'Carbon',
        'date' => 'fmt',
    ];

    function __construct( $table) {
        print "In DoctineTemplate {$table}\n";
    }

    public function setColData( $colData)
    {
        $this->colData = $colData;
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
        $fmt = "%8sprotected %s \$%s;";
        $props = array_map( fn($col) => sprintf( $fmt, '', self::DATATYPES[$col['DATA_TYPE']], self::toCamelCase( $col['COLUMN_NAME'], true)) , $this->colData);
        return implode( "\n", $props);
    }

    private function genMethodsComments()
    {
        $fmt = " * @method %s %s";
        $methods = array_map( fn($col) => sprintf( $fmt, self::DATATYPES[$col['DATA_TYPE']], $this->getGetter( $col['COLUMN_NAME'])) , $this->colData);
        return implode( "\n", $methods);
    }

    public function genEntity( $tableName)
    {
        $entityName = self::toCamelCase($tableName);
        $methods = $this->genMethodsComments();
        $props = $this->genProperties();

        $template = <<<EOT
<?php

namespace Domain\{$entityName}\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;

/**
{$methods}
*/
class $entityName
{
    use TimestampableEntity;

    {$props}
}

EOT;
        return $template;
    }

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

    public function genTransformer( $parentEntity, $tableName)
    {
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
use Domain\{$parentEntity}\DTO\{$classNameDTO};
use Domain\{$parentEntity}}\Entities\{$className};
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