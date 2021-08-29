<?php
namespace App\Controllers\BaseController;

abstract class BaseController
{

    protected $_config_properties = null;
    protected $_request_method = '';
    protected $_parameters = array();
    public $db;
    public $view = null;
    public $models = [];
    protected $Session = null;

    public function __construct()
    {

        if (!empty($GLOBALS["config"])) {
            $this->_config_properties = $GLOBALS["config"];
        }
        if (!empty($GLOBALS["_parameters"])) {
            $this->_parameters = $GLOBALS["_parameters"];
        }
        if (!empty($GLOBALS["_request_method"])) {
            $this->_request_method = $GLOBALS['_request_method'];
        }

        $this->db = new DBManager([
            'db_username' => $GLOBALS['config']['db_username'],
            'db_password' => $GLOBALS['config']['db_password'],
            'db_pdo' => $GLOBALS['config']['db_pdo'],
        ]);

        $this->Session = Application::$staticSession;
        $this->view = new stdClass();
        $this->view->pageTitle = '';
        $this->view->faviconPath = '';
    }

    public function loadModel($modelName)
    {

        if (isset($this->models[$modelName]))
            return $this->models[$modelName];

        $model = $modelName . 'Model';
        $this->models[$modelName] = new $model($this->db);

        return $this->models[$modelName];
    }

    public function getViewData()
    {
        if (isset($this->view)) {
            return $this->view;
        }
        return null;
    }

    protected function setViewRender($view_render)
    {
        if (is_string($view_render)) {
            $GLOBALS['_request_view_path'] = "";
            $GLOBALS['_request_view_file'] = $view_render;
            return true;
        }
        return false;
    }

    protected function setLayout($name)
    {
        $layout_path = dirname(__FILE__) . "../layout/" . $name . ".phtml";
        if (file_exists($layout_path)) {
            $GLOBALS['_request_layout'] = $name;
            return true;
        } else {
            AMSLogger::log("Set undefined layout");
        }
        return false;
    }

    protected function redirect($url)
    {

        if (is_string($url)) {
            $base_url = $this->getHostAddress();
            if (strpos($url, 'http') === 0) { //full url
                header("Location: " . $url, true, 302);
            } else { //relative url
                header("Location: " . $base_url . $url, true, 302);
            }

            exit();
        }

        return false;
    }

    protected function getHostAddress()
    {

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return $protocol . $_SERVER['HTTP_HOST'];
    }

    public function getSession()
    {
        return $this->Session;
    }

}
