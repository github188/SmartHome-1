<?php
/* 模式中的\b标记一个单词边界，所以只有独立的单词"web"会被匹配，而不会匹配
 * 单词的部分内容比如"webbing" 或 "cobweb" */
if (preg_match("/\bweb\b/i", "PHP is the web scripting language of choice.")) {
    echo "A match was found.";
} else {
    echo "A match was not found.";
}

if (preg_match("/\bweb\b/i", "PHP is the website scripting language of choice.")) {
    echo "A match was found.";
} else {
    echo "A match was not found.";
}
?> 

