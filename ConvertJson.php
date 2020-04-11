<?php

namespace GenerateEntity;

class ConvertJson
{
    function __construct() {
        // $this->json = $json;
    }

    public function run($data)
    {
        $metaData = [];
        foreach ($data as $parents) {
            $metaData[] = $this->convertParent($parents);
        }
        return json_encode($metaData, JSON_PRETTY_PRINT);
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
        foreach ($tables as $table => $tableCols) {
            $tablesData[$table] = $this->convertTable($tableCols);
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
