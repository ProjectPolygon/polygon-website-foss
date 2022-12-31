<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(isset($_COOKIE['polygon_session'])) Session::Clear($_COOKIE['polygon_session']);
header("Location: /");