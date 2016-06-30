<?php

!defined('IN_SSY') && exit('Access Denied');


class jwcmodel {

    //var $db;
    var $curl;
    var $base;

    var $login_url='http://219.231.222.17/xfzxk/xsxkweb/xkcheck.asp';
    var $score_url='http://219.231.222.17/xfzxk/xsxkweb/xkcxcj.asp';
    var $roll_url='http://219.231.222.17/xfzxk/xsxkweb/xjcx.asp';
    var $opt_course_url='http://219.231.222.17/xfzxk/xsxkweb/xkcxxk.asp';
    var $term_url='http://219.231.222.17/xfzxk/wkb20041/kbd/title.htm';
    var $classroom_url='http://219.231.222.17/xfzxk/wkb20041/';
	
	var $physical_login_url='http://10.11.10.11/Login.asp';
	var $physical_item='http://10.11.10.11/Student/cjcx/student_tzcjtj.asp';
	var $physical_score='http://10.11.10.11/Student/cjcx/student_tzfstj.asp';
    
    var $page;
    
    function __construct(&$base) {
        $this->jwcmodel($base);
    }
	
    function jwcmodel(&$base) {
        $this->base = $base;
        //$this->db = $base->db;
        $this->curl = $base->curl;
    }
    
	function get_page(){
		return $this->page;
	}
	
    function login($student_num,$password){
        $this->curl->ssy_http_request($this->login_url,'xkyh='.$student_num.'&yhmm='.$password,'set');//获取到的数据相同，无论是否登录成功
    }
    
    function get_score_page(){
        $page=&$this->page;
        //必须先登录
        $page=$this->curl->ssy_http_request($this->score_url,NULL,'get');
        if(strstr($page,'<body><h1>Object Moved</h1>This object may be found <a HREF="login.asp">here</a>.</body>'))
            return -1;//账号密码错误
        if(preg_match('~<b class="unnamed1">(.+)</b>~',$page)!=1)
            return -2;//教务处服务器忙
        return 0;
    }
    
    function cutout_score($page=''){
        //成绩
        empty($page) && $page=&$this->page;
        $each_term_regexp='<td rowspan[^>]+>[\s]*(?<Term>[\S]*)[\s]*</td>[^<]+(?:(?:<td[^>]*>.*</td>[^<]*){5}</tr>[^<]*)+';//各学期
		$each_course_regexp='<td[^>]*>(?<Number>.*)</td>[^<]*<td[^>]*>(?<Name>.*)</td>[^<]*<td[^>]*>(?<Score>.*)</td>[^<]*<td[^>]*>(?<Credit>.*)</td>[^<]*<td[^>]*>(?<Type>.*)</td>[^<]*</tr>[^<]*';//每门课
		
		preg_match_all("~$each_term_regexp~ie",$page,$term);
		if(is_array($term['Term']))foreach($term['Term'] as $key=>$value)
		{
			preg_match_all("~$each_course_regexp~ie",$term[0][$key],$temp);
			if(is_array($temp['Number']))foreach($temp['Number'] as $k=>$val)
			{
				$result[$value][$k]['nu']=iconv('gbk','utf-8',$temp['Number'][$k]);//CourseNumber
				$result[$value][$k]['na']=iconv('gbk','utf-8',$temp['Name'][$k]);//CourseName
				$result[$value][$k]['sc']=iconv('gbk','utf-8',$temp['Score'][$k]);//Score
				$result[$value][$k]['cr']=iconv('gbk','utf-8',$temp['Credit'][$k]);//Credit
				$result[$value][$k]['ty']=iconv('gbk','utf-8',$temp['Type'][$k]);//CourseType
			}
		}
		return empty($result)?-1:$result;//-1成绩未出
    }  
    
    function cutout_credit_statistics($page=''){
		//学分统计
        empty($page) && $page=&$this->page;
		$statistics_regexp='<td[^>]*><table[^>]+>.+?<tr[^>]+>(.+?)</tr>[\s]*<tr[^>]+>(.+?)</tr>[^<]+</table></td>';
		$cell_regexp='<td[^>]*>(?:&nbsp;)?(.*?)(?:&nbsp;)?</td>';
		preg_match_all("~$statistics_regexp~is",$page,$temp);
		preg_match_all("~$cell_regexp~ies",$temp[1][0],$k);
		preg_match_all("~$cell_regexp~ies",$temp[2][0],$val);
		if(is_array($k[1]))
		    foreach($k[1] as $key=>$value)
    		{
    			$result['Statistics'][$key]['na']=trim(iconv('gbk','utf-8',$value));
    			$cell='sc';
    			($key===0||$key===1)&&$cell='ty';
    			$result['Statistics'][$key][$cell]=trim(iconv('gbk','utf-8',$val[1][$key]));
    		}
		return empty($result)?-1:$result;//-1成绩未出
    }
    
    function get_cutout_roll(){
        $page=&$this->page;
        //必须先登录
        $page=$this->curl->ssy_http_request($this->roll_url,NULL,'get');
        if(strstr($page,'<body><h1>Object Moved</h1>This object may be found <a HREF="login.asp">here</a>.</body>'))
            return -1;//账号密码错误
        if(preg_match_all('~<td align="center">(.+)</td>~',$page,$cell)!=6)
            return -2;//教务处服务器忙
        //学籍
        empty($page) && $page=&$this->page;
        preg_match('~<td colspan="5">(.+)</td>~',$page,$major);
        $arr=array('snu','sex','cna','sna','bth','dpt');//'StudentNumber','Sex','ClassName','Name','BirthDate','Department'
        for($i=0;$i<6;$i++)
        {
            $result[$arr[$i]]=trim(iconv('gbk','utf-8',$cell[1][$i]));
        }
        $result['maj']=trim(iconv('gbk','utf-8',$major[1]));//Major
        $result['pwd']=iconv('gbk','utf-8',$this->base->input('password'));//Password
        return $result;//-1成绩未出
    }
    
    function get_opt_course_page(){
        $page=&$this->page;
        //必须先登录
        $page=$this->curl->ssy_http_request($this->opt_course_url,NULL,'get');
        if(strstr($page,'<body><h1>Object Moved</h1>This object may be found <a HREF="login.asp">here</a>.</body>'))
            return -1;//账号密码错误
        if(!strstr($page,iconv('utf-8','gbk','山东工商学院')))
            return -2;//教务处服务器忙
        return 0;
    }
    
    function cutout_opt_course($page=''){
        //选修课
        empty($page) && $page=&$this->page;
        
        $term_regexp='<tr>\s*<td[^>]*rowspan[^>]*>\s?(.*?)\s?</td>';
        $cell_regexp='<td[^>]*>(?:&nbsp;)?(.*?)(?:&nbsp;)?</td>';
        
        $result=preg_split("~$term_regexp~i",$page,-1,PREG_SPLIT_DELIM_CAPTURE);
        unset($result[0]);
        if(!is_array($result)&&empty($result))return -1;//内容错误
        foreach($result as $key=>$value)
        {
            if(preg_match('~^\d\d-\d\d-[12]$~',$value))
            {
                $temp[$value]=explode('</tr>',$result[$key+1]);
                for($i=0;$i<count($temp[$value]);$i++)
                {
                    preg_match_all("~$cell_regexp~ies",$temp[$value][$i],$cell);
                    foreach ($cell[1] as $val){
                        $opt_course[$value][$i][]=trim(iconv('gbk','utf-8',$val));
                    }
                    if($i===count($temp[$value])-1)unset($opt_course[$value][$i]);
                }
            }
        }
        return empty($opt_course)?-1:$opt_course;//-1成绩未出
    }
    
    function cutout_opt_schedule($opt_course,$term){
        if(!isset($opt_course[$term]))return -1;
        $opt_course=$opt_course[$term];
        $opt_schedule=array();
        foreach($opt_course as $value)
        {
            if($value[2]!=='00')//选修课
            {
                $multi=explode(' ',$value[6]);
                foreach($multi as $k=>$val)
                {
                    if(preg_match('~(\d)(\d)([\dA])([DS]?)~',$val,$tmp))
                    {
                        $cell=$value[0].'（选修） '.$value[7].' '.$value[4].'-'.$value[5].'周'.($tmp[4]==='D'?'单周':'').' '.$value[1];
                        $opt_schedule[$tmp[1]][intval(($tmp[2]+1)/2)]=$cell;
                    }
                    else
                    {
                        $cell=$value[0].'（选修） '.$value[7].' '.$value[4].'-'.$value[5].'周'.(strstr($value[6],'D')?'单周':'').' '.$value[1];
                        $opt_schedule[$value[6]][]=$cell;
                    }
                }
            }
        }
        return $opt_schedule;
    }
    
    function get_current_term(){
        $page=$this->curl->ssy_http_request($this->term_url);
        $term_regexp='20(\d\d)\s*-\s*20(\d\d)\s*'.iconv('utf-8','gbk','年度第').'\s*([12])\s*';
        preg_match("~$term_regexp~", $page,$term);
        return empty($term)?false:"$term[1]-$term[2]-$term[3]";
    }
    
    //1、找到索引页面地址
    function get_index_url($type,$area,$term=''){
        //type=class|every|occupy;//area=east|west;//term=\d{4}[12]|NULL(current)
        if($type==='class')$type='/class.htm';
        elseif($type==='every')$type='/room0.htm';
        elseif($type==='occupy')$type='/room1.htm';
        else return -1;//教室类型不存在
        
        if($area==='east')$area='kbd';
        elseif($area==='west')$area='kbx';
        else return -2;//校区不存在
        
        $term=str_replace('-', '', $term);
        if(preg_match('~^\d{4}[12]$~', $term)===1&&$term!==str_replace('-', '', $this->get_current_term())){
            $url=$this->classroom_url.'bak/o'.$area.$term.$type;
        }
        else{
            $url=$this->classroom_url.$area.$type;
        }
        return $url;
    }
    
    //2、在索引地址内搜索关键字，返回页面url
    function get_table_url($page_url,$keyword){
        $page=$this->curl->ssy_http_request($page_url);
        if(!strstr($page,'<P><B><FONT color=#008080>'.iconv('utf-8','gbk','__查询'))){
            if(strstr($page,'<P><B><FONT color=#008080>'.iconv('utf-8','gbk','您正在搜索的页面可能已经删除、更名或暂时不可用。')))return -1;//学期不存在
            return -2;//教务处服务器忙
        }
        
        $url_regexp='<A HREF="([^"]+)"  target="_blank">\s*'.iconv('utf-8','gbk',$keyword).'\s*</A>';
        if(preg_match("~$url_regexp~i",$page,$url))
            return substr($page_url,0,strrpos($page_url,'/')+1).$url[1];
        else return -3;//未找到关键字,指定教室不存在
    
    }
    //2、在索引地址内搜索关键字，返回教室占用情况url
    function get_classroom_url($page_url,$week,$building){
        $classroom_url=$this->get_table_url($page_url,"第 $week 周");
        if(!is_string($classroom_url))return $classroom_url;
        return substr($classroom_url,0,strrpos($classroom_url,'.htm')-1).$building.'.htm';
    }
    
    //3、抓取页面url的页面内容
    function get_table_page($table_url){
        $page=&$this->page;
        $page=$this->curl->ssy_http_request($table_url);
        if(!strstr($page,iconv('utf-8','gbk','山东工商学院')))
            return -1;//教务处服务器忙
        return 0;
    }

    //4、抠出表格信息
    function cutout_table_info($page=''){
        //课表信息
        empty($page) && $page=&$this->page;
        $info_regexp='<P><FONT color=#008080>(.*?)</Font></P>';//
        $term_regexp='20(\d\d)\s*-\s*20(\d\d)\s*'.iconv('utf-8','gbk','年度第').'\s*([12])\s*';
        
        preg_match("~$info_regexp~ies",$page,$info);
        $info=explode(iconv('utf-8','gbk','　'), $info[1]);
        if(count($info)!==3)return false;//内容错误
        for($i=0;$i<3;$i++){
            $temp=explode(':',$info[$i]);
            $key=trim(iconv('gbk','utf-8',$temp[0]));
            $value=trim(iconv('gbk','utf-8',$temp[1]));
            $result[$key]=$value;
        }
        preg_match("~$term_regexp~ies",$page,$info);
        $result['term']="$info[1]-$info[2]-$info[3]";
        return $result;
    }
    function cutout_classroom_info($page=''){
        //教室信息
        empty($page) && $page=&$this->page;
        $cw_regexp=iconv('utf-8','gbk','<P><FONT color=#008080>第\s*(\d{1,2})\s*周');//
        $term_regexp='20(\d\d)\s*-\s*20(\d\d)\s*'.iconv('utf-8','gbk','年度第').'\s*([12])\s*';
        if(!preg_match("~$cw_regexp~ies",$page,$info))return false;
        $result['cw']=$info[1];
        if(!preg_match("~$term_regexp~ies",$page,$info))return false;
        $result['term']="$info[1]-$info[2]-$info[3]";
        return $result;
    }
    //4、抠出表格内容
    function cutout_table_content($page=''){
        //课表内容
        empty($page) && $page=&$this->page;
        $content_regexp='<TD WIDTH=\d+ VALIGN="top">(.+?)</TD>';//
        preg_match_all("~$content_regexp~ies",$page,$cell);
        if(!is_array($cell[1])||count($cell[1])!==35)return -1;//内容错误
        for($i=0;$i<35;$i++)
        {
            $text=iconv('gbk','utf-8',strip_tags($cell[1][$i]));

            $order = array("\r\n", "\n", "\r",'　');            
            $text=str_replace($order,' ',$text);
            preg_replace('~\s\s+~', ' ', $text);
            $text=trim($text);
            $content[$i%7+1][intval($i/7)+1]=$text;
        }
        return $content;//-1成绩未出
    }
    
    //抠出教室内容
    /**
     * 从html页面中抠出教室信息
     * @param string $page 页面
     * @return number(错误)|array(成功) 
     */
    function cutout_classroom($week,$page=''){
        //空教室
        if($week>7||$week<1)return -1;
        empty($page) && $page=&$this->page;
        $row_regexp='<TR>.+?</TR>';
        $cell_regexp='<TD[^>]*>.*?</TD>';
        
        preg_match_all("~$row_regexp~ies",$page,$row);
        if(!is_array($row[0]))return -2;//内容错误
        
        foreach ($row[0] as $value){
            preg_match_all("~$cell_regexp~ies",$value,$cell);
            if(!is_array($cell[0])||count($cell[0])!==38)return -2;//内容错误
            $classroom_type='';
            $classroom_num='';
            $classroom_volume='';
            for($i=0;$i<38;$i++){
                $temp=trim(iconv('gbk','utf-8',strip_tags($cell[0][$i])),"\0\t\n\x0B\r 　");
                if($i<3){
                    $i===0&&$classroom_type=$temp;
                    $i===1&&$classroom_num=$temp;
                    $i===2&&$classroom_volume=$temp;
                    continue;
                }
                if(intval(($i-3)/5)+1!==intval($week))continue;
                $classroom[$classroom_num][intval(($i-3)/5)+1][($i-3)%5+1]=$temp;
            }
            $classroom[$classroom_num]['type']=$classroom_type;
            $classroom[$classroom_num]['vol']=$classroom_volume;
        }
        return $classroom;
    }
    
	function physical_login($student_num,$password){
		$page = $this->curl->ssy_http_request($this->physical_login_url,'admin_id='.$student_num.'&admin_psw='.$password.'&R1=T_Student_Info','set');//获取到的数据相同，无论是否登录成功
		if(strstr($page,iconv('utf-8','gbk','alert("登陆失败')))return -1;
		return 0;
	}
	
	function physical_get_page($type='item',$term=null){
		$page=&$this->page;
		$term_array=explode('-',$term,3);
		if(count($term_array)===3){
			$term_array[2] = $term_array[2]=='1'?'一':'二';
			$term = iconv('utf-8','gbk',"20{$term_array[0]}-20{$term_array[1]}第{$term_array[2]}学期");
		}else{
			$term = null;
		}
		$page=$this->curl->ssy_http_request($type==='item'?$this->physical_item:$this->physical_score,empty($term)?NULL:"news_text1=$term",'get');
		//echo $page;
		if(strstr($page,iconv('utf-8','gbk','alert("对不起，查无此人,请重选！")')))return -1;
		
		$table_regexp='<table[^>]*>\s*<tr>.*?</tr>\s*<tr>.*?</tr>\s*</table>';
		preg_match_all("~$table_regexp~ies",$page,$table);
		$page = $table[0][1];
		return 0;
		//echo $page;
	}
	
	function physical_cutout_content($page=''){
		empty($page) && $page=&$this->page;
        $td_regexp='<td[^>]*>.*?</(?:td|p)>';//
		$content=array();
		list($key_info,$val_info) = explode('</tr>',$page,2);
		
		preg_match_all("~$td_regexp~ies",$key_info,$key_array);
		preg_match_all("~$td_regexp~ies",$val_info,$val_array);

		foreach($key_array[0] as $key => $value){
			$content_k=strip_tags($key_array[0][$key]);
			$content_k=str_replace('&nbsp;','',$content_k);
			$content_k=iconv('gbk','utf-8',trim($content_k));
			
			$content_v=strip_tags($val_array[0][$key]);
			$content_v=str_replace('&nbsp;','',$content_v);
			$content_v=iconv('gbk','utf-8',trim($content_v));
			
			$content[$content_k]=$content_v;
		}
		return $content;
	}

}