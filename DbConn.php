<?php
namespace GenerateEntity;

require_once __DIR__.'/vendor/autoload.php';

use PDO;
use Symfony\Component\Dotenv\Dotenv;

class DbConn
{
    private $pdo;

    function __construct( $envfile = null)
    {
        $dotenv = new Dotenv();
        $filename = ($envfile) ? $envfile : '.env';
        $dotenv->load($filename);
        $this->connect();
    }

// echo $_ENV['DB_CONNECTION'];
// echo $_ENV['DB_HOST'];
// echo $_ENV['DB_PORT'];
// echo $_ENV['DB_DATABASE'];
// echo $_ENV['DB_USERNAME'];
// echo $_ENV['DB_PASSWORD'];
    private function connect()
    {
        $options = [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $database = 'INFORMATION_SCHEMA'; // $_ENV['DB_DATABASE'];
        $user = $_ENV['DB_USERNAME'];
        $pwd = $_ENV['DB_PASSWORD'];
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$database;charset=$charset";
        try {
             $this->pdo = new \PDO($dsn, $user, $pwd, $options);
        } catch (\PDOException $e) {
             throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getTables( )
    {
        $sql = "SELECT * FROM TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = '{$_ENV['DB_DATABASE']}' ORDER BY TABLE_NAME";
        $data = $this->pdo->query( $sql, PDO::FETCH_ASSOC);
        $tables = [];
        foreach($data as $row) {
            $tables[] = $row['TABLE_NAME'];
        }
        return $tables;
    }

    public function getColumns( string $tablename)
    {
        $sql = "SELECT * FROM COLUMNS WHERE TABLE_NAME = '$tablename' ORDER BY ORDINAL_POSITION";
        $data = $this->pdo->query( $sql, PDO::FETCH_ASSOC);
        $res = [];
        foreach($data as $row) {
            $res[] = $row;
        }
        return $res;
    }
}
