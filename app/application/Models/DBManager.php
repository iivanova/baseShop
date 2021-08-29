<?php
namespace App\Models;

class DBManager
{

    private $config;
    private $pdo;
    private $lastInsertId;
    
    public function __construct($conf)
    {

        $this->config = $conf;
        $this->connectPDO();
    }


    public function connectPDO()
    {
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new \PDO($this->config['db_pdo'], $this->config['db_username'], $this->config['db_password']);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

    }

    public function exec($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute($params);
        
        return $stmt;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->exec($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert($sql, $params = [])
    {
        $this->exec($sql, $params);
        $this->lastInsertId = $this->pdo->lastInsertId();
        return $this->lastInsertId;
    }
    public function lastInsertId(){
        return $this->lastInsertId;
    }

}
