<?php

!defined('IN_SSY') && exit('Access Denied');

class base {

    var $time;//init_var()
	var $onlineip;//init_var()
    var $curl;
    
    var $input = array();

    function __construct() {
        $this->base();
    }

    function base() {
        $this->init_var();
        $this->init_curl();
    }
    
    function init_var() {
		$this->time = time();
		$cip = getenv('HTTP_CLIENT_IP');
		$xip = getenv('HTTP_X_FORWARDED_FOR');
		$rip = getenv('REMOTE_ADDR');
		$srip = $_SERVER['REMOTE_ADDR'];
		if($cip && strcasecmp($cip, 'unknown')) {
			$this->onlineip = $cip;
		} elseif($xip && strcasecmp($xip, 'unknown')) {
			$this->onlineip = $xip;
		} elseif($rip && strcasecmp($rip, 'unknown')) {
			$this->onlineip = $rip;
		} elseif($srip && strcasecmp($srip, 'unknown')) {
			$this->onlineip = $srip;
		}
		preg_match('~[\d\.]{7,15}~', $this->onlineip, $match);
		$this->onlineip = isset($match[0])&&$match[0] ? $match[0] : 'unknown';
    }
    /**
     * url参数解析与分离
     * @param string $getagent
     */
    function init_input($getagent = '') {
        $input = getgpc('input');
        if($input) {
            $input = $this->authcode($input);
            parse_str($input, $this->input);
            $this->input = daddslashes($this->input, 1, TRUE);
            $agent = $getagent ? $getagent : (empty($this->input['agent'])?NULL:$this->input['agent']);
    
            if($this->time - $this->input('time') > 36000) {
                exit('Authorization has expired');
            }
        }
        if(empty($this->input)) {
            exit('Invalid input');
        }
    }

    function input($k) {
        return isset($this->input[$k]) ? (is_array($this->input[$k]) ? $this->input[$k] : trim($this->input[$k])) : NULL;
    }
    
    /**
     * 初始化curl类
     */
    function init_curl() {
        require_once SSY_ROOT.'lib/curl.class.php';
        $this->curl = new ssy_curl();
    }
    
    function load($model, $base = NULL) {
        $base = $base ? $base : $this;
        if(empty($_ENV[$model])) {
            require_once SSY_ROOT."model/$model.php";            
            eval('$_ENV[$model] = new '.$model.'model($base);');
        }
        return $_ENV[$model];
    }
    
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    
        $ckey_length = 4;	// 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥
        if($operation === 'DECODE'){
            $string = str_replace('-','+',$string);
            $string = str_replace('_','/',$string);
        }
        $key = md5($key ? $key : SSY_KEY);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
    
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
    
        $result = '';
        $box = range(0, 255);
    
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
    
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
    
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
    
        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            $str = $keyc.str_replace('=', '', base64_encode($result));
            $str = str_replace('+','-',$str);
            $str = str_replace('/','_',$str);
            return $str;
        }
    
    }
    
    function onerror($code,$message){
        $this->onreturn('json',array('errcode'=>$code,'errmsg'=>$message));
    }
    
    function onreturn($type,$content){
        if($type==='json'){//返回json
            header('content-type:application/json;charset=utf8');
            $message=preg_replace("~\\\\u([0-9a-f]{4}+)~ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", json_encode($content));
            echo $message;
            exit();
        }
        if($type==='xml'){//返回xml
            
            exit();
        }
        exit($content);//直接返回
    }
}