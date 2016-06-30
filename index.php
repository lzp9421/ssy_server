<?php

//error_reporting(0);
ini_set("magic_quotes_runtime", 0);

$mtime = explode(' ', microtime());
$starttime = $mtime[1] + $mtime[0];

define('IN_SSY', TRUE);
define('SSY_ROOT', dirname(__FILE__).'/');
define('SSY_API', strtolower((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'))));
define('SSY_DATADIR', SSY_ROOT.'data/');
define('SSY_CONFIG', SSY_ROOT.'config/');
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

unset($GLOBALS, $_ENV, $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS);

$_GET		= daddslashes($_GET, 1, TRUE);
$_POST		= daddslashes($_POST, 1, TRUE);
$_COOKIE	= daddslashes($_COOKIE, 1, TRUE);
$_SERVER	= daddslashes($_SERVER);
$_FILES		= daddslashes($_FILES);
$_REQUEST	= daddslashes($_REQUEST, 1, TRUE);

if(!@include SSY_CONFIG.'config.inc.php') {
	exit;
}

$m = getgpc('m');
$a = getgpc('a');
if(empty($m) && empty($a)) {
	exit;
}

require SSY_ROOT.'model/base.php';

if(in_array($m, array('jwc'))) {

	include SSY_ROOT."control/$m.php";

	$classname = $m.'control';
	$control = new $classname();
	$method = 'on'.$a;
	if(method_exists($control, $method) && $a{0} != '_') {
		$data = $control->$method();
		echo is_array($data) ? $control->serialize($data, 1) : $data;
		exit;
	} elseif(method_exists($control, '_call')) {
		$data = $control->_call('on'.$a, '');
		echo is_array($data) ? $control->serialize($data, 1) : $data;
		exit;
	} else {
		exit('Action not found!');
	}

} else {
    
	exit('Module not found!');
	
}

$mtime = explode(' ', microtime());
$endtime = $mtime[1] + $mtime[0];

function daddslashes($string, $force = 0, $strip = FALSE) {
	if(!MAGIC_QUOTES_GPC || $force) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = daddslashes($val, $force, $strip);
			}
		} else {
			$string = addslashes($strip ? stripslashes($string) : $string);
		}
	}
	return $string;
}

function getgpc($k, $var='R') {
	switch($var) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	return isset($var[$k]) ? $var[$k] : NULL;
}