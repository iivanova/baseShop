<?php

namespace App\Models;

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
        $sql = "SELECT p.*, ps.items_count, ps.reduced_price FROM {$this->tableName} p"
                . " LEFT JOIN product_discounts ps on ps.product_id = p.id WHERE p.id=?";

        $item = $this->db->query($sql, [$id]);
        return (isset($item[0])) ? $item[0] : [];
    }

    private function validateData($data, $keys)
    {
        $errors = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $keys) && empty($v)) {
                $errors[$k] = 'Empty field';
            }
        }
        return $errors;
    }

    public function addEditProduct($data)
    {
        $errors = $this->validateData($data, ['name', 'price']);
        if (empty($errors)) {
            $sql = "insert into product set name=?, price=? , id=? ON DUPLICATE KEY UPDATE name=?, price=?";
            $this->db->insert($sql, [$data['name'], (int) $data['price'], (int) $data['id'], $data['name'], (int) $data['price']]);

            if ($data['id'] == NULL)
                $data['id'] = $this->db->lastInsertId();
        }
        if($data['id'])
            $this->updateProductDiscount($data);
        return $data['id'];
    }

    public function updateProductDiscount($data)
    {
        if (!empty($data['items_count']) && !empty($data['reduced_price'])) {
            $sql = "INSERT INTO product_discounts SET product_id = ?, items_count=?,reduced_price=? "
                    . "ON DUPLICATE KEY UPDATE items_count=?,reduced_price=? ";
            $this->db->exec($sql, [$data['id'], $data['items_count'], $data['reduced_price'], $data['items_count'], $data['reduced_price']]);
        } else {
            $sql = "DELETE  FROM product_discounts WHERE product_id=?";
            $this->db->exec($sql, [$data['id']]);
        }
    }

    public function deleteProduct($id){

        $sql = "DELETE FROM product WHERE id =?";
        $this->db->exec($sql,[$id]);
    }
    public function deleteProductDiscount($id){

        $sql = "DELETE FROM product_discount WHERE product_id=?";
        $this->db->exec($sql,[$id]);
    }
    public function deleteProductFromCart($id){

        $sql = "DELETE FROM cart_products WHERE product_id=?";
        $this->db->exec($sql,[$id]);
    }
}
