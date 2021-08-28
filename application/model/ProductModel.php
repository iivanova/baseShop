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
        $sql = "SELECT * FROM {$this->tableName} p"
                . " LEFT JOIN product_discounts ps on ps.product_id = p.id WHERE p.id=?";

        $item = $this->db->query($sql, [$id]);
        return (isset($item[0])) ? $item[0] : [];
    }

    public function validateData($data, $key)
    {
        $errors = [];
        foreach ($data[$key] as $k => $v) {
            if (empty($v)) {
                $errors[$k] = 'Empty field';
            }
        }
        return $errors;
    }

    public function editProduct($data)
    {
        $errors = $this->validateData($data, 'product');
        if (empty($errors)) {
            $sql = " update product set name=?, price=? where id=?";
            $this->db->query($sql, [$data['product']['name'], (int) $data['product']['price'], (int) $data['id']]);
        }
        if (isset($data['product_discount'])) {
            $this->updateProductDiscount($data);
        }
    }

    public function updateProductDiscount($data)
    {
        $pd = isset($data['product_discount']) ? $data['product_discount'] : [];
        
        if (!empty($pd['items_count']) && !empty($pd['reduced_price'])) {
            $sql = "INSERT INTO product_discounts SET product_id = ?, items_count=?,reduced_price=? "
                    . "ON DUPLICATE KEY UPDATE items_count=?,reduced_price=? ";
            $this->db->exec($sql, [$data['id'], $pd['items_count'], $pd['reduced_price'], $pd['items_count'], $pd['reduced_price']]);
        } else {
            $sql = "DELETE  FROM product_discounts WHERE product_id=?";
            $this->db->exec($sql, [$data['id']]);
        }
    }

}
