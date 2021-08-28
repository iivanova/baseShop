<?php

class ProductModel
{

    protected $tableName = 'product';
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllItems()
    {

        $sql = "SELECT * FROM {$this->tableName}";
        $items = $this->db->query($sql);
        return $items;
    }

    public function getProduct($id)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id=?";
        $item = $this->db->query($sql,[$id]);
        return (isset($item[0]))?$item[0]:[];
    }

}
