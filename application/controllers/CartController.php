<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CartController extends BaseController
{

    const CART_KEY = 'cart_id';
    const CART_TOTAL = 'cart_total';

    private $cartModel;
    private $productModel;
    private $cartId;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = $this->loadModel('Cart');
        $this->productModel = $this->loadModel('Product');
        $this->getCart();
    }

    public function indexAction()
    {
        $this->view->products = $this->cartModel->getCartItems($this->cartId);
        $this->view->cart_total = $this->calculateCartTotal();
//        $this->requireLogin();    
    }

    public function getCart()
    {

        $this->cartId = $this->getCartFromSession();

        if (!$this->cartId) {
            $this->cartId = $this->cartModel->initCart();
            $this->Session->set(self::CART_KEY, $this->cartId);
        }
    }

    public function calculateCartTotal()
    {

        $total = $this->cartModel->calculateTotal($this->cartId);
        $this->Session->set(self::CART_TOTAL, $total);
        return $total;
    }

    public function getCartFromSession()
    {
        return $this->Session->get(self::CART_KEY);
    }

    public function add_productAction()
    {

        $data = $_POST;
        $data['cartId'] = $this->cartId;
        $this->cartModel->addToCart($data);
        exit($this->calculateCartTotal());
    }

    public function remove_productAction()
    {

        $data = $_POST;
        $data['cartId'] = $this->cartId;
        $this->cartModel->removeFromCart($data);
        exit($this->calculateCartTotal());
    }

    public function checkoutAction()
    {
        if (isset($_GET['finish_checkout']) && $_GET['finish_checkout'] == 1) {
            $this->cartModel->updateCartStatus($this->cartId, 1);
            $this->Session->destroy();
            $this->view->message = "Thank you for your purchase";
        }

        $this->view->products = $this->cartModel->getCartItems($this->cartId);
        $this->view->cart_total = $this->calculateCartTotal();
    
        
    }

}
