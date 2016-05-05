<?php
use \GatewayWorker\Lib\Gateway;
class compute
{
    public static function data_to_bmp($data_file_name)
    {
        $data_file_size = filesize($data_file_name);
        $bi_data_size = floor($data_file_size*3/2);
        $bi_width = 320;
        $bi_height = 240;
        $bi_file_size = $bi_data_size + 54;
        //写入54个文件头的信息
        $binarydata = pack("nLSSLLLLSSLLLLLL", 0x424D,$bi_file_size,0,0,54,40,320,$bi_height,1,24,0,$bi_data_size,0,0,0,0);
        //给即将生成的bmp图像命名
        //date_default_timezone_set('Asia/Shanghai');
        //时间命名，年月日_时分秒.bmp
        $bmp_file_name = $data_file_name.".bmp";
        $fp = fopen("/root/workerman-chat1.1/Applications/Chat/Web/bmp/$bmp_file_name","wb");
        //$fp = fopen("$bmp_file_name","wb");
        fwrite($fp, $binarydata, 54);
        $source_file = fopen($data_file_name, "rb") or die("Unable to open file!");
        //转换
        $stream = fread($source_file, $data_file_size);
        fseek($source_file, 0, SEEK_SET);
        $pos = 0;
        $findString = "START";
        for($i=0; $i<$bi_height; $i++)
        {
            $pos = strrpos($stream, $findString);
            $stream = substr($stream, 0, $pos);
            compute::RowTransform($source_file, $fp, $pos);
            //echo "row:".$i." position:".$pos."\n"; 
        }
        fclose($source_file);
        fclose($fp);
        return $bmp_file_name;
    }

    public static function RowTransform($source_file, $fp, $pos)
    {
        $rowDataStart = $pos + 5;
        fseek($source_file, $rowDataStart, SEEK_SET);
        for($j=0; $j<320; $j++)
        {
            $a16 = fread($source_file, "1");
            $b16 = fread($source_file, "1");
            $a16 = ord($a16);
            $b16 = ord($b16);

            $B = $b16 & 0x1F;
            $G=$a16 &0x07;
            $G=$G<<3;
            $G=$G|($b16>>5);
            $R=$a16>>3;
            $b = $B<<3; 
            $g = $G<<2;
            $r = $R<<3; 
            $b = chr($b);
            $g = chr($g);
            $r = chr($r);
            fwrite($fp, $b, 1);
            fwrite($fp, $g, 1);
            fwrite($fp, $r, 1);
        }
    }
}

class Event
{
   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
//       static $_SESSION['mcu_id'] = "";
//       echo __LINE__."mcu_id ".$_SESSION['mcu_id']."\n";
       if(isset($_SESSION['mcu_id']))
       {
//          echo __LINE__."mcu_id is:".$_SESSION['mcu_id']."\n";
          if($_SESSION['mcu_id'] == $client_id)//内容可能是PING
          {
//           echo __LINE__."mcu is sending data...\n";
           date_default_timezone_set('PRC'); //设置中国时区 
           $data_file_name = date("Ymd_Hi").".txt";
           $fp = fopen($data_file_name, "wb");
           fwrite($fp, $message, strlen($message));
           fclose($fp);
           $bmp_file_name = compute::data_to_bmp($data_file_name);
           $new_message = array('type'=>'bmp','file_name'=>$bmp_file_name);
           //Gateway::sendToCurrentClient(json_encode($new_message));
           Gateway::sendToAll(json_encode($new_message));
           //echo $new_message."\n";
          // echo "finish\n";
           return;
          }
       }

        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        // 客户端传递的是json数据
        //$message = trim($message,"END");
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'test':
               $bmp_file_name = compute::data_to_bmp($message_data['content']);
               $new_message = array('type'=>'bmp','file_name'=>$bmp_file_name);
               //Gateway::sendToCurrentClient(json_encode($new_message));
               Gateway::sendToAll(json_encode($new_message));
                return;
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                
                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
                $client_name = htmlspecialchars($message_data['client_name']);
                //记下mcu_id
                echo __LINE__.$client_name."\n";
                if($client_name == 'mcu')
                {
                    $_SESSION['mcu_id'] = $client_id;
                    echo __LINE__."mcu_id is set ".$_SESSION['mcu_id']."\n";
                }
                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;
              
                // 获取房间内所有用户列表 
                $clients_list = Gateway::getClientInfoByGroup($room_id);
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;
                
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx} 
                $new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
                Gateway::sendToGroup($room_id, json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);
               
                // 给当前用户发送用户列表 
                $new_message['client_list'] = $clients_list;
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;
                
            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                
                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = $message_data['content'];
                    echo $new_message."\n";
                    Gateway::sendToClient($message_data['to_client_id'], $new_message);
                    return;
                }

                $new_message = array(
                    'type'=>'say', 
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       
       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }
  
}
