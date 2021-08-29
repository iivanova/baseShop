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
