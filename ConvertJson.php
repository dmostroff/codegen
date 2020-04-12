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
        foreach ($table as $colName => $cols) {
            $tableData[] = $this->convertCols($colName, $cols);
        }
        return $tableData;
    }
    private function convertCols($colName, $cols)
    {
        return [
            'COLUMN_NAME' => $colName,
            'DATA_TYPE' => (count($cols) > 0) ? $cols[0] : "varchar",
            'LENGTH' =>  (count($cols) > 1) ? $cols[1] : 32,
            'IS_NULLABLE' =>  (count($cols) > 2 && $cols[2] == 'YES') ? 'YES' : null,
            'DEFAULT' => (count($cols) > 3) ? $cols[3] : null,
            'AUTO_INCREMENT' => (count($cols) > 4) ? $cols[4] : null
        ];
    }
}
