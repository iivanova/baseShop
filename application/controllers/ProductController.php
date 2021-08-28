<?php

class ProductController extends BaseController
{

    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
    }

    public function viewAction()
    {
        if (isset($_GET['id'])) {
            $this->product = $this->productModel->getProduct($_GET['id']);
        } else {
            $this->redirect('/');
        }
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

    public function user_loginAction()
    {
        echo "Please log in";
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

        $this->view->products = $this->cartModel->getCartItems($this->cartId);
        $this->view->cart_total = $this->calculateCartTotal();
    }

}
