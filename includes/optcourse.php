<h1><center>页面正在努力加载中，请稍候。</center></h1>
<script>
	var xmlHttp;
	function showHint(url,param){
		xmlHttp=GetXmlHttpObject();
		if (xmlHttp==null){
			alert ("Browser does not support HTTP Request");
			return;
		} 
		xmlHttp.onreadystatechange=stateChanged;
		xmlHttp.open("POST",url,true);
		xmlHttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlHttp.send(param);
	} 

	function stateChanged(){ 
		if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete"){
			reg=/alert\('(.*?)'\)/;
			result=xmlHttp.responseText.match(reg);
			if(result!==null){
				alert(result[1]);
				window.location.replace("http://www.ccec-ssy.cn/ssy/index.php?m=schoolroll&a=binding&redirect_uri=%3fm%3doptcourse%26a%3dselect_optcourse%26redirect%3dredirect");
			}else{
				window.location.replace("kn.php?url=http://jw.sdibt.edu.cn/xfzxk/xsxkweb/addxk.asp");
			}
		} 
	}
	
	function GetXmlHttpObject()
	{
		var xmlHttp=null;
		try{// Firefox, Opera 8.0+, Safari
			xmlHttp=new XMLHttpRequest();
		}
		catch (e){// Internet Explorer
			try{
				xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch (e){
				xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
		return xmlHttp;
	}
	window.onload=function(){
		showHint('kn.php?url=http://jw.sdibt.edu.cn/xfzxk/xsxkweb/xkcheck.asp', 'xkyh=<?echo $student_num;?>&yhmm=<?echo $password;?>');
	}
</script>