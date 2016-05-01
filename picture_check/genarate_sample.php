<?php
$fp = fopen("test.txt", "wb");
$findme = "START";
$data = "qwerttyuiopasdfghjkl";
for($i=0; $i<20; $i++)
{
    fwrite($fp, $findme, 5);
    fwrite($fp, $i, 1);
    fwrite($fp, $data, 19);
}
