<?php
$foo = "45678START9234START789c";
$str_len = strlen($foo);
echo $str_len."<br/>";
$pos = strrpos($foo, 'START');
echo $pos."<br/>";
echo strrpos($foo, 'START', -$pos);
