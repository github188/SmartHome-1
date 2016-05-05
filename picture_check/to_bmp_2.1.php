<?php
data_to_bmp("./20160430_1144.txt");
function data_to_bmp($data_file_name)
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
//    $fp = fopen("/root/workerman-chat1.1/Applications/Chat/Web/bmp/$bmp_file_name","wb");
    $fp = fopen("$bmp_file_name","wb");
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
        //compute::RowTransform($source_file, $fp, $pos);
        RowTransform($source_file, $fp, $pos);
        //echo "row:".$i." position:".$pos."\n"; 
        //echo "row:".$i." position:".$pos."<br/>"; 
    }
    fclose($source_file);
    fclose($fp);
    return $bmp_file_name;
}
function RowTransform($source_file, $fp, $pos)
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
