<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class TestController extends BaseController
{
	public function __construct()
    {
        
        parent::__construct();
        
    }
	
	public function alltestsAction()
    {
		$phpunitDir = dirname(__FILE__) . "/../Tests/";
		$output = shell_exec($phpunitDir . "/phpunit.phar --do-not-cache-result " . $phpunitDir);
		echo "<pre>$output</pre>";
		exit;
	}
}