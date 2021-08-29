<?php
namespace App\Helpers;

class MySession {

    // THE only instance of the class
    private static $instance = null;

    private function __construct() {
        
    }

    /**
     *    Returns THE instance of 'Session'.
     *    The session is automatically initialized if it wasn't.
     *    
     *    @return    object
     * */
    public static function getInstance() {
        
        if (self::$instance == null) {
            self::$instance = new self;
        }

        self::$instance->startSession();

        return self::$instance;
    }

    /**
     *    (Re)starts the session.
     *    
     *    @return    bool    TRUE if the session has been initialized, else FALSE.
     * */
    public function startSession() {
        if(!$this->sessionStarted()) { // session not started
            return session_start();
        } 
        
        return true;
    }

    public function sessionStarted() {
        if(session_id() == '') {
            return false;
        }
        
        return true;
    }
    
    /**
     *    Stores datas in the session.
     *    Example: $instance->foo = 'bar';
     *    
     *    @param    name    Name of the datas.
     *    @param    value    Your datas.
     *    @return    void
     * */
    public function set($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     *    Gets datas from the session.
     *    Example: echo $instance->foo;
     *    
     *    @param    name    Name of the datas to get.
     *    @return    mixed    Datas stored in session.
     * */
    public function get($name) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    }

    public function is_set($name) {
        return isset($_SESSION[$name]);
    }

    public function remove($name) {
        unset($_SESSION[$name]);
    }
    public function getSessionId() {
        return session_id();
    }
    
    /**
     *    Destroys the current session.
     *    
     *    @return    bool    TRUE is session has been deleted, else FALSE.
     * */
    public function destroy() {

        if ($this->startSession()){
            session_destroy();
            return true;
        }
        
        return true;
    }

}
