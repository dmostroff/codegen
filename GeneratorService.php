<?php

namespace GenerateEntity;

class GeneratorService
{
    public function readMetaDataFile($jsonFilename)
    {
        $json = file_get_contents($jsonFilename);
        $data = json_decode($json, true);
        $convertedData['Project'] = $data['Project'];
        $convertedData['ProjectData'] = $this->convertData($data['ProjectData']);
        return $convertedData;
    }

    public function run($metaData)
    {
        OutputFile::setProjectRoot($metaData['Project']['ProjectRoot']);
        OutputFile::setAppRoot($metaData['Project']['AppRoot']);
        OutputFile::setResourceRoot($metaData['Project']['ResourceRoot']);
        $dt = new DoctrineTemplate($metaData['Project']['Name']);
        foreach ($metaData['ProjectData'] as $parentName => $tables) {
            $dt->setParentName($parentName);
            foreach ($tables as $tableName => $projectData) {
                $dt->setTableName($tableName);
                $dt->setProjectData($projectData);
                $className = $dt->getClassName($tableName);
                $entity = $dt->genEntity($tableName);
                OutputFile::writeEntity($parentName, $className, $entity);
                $entity = $dt->genEntityDTO($tableName);
                OutputFile::writeEntityDTO($parentName, $className, $entity);
                $entity = $dt->genMappings($tableName);
                OutputFile::writeMapping($parentName, $className, $entity);
                $entity = $dt->genTransformer($tableName);
                OutputFile::writeTransformer($parentName, $className, $entity);
            }
        }
//        file_put_contents(TARGETFILENAME, $json);
    }

    public function convertData($data)
    {
        return $this->convertParent($data);
        // return json_encode($metaData, JSON_PRETTY_PRINT);
    }

    private function convertParent($parents)
    {
        $parentData = [];
        foreach ($parents  as $parent => $tables) {
            $parentData[$parent] = $this->convertTables($tables);
        }
        return $parentData;
    }

    private function convertTables($tables)
    {
        $tablesData = [];
        foreach ($tables as $table => $cols) {
            $tablesData[$table] = $this->convertTable($cols);
        }
        return $tablesData;
    }


    private function convertTable($table)
    {
        $tableData = [];
        foreach ($table as $cols) {
            $tableData[] = $this->convertCols($cols);
        }
        return $tableData;
    }

    private function convertCols($cols)
    {
        $colData = [];
        $keys = ['COLUMN_NAME', 'COLUMN_DEFAULT', 'IS_NULLABLE', 'DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH'];
        $ii = 0;
        foreach ($cols as $colPart) {
            $colData[$keys[$ii++]] = $colPart;
        }
        return $colData;
    }
}
