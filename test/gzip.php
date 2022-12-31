<?php
header("Content-type: application/octet-stream");
$file = gzopen('gzip.rbxl', 'rb');
while(!gzeof($file)) { echo gzread($file, 8192); }
gzclose($file);
?>