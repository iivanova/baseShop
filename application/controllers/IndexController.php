<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
