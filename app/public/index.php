<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

define("APP_PATH", realpath(__DIR__ ."/.."));

include ("../vendor/autoload.php");

use App\Application;


//require_once '../application/Application.php';

$app = new Application();
$app->run();

exit;
