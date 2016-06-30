<?php
require_once('conf.php');
require_once('includes/module_parser.php');
require_once('includes/module_url.php');
require_once('includes/module_http.php');
require_once('includes/general_functions.php');
if(!isset($_COOKIE['ssy_token'])||$_COOKIE['ssy_token']!=='gvhtby3nkiolyte5rewvbmkpo8iuytvrfed')exit;
	
function extractSettings($settingName,$default = null){
	if(isset($_COOKIE["knp_settings"])){
		$v = explode(",",$_COOKIE["knp_settings"]);
		foreach($v as $s){
			$sk = explode("=",$s);
			if(count($sk) > 1){
				if($sk[0] == $settingName)
					return $sk[1];
			}
		}
	}
	return $default;
}

function commitSettings($key, $value){
	if(isset($_COOKIE["knp_settings"]))
		$v = explode(",",$_COOKIE["knp_settings"]);
	else
		$v = array();
	$settings = array();
	$found = false;
	foreach($v as $s){
		$sk = explode("=",$s);
		if(count($sk) > 1){
				if($sk[0] == $key){
				$sk[1] = $value;
				$found = true;
			}
				$settings[] = $sk[0] . "=" . $sk[1];
		}
	}
	if(!$found){
		$settings[] = $key . "=" . $value;
	}
	$_COOKIE["knp_settings"] = implode(",",$settings);
	return $_COOKIE["knp_settings"];
}

if(!isset($_GET['url']) || $_GET['url']==''){
	exit();
}
$url = $_GET['url'];
$url = checkHttpUrl($url);
if($url == "http://" || $url == "https://"){
	header('Location: frames/dynami_index.php');
	exit();
}
/** Get the Scripts URL **/
$_HOST = 'http://' . $_SERVER['HTTP_HOST'];
$_SCRIPT =$_HOST . $_SERVER['SCRIPT_NAME'];
/** Create the modules **/
$knURL = new knUrl();
$knURL->setBaseurl($url);
$knHTTP = new knHttp($url);

/** Init them **/
if(isset($_POST['knproxy_gettopost']) && $_POST['knproxy_gettopost']=='true'){
	unset($_POST['knproxy_gettopost']);
	$knHTTP->set_get($_POST);
}else
	$knHTTP->set_post($_POST);
$knHTTP->set_cookies($_COOKIE);
if(!empty($_POST['knUSER']) || isset($_COOKIE['__knLogin'])){
	if($_POST['knUSER']==''){
		$pas=explode('/',$url);
		unset($pas[count($pas)-1]);
		$url_base = strtolower(implode('/',$pas));
		$a=explode('|',$_COOKIE['__knLogin']);
		foreach($a as $vak){
			$uK = explode('#',$vak);
			if($uK[0]!='' && strpos($url_base,$uK[0])===0){
				$knHTTP->httpauth=$uK[1];
				break;
			}
		}
	}else{
		$knHTTP->set_http_creds($_POST['knUSER'],$_POST['knPASS']);
		$pas=explode('/',$url);
		unset($pas[count($pas)-1]);
		$url_base = strtolower(implode('/',$pas));
		$a=explode('|',$_COOKIE['__knLogin']);
		$a[]=$url_base . '#' . $knHTTP->httpauth;
		setcookie('__knLogin',implode('|',$a),2147364748);
	}
}

/** See if we should give out an HTTPS warning **/
if(defined('KNPROXY_HTTPS_WARNING') && KNPROXY_HTTPS_WARNING != 'off'){
	if(!preg_match("~(\.js|\.css)$~iUs",$knURL->base["PATH"])){
		if($knHTTP->is_https && extractSettings("https_warning","on") != "off" && !isset($_POST['yes'])){
			include_once('includes/gui_notice.php');exit();
		}elseif($knHTTP->is_https && isset($_POST['yes'])){
			setcookie("knp_settings",commitSettings("https_warning","off"),2147480000);
		}
	}
}

/** Handle Cached Requests **/
if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
	$knHTTP->set_request_headers(Array('If-Modified-Since',$_SERVER['HTTP_IF_MODIFIED_SINCE']));
if(isset($_SERVER['HTTP_IF_MATCH']))
	$knHTTP->set_request_headers(Array('If-Match',$_SERVER['HTTP_IF_MATCH']));
if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
	$knHTTP->set_request_headers(Array('If-None-Match',$_SERVER['HTTP_IF_NONE_MATCH']));
if(isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']))
	$knHTTP->set_request_headers(Array('If-Unmodified-Since',$_SERVER['HTTP_IF_UNMODIFIED_SINCE']));
if(isset($_SERVER['HTTP_IF_RANGE']))
	$knHTTP->set_request_headers(Array('If-Range',$_SERVER['HTTP_IF_RANGE']));
/** HTTP Ranges **/
if(isset($_SERVER['HTTP_RANGE']))
	$knHTTP->set_request_headers(Array('Range',$_SERVER['RANGE']));
/** Send And Load **/
$knHTTP->send();
$headers = $knHTTP->refined_headers();
/** Do some fancy stuff with the headers **/
if(!isset($headers["HTTP_RESPONSE"])){
	$eobj = Array("status"=> 1014);
	include("includes/gui_error.php");
	exit();
}
if($headers['HTTP_RESPONSE']==401){
	//UNAUTHORIZED
	$realm = $headers['WWW_AUTHENTICATE_REALM'];
	include('includes/gui_httpauth.php');
	exit();
}
/** Await Authentication **/
header('HTTP/1.1 ' . $headers['HTTP_RESPONSE'] . ' Omitted');
if(((int)$headers['HTTP_RESPONSE']>=400 && $headers['HTTP_RESPONSE']!=404) || (int)$headers['HTTP_RESPONSE']<1){
	$eobj=Array('status'=>$headers['HTTP_RESPONSE']);
	include('includes/gui_error.php');
	exit();
}
/** Load The Page **/
header('Content-Type: ' . $knHTTP->doctype);
/** Need Redirection? **/
if(isset($headers['KNPROXY_LOCATION']) && $headers['KNPROXY_LOCATION']!=""){
	header('Location: ' . basename(__FILE__) . "?url=" . $headers['KNPROXY_LOCATION']);
	exit();
}
if(isset($headers['HTTP_LOCATION']) && $headers['HTTP_LOCATION']!=''){
	$url = $knURL->getAbsolute($headers['HTTP_LOCATION']);
	$knurl = $url;
	$nURL = basename(__FILE__) . "?url=" . $knurl;
	header('Location: ' . $nURL );
}
/** Downloads And Filename **/
if(isset($headers['CONTENT_DISPOSITION']) && $headers['CONTENT_DISPOSITION']!='')
	header('Content-Disposition: ' . $headers['CONTENT_DISPOSITION']);
/** Cache Stuff **/
if(!empty($headers['CACHE_CONTROL']))
	header('Cache-Control: ' . $headers['CACHE_CONTROL']);
if(!empty($headers['EXPIRES']))
	header('Expires: ' . $headers['EXPIRES']);
if(!defined('KNPROXY_CACHE_MODE') || KNPROXY_CACHE_MODE=="none"){
	if(!empty($headers['LAST_MODIFIED']))
		header('Last-Modified: ' . $headers['LAST_MODIFIED']);
	if(defined('KNPROXY_ETAG'))
		switch(KNPROXY_ETAG){
			case 'forward':if(isset($headers['ETAG']) && !empty($headers['ETAG'])){header('ETag: ' . $headers['ETAG']);};break;
			case 'generate':{
				if(isset($headers['ETAG']) && !empty($headers['ETAG'])){
					header('ETag: ' . $headers['ETAG']);
				}else{
					header('ETag: "' . md5($knHTTP->content) . '"');
				}
			}break;
			case 'suppress':{
				header('ETag: ');//Suppressed
			}
		}
}
/** Do a Range Check **/
if(!empty($headers['ACCEPT_RANGES']))
	header('Accept-Ranges: ' . $headers['ACCEPT_RANGES']);
if(!empty($headers['CONTENT_RANGE']))
	header('Content-Range: ' . $headers['CONTENT_RANGE']);
/** Http Refresh Headers **/
if(isset($headers['HTTP_REFRESH'])){
	$pre=basename(__FILE__) . '?url=';
	header('refresh:'.(int)$headers['refresh'][0].';url='. $pre . $knURL->getAbsolute($headers['refresh'][1]));
}

if(isset($headers['HTTP_COOKIES']) && is_array($headers['HTTP_COOKIES']))
	foreach($headers['HTTP_COOKIES'] as $cookie){
		if($cookie[0] == "knp_settings" || $cookie[0] == "knp_login")
			continue;
		if($cookie[2]!=''){
			$expires = strtotime($cookie[2]);
			setcookie($cookie[0],$cookie[1],$expires);
		}else{
			setcookie($cookie[0],$cookie[1]);//Session cookie
		}

	}
/** Parsing Process **/
$knParser = new knParser($knURL,$knHTTP->content,$_SCRIPT . '?url=');
$knParser->setMimeType($knHTTP->doctype);
$knParser->setCharset($knHTTP->doctype,$knHTTP->content);
if(defined('KNPROXY_ENCRYPT_PAGE') && KNPROXY_ENCRYPT_PAGE=='true'){
	if($knParser->type=='text/html' || $knParser->type==''){
		$t = '<script language="javascript" type="text/javascript" src="js/denjihou.js"></script>';
		$t.= '<script language="javascript" type="text/javascript">';
		$knParser->set_value('use_page_encryption',true);
		$knParser->parse();
		$key = $knParser->get_value('key','');
		$t.= 'knEncode.setxmkey("' . $key . '");' . "\n";
		$t.= 'knEncode.charset="'. $knParser->charset .'";' . "\n";
		$t.= 'var page = knEncode.decode("' . $knParser->output . '");' . "\n";
		$t.= 'document.write(page);' . "\n";
		$t.= '</script>';
		if(defined('KNPROXY_USE_GZIP') && KNPROXY_USE_GZIP == 'true' && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('ob_gzhandler')){
			ob_start("ob_gzhandler");
			echo $t;
		}else{
			echo $t;
		}
		exit();
	}
}
$knParser->parse();
if(defined('KNPROXY_USE_GZIP') && KNPROXY_USE_GZIP == 'true' && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('ob_gzhandler')){
	if(substr($knParser->type,0,5)=='text/'){
		ob_start("ob_gzhandler");
		echo $knParser->output;
	}else
		echo $knParser->output;
}else
	echo $knParser->output;
?>
