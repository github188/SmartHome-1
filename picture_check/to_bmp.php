<?php
data_to_bmp("test.txt");

function data_to_bmp($data_file_name)
{
    $data_file_size = 153600;
    $bi_data_size = floor($data_file_size*3/2);
    $bi_width = 320;
    $bi_height = 240;
    $bi_file_size = $bi_data_size + 54;
    //写入54个文件头的信息
    $binarydata = pack("nLSSLLLLSSLLLLLL", 0x424D,$bi_file_size,0,0,54,40,320,$bi_height,1,24,0,$bi_data_size,0,0,0,0);
    //给即将生成的bmp图像命名
    date_default_timezone_set('Asia/Shanghai');
    //时间命名，年月日_时分秒.bmp
    $bmp_file_name = $data_file_name.".bmp";
    //$fp = fopen("/root/workerman-chat1.1/Applications/Chat/Web/bmp/$bmp_file_name","wb");
    $fp = fopen("$bmp_file_name","wb");
    fwrite($fp, $binarydata, 54);
    $source_file = fopen($data_file_name, "rb") or die("Unable to open file!");
    //转换
    $r = 7; //查找半径设为5
    for($i=0; $i<20; $i++)
    {
        if(rowStart($source_file, $r))
        {
            echo "have find the '$i' row<br/>";
            /*
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
             */
        }
        fseek($source_file, 25, SEEK_CUR);
    }
}

/*
 * 参数：数据文件指针，匹配半径，匹配范围为2r
 * 返回值：匹配成功，返回true，文件指针指向行数据开始位置；失败，返回false，指针。。。
 * */
function rowStart($source_file, $r)
{
    $str1 = fread($source_file, 5);
    if(0 == strcmp($str1, "START"))  //没有丢失数据
    {
        echo "case 1<br/>";
        fseek($source_file, -5, SEEK_CUR);
        return true;
    }
    else
    {
        echo ftell($source_file)." ";
        fseek($source_file, -5, SEEK_CUR);
        fseek($source_file, -$r, SEEK_CUR);
        echo ftell($source_file)." ";
        $str = fread($source_file, 2*$r);
        echo ftell($source_file)." ";
        $findme = "START";
        $pos = strpos($str, $findme);
        if ($pos === false) 
        {
            echo "No, The string '$findme' was not found in the string '$str'<br/>";
            fseek($source_file, -2*$r, SEEK_CUR); //匹配失败，指针回到进入函数的位置
            fseek($source_file, 5, SEEK_CUR); //匹配失败，指针回到进入函数的位置
            return false;
        } 
        else 
        {
            echo "Yes, The string '$findme' was found in the string '$str'<br/>";
            echo " and exists at position $pos<br/>";
            fseek($source_file, -2*$r, SEEK_CUR); //指针回到进入函数的位置
            fseek($source_file, $pos, SEEK_CUR); //使指针指向数据行开始的位置
            return true;
        }
    }
}
/*
 * 如果做模糊匹配的话要分很多类讨论 separator_len :5, 4, 3，偏移值 = data_row_len + separator_len
 * 第一次必须要匹配上，或者扩大搜索半径
 * 搜索半径应该大于分隔符长度
 * */
