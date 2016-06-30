<?php

!defined('IN_SSY') && exit('Access Denied');

class ssy_curl {
    
    var $cookie_file='';
    //var $user_agent='Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36 Ssy/1.0';
    /**
     * 使用给定的url初始化一个curl句柄
     * @link
     * @param string $url   给定的url
     * @param string $post_fields   post参数
     * @return resource
     */
	 
	function __destruct()
	{
		file_exists($this->cookie_file) && unlink($this->cookie_file);
	}
    function ssy_curl_init($url,$post_fields='')
    {
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if(!empty($post_fields)){
		    curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);
		}
        return $ch;
    }
    
    /**
     * 完成一次http请求，返回页面内容
     * @param string $url   给定的url
     * @param string $post_fields   post参数
     * @param string $cookie   false不使用cookie,'set'保存cookie,'get'使用之前保存的cookie
     * @return mixed
     */
    function ssy_http_request($url,$post_fields='',$cookie=false)
    {
		if($cookie!==false&&$cookie==='set'){
		    $this->cookie_file = tempnam(SSY_DATADIR.'/tmp/cookie','cookie');
		}
		$ch=$this->ssy_curl_init($url,$post_fields);
        if($cookie=='set'){
            curl_setopt($ch,CURLOPT_COOKIEJAR,$this->cookie_file);
		}elseif ($cookie=='get'){
		    curl_setopt($ch,CURLOPT_COOKIEFILE,$this->cookie_file);
		}
		$content=curl_exec($ch);
		curl_close($ch);
		return $content;
	}
	
	/**
	 * 完成一次http并发请求，返回页面内容数组
	 * @param array $request_array 请求数组array(array('url'=>$url[1],'post_fields'=>$post_fields[1]),array('url'=>$url[2],'post_fields'=>$post_fields[2]),)
	 * @param string $cookie   false不使用cookie,'set'保存cookie,'get'使用之前保存的cookie
	 * @return Ambigous <boolean, string>
	 */
	function ssy_http_multi_request($request_array,$cookie=false)
	{
	    $mh = curl_multi_init(); // multi curl handler
	    $i = 0;
	    foreach($request_array as $value)
	    {
	        $handle[$i]=$this->ssy_curl_init($value['url'],$value['post_fields']);
	        if($cookie!==false){
	            curl_setopt($handle[$i],CURLOPT_COOKIEFILE,$this->cookie_file);
	        }
	        curl_multi_add_handle($mh, $handle[$i]); // 把 curl resource 放进 multi curl handler 里
	        $i++;
	    }
	    
	    $running=null;
	    do {// 执行批处理句柄
	        usleep(1000);
	        curl_multi_exec($mh,$running);
	    } while ($running > 0);
	    
	    foreach($handle as $i => $ch) {
	        $temp = curl_multi_getcontent($ch);//读取资料
	        $content[$i] = (curl_errno($ch) == 0) ? $temp : false;//移除 handle
	        curl_multi_remove_handle($mh, $ch);
	    }
	    curl_multi_close($mh);
	    return $content;
	}
}