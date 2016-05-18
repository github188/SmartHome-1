<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1,target-densitydpi=medium-dpi">
<style>
    html {
        -webkit-user-select: none;
    }
</style>
<!-- 样式控制 -->
<script type="text/javascript" src="http://www.francescomalagrino.com/BootstrapPageGenerator/3/js/jquery-2.0.0.min.js"></script>
<script type="text/javascript" src="http://www.francescomalagrino.com/BootstrapPageGenerator/3/js/jquery-ui"></script>
<link href="http://www.francescomalagrino.com/BootstrapPageGenerator/3/css/bootstrap-combined.min.css" rel="stylesheet" media="screen">
<script type="text/javascript" src="http://www.francescomalagrino.com/BootstrapPageGenerator/3/js/bootstrap.min.js"></script>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>send and receive</title>
  <script type="text/javascript">
  //WebSocket = null;
  </script>
  <!-- Include these three JS files: -->
  <script type="text/javascript" src="/js/swfobject.js"></script>
  <script type="text/javascript" src="/js/web_socket.js"></script>
  <script type="text/javascript" src="/js/jquery.min.js"></script>

  <script type="text/javascript">
    if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
    WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    WEB_SOCKET_DEBUG = true;
    var ws, name, client_list={};
    //是否已经过去一秒，默认为false
    window.gone_one_second = false;
    // 连接服务端
    function connect() {
       // 创建websocket
       ws = new WebSocket("ws://"+document.domain+":7272");
       // 当socket连接打开时，输入用户名
       ws.onopen = onopen;
       // 当有消息时根据消息类型显示不同信息
       ws.onmessage = onmessage; 
       ws.onclose = function() {
    	  console.log("连接关闭，定时重连");
          connect();
       };
       ws.onerror = function() {
     	  console.log("出现错误");
       };
    }

    // 连接建立时发送登录信息
    function onopen()
    {
        if(!name)
        {
            show_prompt();
        }
        // 登录
        var login_data = '{"type":"login","client_name":"'+name.replace(/"/g, '\\"')+'","room_id":"<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>"}';
        console.log("websocket握手成功，发送登录数据:"+login_data);
        ws.send(login_data);
    }

    // 服务端发来消息时
    function onmessage(e)
    {
        console.log(e.data);
        var data = eval("("+e.data+")");
        switch(data['type']){
            case 'bmp':
                //$("#bmp_area").append('<div class="speech_item">'+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>');
                //$("#bmp_area").append('<img src=./bmp/'+data['file_name']+'/><br/>');
                add_bmp(data['file_name']);
                break;
            // 服务端ping客户端
            case 'ping':
                ws.send('{"type":"pong"}');
                break;;
            // 登录 更新用户列表
            case 'login':
                //{"type":"login","client_id":xxx,"client_name":"xxx","client_list":"[...]","time":"xxx"}
                say(data['client_id'], data['client_name'],  data['client_name']+' 登录了系统', data['time']);
                if(data['client_list'])
                {
                    client_list = data['client_list'];
                }
                else
                {
                    client_list[data['client_id']] = data['client_name']; 
                }
                flush_client_list();
                console.log(data['client_name']+"登录成功");
                break;
            // 发言
            case 'say':
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                break;
            // 用户退出 更新用户列表
            case 'logout':
                //{"type":"logout","client_id":xxx,"time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' 退出了', data['time']);
                delete client_list[data['from_client_id']];
                flush_client_list();
        }
    }
    function add_bmp(file_name)
    {
        //window.alert("in");
        var bmp_area = document.getElementById("bmp_area");
        //var file_name = "20160324_1936.txt.bmp";
        var bmp_file_name = "./bmp/"+file_name;
        var str = "<img src="+bmp_file_name+" />";
        //var str = bmp_file_name;
        bmp_area.innerHTML = str;
        //$("#bmp_area").append('<img src=./bmp/'+bmp_file_name+'/><br/>');
    }

    // 输入姓名
    function show_prompt(){  
        name = prompt('请输入用户名：', '');
        if(!name || name=='null'){  
            alert("输入用户名为空或者为'null'，请重新输入！");  
            show_prompt();
        }
    }  

    // 提交对话
    function onSubmit() {
      var input = document.getElementById("textarea");
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
      ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      input.value = "";
      input.focus();
    }

    // 刷新用户列表框
    function flush_client_list(){
    	var userlist_window = $("#userlist");
    	var client_list_slelect = $("#client_list");
    	userlist_window.empty();
    	client_list_slelect.empty();
    	userlist_window.append('<h4>在线设备</h4><ul>');
    	client_list_slelect.append('<option value="all" id="cli_all">所有设备</option>');
    	for(var p in client_list){
            userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
            client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');
        }
    	$("#client_list").val(select_client_id);
    	userlist_window.append('</ul>');
    }

    // 发言
    function say(from_client_id, from_client_name, content, time){
    	//$("#dialog").append('<div class="speech_item"><img src="http://lorempixel.com/38/38/?'+from_client_id+'" class="user_icon" /> '+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>');
        $("#dialog").append('<div class="speech_item">'+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>');
    }

    $(function(){
    	select_client_id = 'all';
	    $("#client_list").change(function(){
	         select_client_id = $("#client_list option:selected").attr("value");
	    });
    });
    function get_data() {
      //var input = document.getElementById("textarea");
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
      //ws.send('{"type":"data","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      ws.send('{"type":"data","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+"send"+'"}');
      //input.value = "";
      //input.focus();
    }
    function sendCommand(command)
    {
      //window.alert(command);
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
        //ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+command.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
        if(command == 'T')
        {
            ws.send('{"type":"test","content":"20160503_2225.txt"}');
        }
        else
        {
            ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+command.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
        }
    }
    function onTouchStart(command)
    {
        sendCommand(command);
        setTimeout("window.gone_one_second=true", 1000);
    }
    function onTouchEnd()
    {
        if(window.gone_one_second)
        {
            sendCommand('A3L');
            window.gone_one_second = false;
        }
        else
        {
            sendCommand('A3S');
            window.gone_one_second = false;
        }
    }
  </script>
</head>
<body onload="connect();">
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span12">
			<h3 class="text-center">
				<strong>家居监控</strong>
			</h3>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span4">
			<h4>
				控制
			</h4>
			<p>
				<span class="label badge-inverse">电机：</span>
			</p> 
               <!-- 
                <input type=button style="background-image: url(up.png);width:70px;height:40px;" ontouchstart="sendCommand('A1')" ontouchend="sendCommand('A3')"/>
                <input type=button style="background-image: url(down.png);width:70px;height:40px;" ontouchstart="sendCommand('A2')" ontouchend="sendCommand('A3')"/>
                -->
                <button class="btn btn-info" type="button" ontouchstart="onTouchStart('A1')" ontouchend="onTouchEnd()">前进</button> 
                <button class="btn btn-info" type="button" ontouchstart="onTouchStart('A2')" ontouchend="onTouchEnd()">后退</button>
            <br/> 
            <br/> 
			<p>
				<span class="label">方向：</span>
			</p> 
                <button class="btn btn-primary" type="button" ontouchstart="sendCommand('B1')" ontouchend="sendCommand('B3')">左转</button> 
                <button class="btn btn-primary" type="button" ontouchstart="sendCommand('B2')" ontouchend="sendCommand('B3')">右转</button> 
            <br/> 
            <br/> 
			<p>
				<span class="label badge-inverse">自动避障</span>
			</p> 
                <button class="btn btn-info" type="button" onClick="sendCommand('D1')">开启</button> 
                <button class="btn btn-info" type="button" onClick="sendCommand('D2')">关闭</button>
			<p>
				<span class="label badge-inverse">摄像头位置</span>
			</p> 
                <button class="btn btn-info" type="button" onClick="sendCommand('E1')">向上</button> 
                <button class="btn btn-info" type="button" onClick="sendCommand('E2')">向下</button>
		</div>
		<div class="span4">
			<h4>
				图像
			</h4> 
            <button class="btn btn-success" type="button" onClick="sendCommand('C')">获取图像</button>
            <!--<button class="btn btn-success" type="button" onClick="sendCommand('T')">测试</button>-->
            <br/>
            <br/>
            <div id="bmp_area">
                pic area<br/>
            </div>
		</div>
		<div class="span4">
			<h4>
				信息
			</h4>
            组号：<?php echo isset($_GET['room_id'])&&intval($_GET['room_id'])>0 ? intval($_GET['room_id']):1; ?>
            <select style="margin-bottom:8px" id="client_list">
                <option value="all">所有人</option>
            </select>
            <div class="caption" id="userlist"></div>
            <div class="thumbnail">
                <div class="caption" id="dialog"></div>
            </div>
		</div>
	</div>
</div>
</body>
</html>
