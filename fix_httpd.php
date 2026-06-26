<?php
$file = 'C:/xampp/apache/conf/httpd.conf';
$content = file_get_contents($file);
$content = str_replace("\0", "", $content);
file_put_contents($file, $content);
echo "Cleaned!";
