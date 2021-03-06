<?php
namespace App\Models;

class CartModel
{

    protected $tableName = 'cart';
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getCartItems($cartId)
    {
        $items = $this->getCartItemsAndPromotions($cartId);

        if(!empty($items))
        {
            $total = $this->calculateTotal($cartId, $items);
        }

        return $items;
    }

    public function calculateTotal($cartId, &$items = [])
    {
        if(empty($items)){
            $items = $this->getCartItemsAndPromotions($cartId);
        }
        $total = 0;
        foreach ($items as $k=>$item) {
            
            if (isset($item['reduced_price']) && $item['reduced_price']) {
                $subtotal = floor($item['quantity'] / $item['items_count']) * $item['reduced_price'] + ($item['quantity'] % $item['items_count']) * $item['price'];
            } else {
                $subtotal = $item['quantity'] * $item['price'];
            }
            $items[$k]['item_total_sum'] = $subtotal;
            $total += $subtotal;
        }
        $this->updateCartTotal($cartId, $total);
        return $total;
    }
    
    public function initCart()
    {
        $sql = "INSERT INTO {$this->tableName} (`id`, `date_created`) VALUES (NULL, current_timestamp()) ";
        return $this->db->insert($sql);
    }

    public function getCartItemsAndPromotions($cartId)
    {
        $sql = "SELECT p.id, p.name, p.price, cp.quantity, pd.items_count, pd.reduced_price "
                . "FROM cart_products cp"
                . " LEFT JOIN product p on p.id=cp.product_id "
                . " LEFT JOIN product_discounts pd on pd.product_id=p.id "
                . " WHERE cp.cart_id=?";

        $items = $this->db->query($sql, [$cartId]);
        return $items;
    }


    private function updateCartTotal($cartId, $total){
        $sql =" UPDATE cart SET sum_total=? where id=?";
        $this->db->exec($sql,[$total,$cartId]);
    } 
    public function addToCart($data)
    {
        if (isset($data['productId'], $data['cartId'])) {
            $sql = "INSERT INTO cart_products (cart_id, product_id, quantity) VALUES (?,?,1)
                ON DUPLICATE KEY UPDATE quantity = quantity+1";
            $this->db->exec($sql, [$data['cartId'], $data['productId']]);
        }
        return;
    }

    public function removeFromCart($data)
    {

        $product = $this->getCartProduct($data);
        if (isset($product[0]['quantity']) && $product[0]['quantity'] > 1) {
            $sql = "UPDATE cart_products SET quantity = quantity-1 WHERE cart_id=? AND product_id = ? ";
        } else {
            $sql = "DELETE FROM cart_products WHERE cart_id=? AND product_id = ?";
        }
        $this->db->exec($sql, [$data['cartId'], $data['productId']]);
    }

    public function getCartProduct($data)
    {
        $sql = "SELECT * FROM cart_products WHERE cart_id=? AND product_id=?";
        return $this->db->query($sql, [$data['cartId'], $data['productId']]);
    }

    public function updateCartStatus($cartId, $status)
    {
        $sql = ' UPDATE cart set `status`=? WHERE id=?';
        $this->db->exec($sql, [$status, $cartId]);
    }

}
