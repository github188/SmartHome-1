<?php
define("separator_len", 5);
$data_file_name = "./20160503_2224.txt";
$data_file_size = filesize($data_file_name);
    $bi_data_size = floor($data_file_size*3/2);
    $bi_width = 320;
    $bi_height = 240;
    $bi_file_size = $bi_data_size + 54;
    //写入54个文件头的信息
    $binarydata = pack("nLSSLLLLSSLLLLLL", 0x424D,$bi_file_size,0,0,54,40,320,$bi_height,1,24,0,$bi_data_size,0,0,0,0);
    //给即将生成的bmp图像命名
    $bmp_file_name = $data_file_name.".bmp";
    //$fp = fopen("/root/workerman-chat1.1/Applications/Chat/Web/bmp/$bmp_file_name","wb");
    $fp = fopen("$bmp_file_name","wb");
    fwrite($fp, $binarydata, 54);
    $source_file = fopen($data_file_name, "rb") or die("Unable to open file!");
$stream = fread($source_file, $data_file_size);
fseek($source_file, 0, SEEK_SET);
$pos = 0;
$findString = "START";
for($i=0; $i<$bi_height; $i++)
{
    $pos = strpos($stream, $findString, $pos);
    RowTransform($source_file, $fp, $pos);
    echo "row:".$i." position:".$pos."<br/>"; 
    $pos += separator_len;
}
//echo ftell($source_file);

function RowTransform($source_file, $fp, $pos)
{
    $rowDataStart = $pos + separator_len;
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
/*
 * 转换需要什么呢？
 * 一行数据的长度
 * pos表示START中S位置，0:0,1:645,2:1290
 * rowDataLen[i] = pos[i+1] - pos[i] - 5
 * 且这行数据的起始数据的位置为 pos[i]+5;
 * 转换出错的原因就在于数据量和设定的长宽度不对应，
 * 因此，需要每一行的数据量都为640KB，不足的用附近的补全
 *
 * */
