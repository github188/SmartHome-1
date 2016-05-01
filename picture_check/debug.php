<?php
    $data_file_name = "test.txt";
    $source_file = fopen($data_file_name, "rb") or die("Unable to open file!");
    echo ftell($source_file)."<br/>";
    $r = 5; //查找半径设为5
    for($i=0; $i<20; $i++)
    {
        if(rowStart($source_file, $r))
        {
            echo "have find the '$i' row<br/>";
        }
        fseek($source_file, 25, SEEK_CUR);
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
}
