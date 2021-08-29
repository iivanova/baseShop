<?php

namespace App;

use App\Helpers\MySession;
use App\Helpers\MyLogger;

use App\Controllers\BaseController;
use App\Controllers\CartController;
use App\Controllers\IndexController;
use App\Controllers\ProductController;

/**
 * Description of Application
 *
 * @author ina
 */
class Application
{

    // ----------- REQUEST ITEMS ----------------
    private $_request_uri = '';
    private $_request_controller = '';
    private $_request_action = '';
    // ----------- CONFIG ITEMS -----------------
    private $_config = null;
    // ------------- VIEW DATA ------------------
    private $viewContent = "";
    private $view = null;
    private $Session = null;
    public static $staticSession = null;
    public static $scriptList = array();
    public static $styleList = array();

    private function createFromGlobal()
    {

        if (empty($_SERVER)) {
            return 'Invalid Request Data';
        }

        $GLOBALS['_request_method'] = '';
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $GLOBALS['_request_method'] = $_SERVER['REQUEST_METHOD'];
        }
        if ($GLOBALS['_request_method'] != 'GET' && $GLOBALS['_request_method'] != 'POST') {
            return 'Request method not supported';
        }
        $GLOBALS['_parameters'] = $_REQUEST;

        if (!empty($_SERVER['REQUEST_URI'])) {
            $this->_request_uri = trim($_SERVER['REQUEST_URI']);
            if (!empty($_SERVER['REDIRECT_BASE'])) {
                $this->_request_uri = substr($this->_request_uri, strlen($_SERVER['REDIRECT_BASE']));
            }

            $this->_request_uri = ltrim($this->_request_uri, '/');

            $url_parts = explode('/', $this->_request_uri);

            //call page functions
            $this->_request_controller = isset($url_parts[0]) ? $url_parts[0] : "";
            if (isset($url_parts[1])) {
                $url_action = explode('?', $url_parts[1]);
                $this->_request_action = $url_action[0];
            } else {
                $this->_request_action = '';
            }
            $GLOBALS['_request_view_path'] = $this->_request_controller;
            $GLOBALS['_request_view_file'] = $this->_request_action;
            $GLOBALS['_request_layout'] = "default";


            return true;
        } else {
            return "Request url too short";
        }
    }

    private function loadConfig()
    {
        $configFile = dirname(__FILE__) . "/application.ini";
        if (!file_exists($configFile)) {
            return "Missing config file";
        }

        $config = parse_ini_file($configFile, true);
        if ($config === FALSE) {
            return "Wrong config file";
        }

        return $config;
    }

    private function setSessionPath($path)
    {
        if (file_exists($path) && is_writable($path)) {
            session_save_path($path);
        }
    }

//    private function loadControllers()
//    {
//        include_once dirname(__FILE__) . "/controllers/BaseController.php";
//        foreach (glob(dirname(__FILE__) . "/controllers/*.php") as $filename) {
//            include_once $filename;
//        }
//    }
//
//    private function loadHelpers()
//    {
//        include_once dirname(__FILE__) . "/helpers/MyLogger.php";
//        include_once dirname(__FILE__) . "/helpers/MySession.php";
//    }
//
//    private function loadServices()
//    {
//        include dirname(__FILE__) . "/model/DBManager.php";
//        foreach (glob(dirname(__FILE__) . "/model/*Model.php") as $filename) {
//            include_once $filename;
//        }
//    }

    public function run()
    {

        try {
            $stat = $this->createFromGlobal();

            if ($stat === true) {

                $this->_config = $this->loadConfig();
                if (is_string($this->_config)) {
                    $this->makeError();
                    return;
                }
                if (!empty($this->_config["session_path"])) {
                    $this->setSessionPath($this->_config["session_path"]);
                }

                $GLOBALS["config"] = $this->_config;

                if (empty($this->_request_action) && empty($this->_request_controller)) {
                    $this->setDefaultRoute();
                }

//                $this->loadControllers();
//
//                $this->loadHelpers();
//                $this->loadServices();

                self::$staticSession = MySession::getInstance();
                $this->Session = self::$staticSession;

                $controllerName = 'App\\Controllers\\'.ucfirst($this->_request_controller) . "Controller";
                
//               var_dump($controllerName);
                $actionName = $this->_request_action . "Action";

                if (!class_exists('App\Controllers\IndexController')) {
                    $this->makeError("Not Found: The requested URL was not found on this server.", 404);
                    MyLogger::log('request controller doesn`t exist', MyLogger::ERROR);
                    return;
                }

                $controller = new $controllerName();
                if ($controller instanceof BaseController && method_exists($controller, $actionName)) {

                    $response = $controller->{$actionName}();

                    if (is_string($response)) {
                        $this->makeError($response, 500);
                        return;
                    } else {


                        $this->view = $this->htmlEscape($controller->getViewData());
                        $this->view->controllerName = strtolower($this->_request_controller);
                        $this->view->actionName = strtolower($this->_request_action);

                        $view_path = dirname(__FILE__) . "/view/" . $GLOBALS['_request_view_path'] . "/" . $GLOBALS['_request_view_file'] . ".phtml";

                        $this->viewContent = $this->render_view($view_path);

                        if (!empty($GLOBALS['_request_layout'])) {
                            $layout_path = dirname(__FILE__) . "/view/layout/" . $GLOBALS['_request_layout'] . ".phtml";

                            echo $this->render_view($layout_path);
                        } else {
                            echo $this->viewContent;
                        }
                    }
                } else {
                    $this->makeError("Not Found: The requested URL was not found on this server.", 404);
                    MyLogger::log('request action doesn`t exist', MyLogger::ERROR);
                    return;
                }
            } else {
                $this->makeError($stat);
            }
        } catch (Exception $ex) {
            $this->makeError($ex->getMessage());
        }
    }

    public function render($path, $parent = 'view')
    {

        $view_path = dirname(__FILE__) . "/{$parent}/" . $path;
        if (file_exists($view_path)) {
            return $this->render_view($view_path);
        }
        return "";
    }

    private function render_view($path)
    {
        ob_start();
        @include($path);
        $view = ob_get_contents();
        ob_end_clean();
        return $view;
    }


    private function setDefaultRoute()
    {
        if (!empty($this->_config["default_controller"]) && !empty($this->_config["default_method"])) {
            $this->_request_controller = $this->_config["default_controller"];
            $this->_request_action = $this->_config["default_method"];
            $GLOBALS['_request_view_path'] = $this->_request_controller;
            $GLOBALS['_request_view_file'] = $this->_request_action;
        }
    }

    private function makeError($error = "Internal Server Error", $code = 500)
    {

        if ($code == 404) {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        } else if ($code == 400) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
        } else {
            header("HTTP/1.1 500 Internal Server Error");
        }

        echo $error;
    }

    private function loadLanguagePack()
    {
        $langPath = dirname(__FILE__) . "/languages/" . self::$lang . ".json";
        if (file_exists($langPath)) {
            self::$languagePack = json_decode(file_get_contents($langPath), true);
        }
    }

    private function htmlEscape($data)
    {
        if (is_object($data)) {
            $newObj = new \stdClass();
            foreach ($data as $property => $value) {
                $newValue = $this->htmlEscape($value);
                $newObj->{$property} = $newValue;
            }
            return $newObj;
        } else if (is_array($data)) {
            $newArray = array();
            foreach ($data as $key => $value) {
                $newArray[$key] = $this->htmlEscape($value);
            }
            return $newArray;
        } else {
            return htmlentities($data);
        }
    }

}

function __($key)
{
    if (isset(Application::$languagePack[$key])) {
        return Application::$languagePack[$key];
    }
    return $key;
}
