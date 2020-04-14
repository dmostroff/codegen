<?php

namespace GenerateEntity;

class MetaDataReader
{
    public function read( string $filename)
    {
        $json = file_get_contents($filename);
        echo "Reading..." . $filename;
        $metaData = json_decode($json, true);
        if( $metaData) {
            $metaData['Entities'] = $this->convertData($metaData['Entities']);
        }
        return $metaData;
    }

    private function convertData(array $metaData)
    {
        $parentData = [];
        foreach ($metaData  as $parent => $tables) {
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
        $tableData = [
            $this->convertCols( "id", [ "int", null, null, null, "auto_increment"])
        ];
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