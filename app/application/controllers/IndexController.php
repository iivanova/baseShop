<?php
namespace App\Controllers\IndexController;

class IndexController extends BaseController
{

    protected $cartModel;
    protected $productModel;
    public function __construct()
    {
        parent::__construct();
        $this->cartModel = $this->loadModel('Cart');
        $this->productModel = $this->loadModel('Product');
    }

    
    public function indexAction()
    {
        
        $this->view->products = $this->productModel->getAllItems();
    }


    public function user_loginAction()
    {
        echo "Please log in";
    }

}
