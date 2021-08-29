<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ProductController extends BaseController
{

    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
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

    public function add_editAction()
    {

        if (!empty($_POST['data'])) {

            $data = $_POST['data'];
            $data['id'] = isset($_GET['id']) ? $_GET['id'] : NULL;
            $id = $this->productModel->addEditProduct($data);
            if ($id && !isset($_GET['id'])) {
                $this->redirect('/product/add_edit?id=' . $id);
            }
        }
        if (isset($_GET['id'])) {

            $this->view->product = $this->productModel->getProduct((int) $_GET['id']);
            if (empty($this->view->product)) {
                $this->redirect('/product/add_edit');
            }
        }
    }

    public function deleteAction()
    {
        $id = (int) $_GET['id'];
        if ($id) {
            $this->productModel->deleteProduct($id);
            $this->productModel->deleteProductDiscount($id);
            $this->productModel->deleteProductFromCart($id);
            
            $this->redirect('/');
        }
    }

}
