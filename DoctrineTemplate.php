<?php

namespace GenerateEntity;

use GenerateEntity\AdminUtils;

class DoctrineTemplate
{
    private $projectData;
    private $parentName;
    private $tableName;

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

    const VUE_TEMPLATE_DIR = '.\Templates\Vue';

    function __construct($parentName)
    {
        $this->parentName = $parentName;
    }

    public static function toCamelCase($word, $lowercasefirst = false)
    {
        $retval = str_replace(' ', '', ucwords(strtr($word, '_-', ' ')));
        if ($lowercasefirst) {
            $retval = lcfirst($retval);
        }
        return $retval;
    }

    public function setParentName($parentName)
    {
        $this->parentName = $parentName;
    }
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function getClassName($tableName)
    {
        return self::toCamelCase($tableName);
    }

    public function getEntityName($tableName)
    {
        return self::toCamelCase($tableName, true);
    }

    public function setProjectData($projectData)
    {
        $this->projectData = $projectData;
    }

    public function getAppNamespace($appPart)
    {
        return sprintf('Domain\%s\%s', $this->parentName, $appPart);
    }

    public function filterColumns($projectData)
    {
        $keys = ['COLUMN_NAME', 'DATA_TYPE', 'DEFAULT', 'IS_NULLABLE', 'LENGTH'];
        $cols = array_map(fn ($d) => array_values(array_filter($d, fn ($k) => in_array($k, $keys), ARRAY_FILTER_USE_KEY)), $projectData);
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
        $props = array_map(fn ($col) => sprintf($fmt, '', self::DATATYPES[$col['DATA_TYPE']], self::toCamelCase($col['COLUMN_NAME'], true)), $this->projectData);
        return implode("\n", $props);
    }

    private function genMethodsComments()
    {
        $fmt = " * @method %s %s";
        $methods = array_map(fn ($col) => sprintf($fmt, $col['DATA_TYPE'], $this->getGetter($col['COLUMN_NAME'])), $this->projectData);
        return implode("\n", $methods);
    }

    /**
     * Entity
     */
    public function genEntity($tableName)
    {
        $namespace = $this->getAppNamespace('Entities');
        $entityName = $this->getEntityName($tableName);
        $methods = $this->genMethodsComments();
        $props = $this->genProperties();

        $template = <<<EOT
<?php

namespace $namespace;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;
use Support\GettersAndSetters;

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
        return str_replace("$bs$bs", $bs, $template);
    }


    /**
     * Entity
     */
    public function genEntityDTO($tableName)
    {
        $namespace = $this->getAppNamespace('DTO');
        $entityName = $this->getEntityName($tableName . 'DTO');
        $methods = $this->genMethodsComments();
        $props = $this->genProperties();

        $template = <<<EOT
<?php

namespace $namespace;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;
use Support\GettersAndSetters;


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
        return str_replace("$bs$bs", $bs, $template);
    }


    /**
     * Mappings
     */
    private function getMappingEntries()
    {
        $line = array_map(fn ($col) => sprintf("%8s%s;", '', $this->getMappingEntry($col)), $this->projectData);
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
        $namespace = $this->getAppNamespace('Mappings');
        $entityName = $this->getEntityName($this->tableName);
        $useEntity = sprintf("Domain\%s\Entities\%s", $this->parentName, $entityName);
        $mappingEntries = $this->getMappingEntries();
        $className = self::toCamelCase($this->tableName);
        $template = <<<EOT
<?php

namespace $namespace;

use $useEntity;
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
    private function getTransformEntityToView()
    {
        $sq = "'";
        $fmt = "%12s%-15s => \$entity->%s,";
        $line = array_map(fn ($col) => sprintf($fmt, '', $sq . self::toCamelCase($col['COLUMN_NAME'], true) . $sq, $this->getGetter($col['COLUMN_NAME'])), $this->projectData);
        return trim(implode("\n", $line));
    }

    private function getTransformRequestToDTO($entity)
    {
        $line = array_map(function ($col) use ($entity) {
            $req = sprintf("\$request->get('%s')", $col['COLUMN_NAME']);
            return sprintf("%12s->%s", '', $this->getSetter($col['COLUMN_NAME'], $req));
        }, $this->projectData);
        return trim(implode("\n", $line));
    }

    private function getTransformDTOToEntity($entityDTO)
    {
        $line = array_map(function ($col) use ($entityDTO) {
            $entityDTOGet = sprintf("\$%s->%s", $entityDTO, $this->getGetter($col['COLUMN_NAME']));
            return sprintf("%12s->%s", '', $this->getSetter($col['COLUMN_NAME'], $entityDTOGet));
        }, $this->projectData);
        return trim(implode("\n", $line));
    }

    public function genTransformer()
    {
        $namespace = $this->getAppNamespace('Transformers');
        $tableName = $this->tableName;
        $entityName = $this->getEntityName($tableName);
        $className = $this->getClassName($tableName);
        $entityNameDTO = $entityName . 'DTO';
        $classNameDTO = $className . 'DTO';
        $tEntityToView = $this->getTransformEntityToView();
        $tRequestToDTO = $this->getTransformRequestToDTO($entityNameDTO);
        $tDTOToEntity = $this->getTransformDTOToEntity($entityNameDTO);

        $useEntity = sprintf("Domain\%s\Entities\%s", $this->parentName, $className);
        $useEntityDTO = sprintf("Domain\%s\DTO\%s", $this->parentName, $classNameDTO);

        $template = <<<EOT
<?php

namespace $namespace;

use Carbon\Carbon;
use LaravelDoctrine\ORM\Facades\EntityManager;
use $useEntity;
use $useEntityDTO;
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

    public static function transformDTOToEntity (DTO \$entityDTO, {$classNameDTO} \${$entityNameDTO} = null): BaseEntity
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

    /**
     * Vue templates
     */
    const VUE_DATATYPES = [
        'varchar' => 'string',
        'text' => 'string',
        'date' => 'attr',
        'datetime' => 'attr',
        'int' => 'number',
        'float' => 'number',
    ];

    private static function escapePattern( $searchString)
    {
        return sprintf( '/\{\$%s\}/', $searchString);
    }

    private function getVueColumns( $cols)
    {
        $colData = array_map( fn($col) => $this->getVueColumn($col), $this->projectData);
        return implode( ",\n", $colData);
    }

    private function getVueColumn( $col) {
        $fmt = "%12s%s: this.%s()";
        if( $col['AUTO_INCREMENT'] == 'auto_increment') {
            return sprintf( $fmt, '', $col['COLUMN_NAME'], 'uid');
        }
        return sprintf( $fmt, '', self::toCamelCase($col['COLUMN_NAME'], true), self::VUE_DATATYPES[$col['DATA_TYPE']]);
    }

    public function genVueModel()
    {
        $className = $this->getClassName($this->tableName);
        $entityName = $this->getEntityName($this->tableName);
        $colData = $this->getVueColumns($this->projectData);
        $tmplt = <<<EOT
import { Model } from '@vuex-orm/core'

export default class $className extends Model {
    static entity = '$entityName'

    static fields () {
        return {
$colData
        }
    }
}

EOT;
        return $tmplt;
    }

    private function substituteVueTemplate( $templateFile, $aPatterns, $aReplacements)
    {
        $className = $this->getClassName($this->tableName);
        $title = (substr($className, -1) == 'y') ? substr( $className, 0, -1) . 'ies' : $className . 's';
        $entityName = $this->getEntityName($this->tableName);
        $entitiesName = (substr($entityName, -1) == 'y') ? substr( $entityName, 0, -1) . 'ies' : $className . 's';

        $basePattern = [
            self::escapePattern( 'className'),
            self::escapePattern('title'),
            self::escapePattern('entitiesName'),
            self::escapePattern('entityName')
        ];
        $patterns = array_merge( $basePattern, $aPatterns);
        var_dump( $patterns);
        $replacements = array_merge([$className, $title, $entitiesName, $entityName], $aReplacements);
        echo "&&&&&&\n";
        var_dump($replacements);
        $template = file_get_contents(self::getTemplateFileName( $templateFile));
        $instance = preg_replace($patterns, $replacements, $template);
        var_dump($instance);
        echo "***************";
        return $instance;
    }

    public function genVueIndex()
    {
        $tableColData = $this->getVueColumns($this->projectData);
        $indexFile = $this->substituteVueTemplate( 'index.vue.txt', ['/\{\$tableColData\}/'], [$tableColData]);
        return $indexFile;
    }

    public function genVueEdit()
    {
        $tableColData = $this->getVueColumns($this->projectData);
        $editFile = $this->substituteVueTemplate( 'edit.vue.txt', [], []);
        var_dump($editFile);
    }

    public function genVueCreate()
    {
        $tableColData = $this->getVueColumns($this->projectData);
        $createFile = $this->substituteVueTemplate( 'create.vue.txt', [], []);
        var_dump($createFile);
    }

    private function getVueTextInputFields($cols)
    {
        $template = file_get_contents(self::getTemplateFileName( 'text-input.vue.txt'));
        $entityName = $this->getEntityName($this->tableName);
        $patterns = [
            self::escapePattern( 'entityName'),
            self::escapePattern('colName'),
            self::escapePattern('colLabel')
        ];
        $textInputs = array_map( function ($col) use ($template, $entityName, $patterns) {
            $replace = [ $entityName, $col['COLUMN_NAME'], str_replace( '_', ' ', $col['COLUMN_NAME'])];
          return preg_replace( $patterns, $replace, $template);
        }, $cols);
        return $textInputs;
    }
    public function genVueEntityForm()
    {
        $cols = $this->projectData[$this->parentName][$this->tableName];
        $textInputFields = $this->getVueTextInputFields($cols);
        $entityFormFile = $this->substituteVueTemplate( 
            'entityForm.vue.txt',
            self::escapePattern('textInputFields'),
            $textInputFields
            );
        var_dump($entityFormFile);

        // var_dump($indexTmplt);
    }

    private static function getTemplateFileName($filename)
    {
        return implode(DIRECTORY_SEPARATOR, [self::VUE_TEMPLATE_DIR, $filename]);
    }
}
