<?php

namespace GenerateEntity;

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'MetaDataReader.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'CodeTemplator.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'TemplatorWriter.php');

use GenerateEntity\MetaDataReader;

class GeneratorService
{
    private $metaData;
    public function init(string $jsonFilename): GeneratorService
    {
        $metaDataReader = new MetaDataReader();
        $this->metaData = $metaDataReader->read($jsonFilename);
        TemplatorWriter::setProjectRoot($this->metaData['Project']['ProjectRoot']);
        TemplatorWriter::setAppRoot($this->metaData['Project']['AppRoot']);
        TemplatorWriter::setResourceRoot($this->metaData['Project']['ResourceRoot']);
        return $this;
    }

    public function runBackend(): GeneratorService
    {
        echo "\n" . __FUNCTION__ . "\n";
        (new CodeTemplatorBackend($this->metaData['Entities']))->instantiate();
        return $this;
    }

    public function runVue(): GeneratorService
    {
        echo "\n" . __FUNCTION__ . "\n";
        (new CodeTemplatorVue($this->metaData['Entities']))->instantiate();
        return $this;
    }
}
