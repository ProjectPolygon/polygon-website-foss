<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(isset($_COOKIE['polygon_session']) && session::getSessionData($_COOKIE['polygon_session']))
{	
	session::destroySession($_COOKIE['polygon_session']);
	session::invalidateSession($_COOKIE['polygon_session']);
}

header("Location: /");