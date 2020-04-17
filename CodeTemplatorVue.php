<?php

namespace GenerateEntity;

class CodeTemplatorVue extends CodeTemplator
{
    /**
     * Vue templates
     */
    const VUE_TEMPLATE_DIR = '.\Templates\Vue';

    const VUE_DATATYPES = [
        'varchar' => 'string',
        'text' => 'string',
        'date' => 'attr',
        'datetime' => 'attr',
        'int' => 'number',
        'float' => 'number',
        'boolean' => 'bool'
    ];

    public function instantiateParts()
    {
        $className = $this->getClassName($this->tableName);
        $vueModel = $this->genVueModel($this->tableName);
        $this->writeVueModel($className, $vueModel);
        $vueIndex = $this->genVueIndex($this->tableName);
        $this->writeVuePage($className, 'Index', $vueIndex);
        $vueEdit = $this->genVueEdit($this->tableName);
        $this->writeVuePage($className, 'Edit', $vueEdit);
        $vueCreate = $this->genVueCreate($this->tableName);
        $this->writeVuePage($className, 'Create', $vueCreate);
        $vueEntityForm = $this->genVueEntityForm($this->tableName);
        $this->writeVuePage($className, $className . 'Form', $vueEntityForm);
    }

    public function genVueIndex()
    {
        $tableColData = $this->getVueIndexColumns($this->entitiesData);
        $indexFile = $this->substituteTemplate(
            $this->getTemplateFileName('index.vue.txt'),
            ['/\{\$tableColData\}/'],
            [$tableColData]
        );
        return $indexFile;
    }

    public function genVueEdit()
    {
        echo __FUNCTION__ . "\n";
        return $this->substituteTemplate($this->getTemplateFileName('edit.vue.txt'));
    }

    public function genVueCreate()
    {
        echo __FUNCTION__ . "\n";
        return $this->substituteTemplate($this->getTemplateFileName('create.vue.txt'));
    }

    public function genVueEntityForm()
    {
        echo __FUNCTION__ . "\n";
        $textInputFields = $this->getVueTextInputFields($this->entitiesData);
        $entityFormFile = $this->substituteTemplate(
            $this->getTemplateFileName('entityForm.vue.txt'),
            [self::escapePattern('textInputFields'), self::escapePattern('hasManyManager')],
            [$textInputFields, ''],

        );
        return $entityFormFile;
    }

    protected function getTemplateFileName($filename): string
    {
        return implode(DIRECTORY_SEPARATOR, [self::VUE_TEMPLATE_DIR, $filename]);
    }

    private function getVueColumns($cols)
    {
        $colData = array_map(fn ($col) => $this->getVueColumn($col), $this->entitiesData);
        return implode(",\n", $colData);
    }

    private function getVueColumn($col)
    {
        $fmt = "%12s%s: this.%s()";
        if ($col['EXTRA'] == 'auto_increment') {
            return sprintf($fmt, '', $col['COLUMN_NAME'], 'uid');
        }
        $dataType = array_key_exists($col['DATA_TYPE'], self::VUE_DATATYPES) ? self::VUE_DATATYPES[$col['DATA_TYPE']] : $col['DATA_TYPE'];
        return sprintf($fmt, '', self::toCamelCase($col['COLUMN_NAME'], true), $dataType);
    }

    public function genVueModel()
    {
        $className = self::getClassName($this->tableName);
        $entityName = self::getEntityName($this->tableName);
        $colData = $this->getVueColumns($this->entitiesData);
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

    private function getVueIndexColumns($cols)
    {
        $colData = array_map(fn ($col) => $this->getVueIndexColumn($col), $this->entitiesData);
        return implode(",\n", $colData);
    }

    private function getVueIndexColumn($col)
    {
        $fmt = <<<EOT
        {
            text: this.\$i18n.t("%s.%s"),
            value: "%s",
            sortable: true
EOT;
        if (array_key_exists($col['DATA_TYPE'], self::VUE_DATATYPES) && self::VUE_DATATYPES[$col['DATA_TYPE']] == 'number') {
            $fmt .= <<<EOT
,
            align: "end",
            width: "45"
EOT;
        }
        $fmt .= <<<EOT

        }
EOT;
        return sprintf(
            $fmt,
            self::getEntityName($this->tableName),
            self::toCamelCase($col['COLUMN_NAME'], true),
            self::toCamelCase($col['COLUMN_NAME'], true)
        );
    }

    private function getVueTextInputFields($cols)
    {
        $template = file_get_contents(self::getTemplateFileName('text-input.vue.txt'));
        $entityName = $this->getEntityName($this->tableName);
        $patterns = [
            self::escapePattern('entityName'),
            self::escapePattern('colName'),
            self::escapePattern('colLabel')
        ];
        $textInputs = array_map(function ($col) use ($template, $entityName, $patterns) {
            $replace = [$entityName, $col['COLUMN_NAME'], ucwords(str_replace('_', ' ', $col['COLUMN_NAME']))];
            return preg_replace($patterns, $replace, $template);
        }, $cols);
        return implode("\n", $textInputs);
    }
    /**
     * Vue templates
     */
    private function writeVuePage(string $className, string $viewName, string $outString)
    {
        TemplatorWriter::writeResourceFile("Pages", $className, $viewName, $outString);
    }
    private function writeVueModel(string $className, string $outString)
    {
        $subDirectory = implode( DIRECTORY_SEPARATOR, [$this->parentName, $className]);
        TemplatorWriter::writeResourceFile("Models", $subDirectory, $className, $outString);
    }
}
