<?php

!defined('IN_SSY') && exit('Access Denied');

class jwccontrol extends base {

	function __construct() {
		$this->jwccontrol();
	}

	function jwccontrol() {
		parent::__construct();
		$this->init_input();
		$this->load('jwc');
	}
	function onget_score(){
	    $student_num=$this->input('student_num');
	    $password=$this->input('password');
		$page_content=$this->input('page_content');
	    $_ENV['jwc']->login($student_num,$password);//登录
	    $result=$_ENV['jwc']->get_score_page();//获取页面
	    $result===-1 && $this->onerror('1000','学号或密码错误');
	    $result===-2 && $this->onerror('1001','教务处服务器忙');
	    $result!==0 && $this->onerror('1111','未定义错误：get_score');
		if($page_content==='raw')return $_ENV['jwc']->get_page();//返回原网页
		$score=$_ENV['jwc']->cutout_score();
		!is_array($score) && $this->onerror('1002','未出成绩');
	    $statistics=$_ENV['jwc']->cutout_credit_statistics();
		!is_array($statistics) && $this->onerror('1002','未出成绩');
		
	    $this->onreturn('json',$score+$statistics);
	}
	
	function onget_roll(){
	    $student_num=$this->input('student_num');
	    $password=$this->input('password');
	    $_ENV['jwc']->login($student_num,$password);//登录
	    $result=$_ENV['jwc']->get_cutout_roll();//获取页面
	    $result===-1 && $this->onerror('1000','学号或密码错误');
	    $result===-2 && $this->onerror('1001','教务处服务器忙');
	    !is_array($result) && $this->onerror('1111','未定义错误：get_roll');
	    $this->onreturn('json', $result);
	}

	function onget_opt_course(){
	    $student_num=$this->input('student_num');
	    $password=$this->input('password');
		$page_content=$this->input('page_content');
	    $_ENV['jwc']->login($student_num,$password);//登录
	    $result=$_ENV['jwc']->get_opt_course_page();//获取页面
	    $result===-1 && $this->onerror('1000','学号或密码错误');
	    $result===-2 && $this->onerror('1001','教务处服务器忙');
	    $result!==0 && $this->onerror('1111','未定义错误：get_score');
		if($page_content==='raw')return $_ENV['jwc']->get_page();//返回原网页
	    $content=$_ENV['jwc']->cutout_opt_course();
	    $content=$_ENV['jwc']->cutout_opt_schedule($content,$this->input('term'));
	    $this->onreturn('json', $content);
	}
	
	function onget_schedule(){
	    
	    $area=array('east','west');
	    foreach($area as $value){
	        //1、找到索引页面地址
    	    $page_url=$_ENV['jwc']->get_index_url('class',$value,$this->input('term'));
    	    $page_url===-1 && $this->onerror('1003','教室类型不存在');
    	    $page_url===-2 && $this->onerror('1004','校区不存在');
    	    //2、、在索引地址内搜索关键字，返回页面url
    	    $table_url=$_ENV['jwc']->get_table_url($page_url,$this->input('classname'));
    	    $table_url===-1 && $this->onerror('1005','不存在该学期');
    	    $table_url===-2 && $this->onerror('1001','教务处服务器忙');
    	    if($table_url===-3)continue;
            break;
	    }
	    $table_url===-3 && $this->onerror('1006','未找到指定教室');
	    //3、抓取页面url的页面内容
	    $result=$_ENV['jwc']->get_table_page($table_url);
	    $result!==0 && $this->onerror('1001','教务处服务器忙');
	    //4、
	    unset($result);
	    $result['info']=$_ENV['jwc']->cutout_table_info();
	    $result['info']===false && $this->onerror('1101','内容错误');
	    //5、
	    $result['content']=$_ENV['jwc']->cutout_table_content();
	    $result['content']===-1 && $this->onerror('1101','内容错误');
	    
	    //6、
	    $student_num=$this->input('student_num');
	    $password=$this->input('password');
	    
	    if(!empty($student_num)&&!empty($password)){
	        $_ENV['jwc']->login($student_num,$password);//登录
	        if($_ENV['jwc']->get_opt_course_page()===0){//获取页面
	            $content=$_ENV['jwc']->cutout_opt_course();
	            $result['opt']=$_ENV['jwc']->cutout_opt_schedule($content,$result['info']['term']);
	        }
	    }

	    $this->onreturn('json', $result);
	}
	
	function onget_classroom(){
	    $cw=$this->input('cw');
	    $week=$this->input('week');
	    $all=array('east'=>array(1,2,3,4,5,6),'west'=>array(1,2));
	    foreach ($all as $key=>$value){
    	   foreach ($value as $val){
    	       //1、
    	       $page_url=$_ENV['jwc']->get_index_url('occupy',$key,$this->input('term'));
    	       $page_url===-1 && $this->onerror('1003','教室类型不存在');
    	       $page_url===-2 && $this->onerror('1004','校区不存在');
    	       //2、
    	       $table_url=$_ENV['jwc']->get_classroom_url($page_url,$cw,$val);

    	       $table_url===-1 && $this->onerror('1005','不存在该学期');
    	       $table_url===-2 && $this->onerror('1001','教务处服务器忙');
    	       $table_url===-3 && $this->onerror('1007',"不存在第${week}周");
    	       
    	       //3、
    	       $result=$_ENV['jwc']->get_table_page($table_url);
    	       $result!==0 && $this->onerror('1001','教务处服务器忙');
    	       
    	       //4、
    	       $result=$classroom[$key][$val]=$_ENV['jwc']->cutout_classroom($week);
    	       $result===-1 && $this->onerror('1008','不存在该星期');
    	       $result===-2 && $this->onerror('1101','内容错误');
            }
	    }

	    unset($result);
	    $result['info']=$_ENV['jwc']->cutout_classroom_info();
	    $result['info']['week']=$week;
	    //$result['info']===false && $this->onerror('4001','内容错误');
	    //5、
	    $result['content']=$classroom;
	    $this->onreturn('json', $result);
	}
	
	function onget_classroom_details(){
	    //1、
	    $page_url=$_ENV['jwc']->get_index_url('every',$this->input('area'),$this->input('term'));
	    $page_url===-1 && $this->onerror('1003','教室类型不存在');
	    $page_url===-2 && $this->onerror('1004','校区不存在');
	    //2、
	    $table_url=$_ENV['jwc']->get_table_url($page_url,$this->input('classroom'));
	    
	    $table_url===-1 && $this->onerror('1005','不存在该学期');
	    $table_url===-2 && $this->onerror('1001','教务处服务器忙');
	    $table_url===-3 && $this->onerror('1006','未找到指定教室');
	    
	    //3、
	    $result=$_ENV['jwc']->get_table_page($table_url);
	    $result!==0 && $this->onerror('1001','教务处服务器忙');
	    //4、
	    unset($result);
	    $result['info']=$_ENV['jwc']->cutout_table_info();
	    $result['info']===false && $this->onerror('1101','内容错误');
	    //5、
	    $result['content']=$_ENV['jwc']->cutout_table_content();
	    $result['content']===-1 && $this->onerror('1101','内容错误');
	    
	    $this->onreturn('json', $result);
	}
	
	function onget_physical_test(){
		$student_num=$this->input('student_num');
	    $password=$this->input('password');
		$term=$this->input('term');
		
		$content=array('item'=>array(),'score'=>array());
		
	    $result=$_ENV['jwc']->physical_login($student_num,$password);//登录
		$result===-1 && $this->onerror('1021','帐号密码错误！');
		
		foreach($content as $key => $value){
			$result=$_ENV['jwc']->physical_get_page($key,$term);//获取页面
			if($result===-1){
				$content[$key]='查无此人';
			}else{
				$content[$key]=$_ENV['jwc']->physical_cutout_content();
			}
		}
	    $this->onreturn('json', $content);
	}
	
	function onproxy(){
		//原始链接重定向
		$option=getgpc('option');
	    $student_num=getgpc('student_num');
	    $password=getgpc('password');
		$url=getgpc('url');
		$input=getgpc('input');
		if(getgpc('redirect','G')==='redirect'){
			setcookie('ssy_token',getgpc('ssy_token'),time()+3600*24);
			header("Location: ?m=jwc&a=proxy&option=$option&student_num=$student_num&password=$password&url=$url&input=$input");
			exit;
		}
		switch ($option){
			case 'opt': 
				include_once(SSY_ROOT.'includes/optcourse.php');
			break;
			case 'pj':
				include_once(SSY_ROOT.'includes/evaluate.php');
			break;
			case 'url':
				header("Location: kn.php?url=$url");
			break;
			}
        exit;
	}
	
}