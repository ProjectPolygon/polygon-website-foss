<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize();

if(!isset($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID not set"); }
if(!is_numeric($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID is not numeric"); }
if(!isset($_GET['type'])){ api::respond(400, false, "Invalid Request - badge type not set"); }
if(!in_array($_GET['type'], ["user-badges", "polygon-badges"])){ api::respond(400, false, "Invalid Request - invalid badge type"); }

$selfProfile = isset($_SERVER['HTTP_REFERER']) && str_ends_with($_SERVER['HTTP_REFERER'], "/user");
$page = $_GET['page'] ?? 1;
$type = $_GET['type'] ?? false;

$userinfo = users::getUserInfoFromUid($_GET['userID']);
if(!$userinfo) api::respond(400, false, "User does not exist");

$badges = [];

if($_GET['type'] == "polygon-badges")
{
	if($userinfo->adminlevel)
	{
		$badges[] = ["name" => "Administrator", "id" => 1];
	}

	if($userinfo->adminlevel == 2)
	{
		//$badges[] = ["badgeName" => "Administrator", "badgeId" => 2];
	}
}
else
{

}


if($badges == [])
{  
	$responsemsg = ($selfProfile?"You have":$userinfo->username." has")."n't earned any ";
	$responsemsg .= $type == "user-badges" ? "player-made badges" : "Polygon badges";
	api::respond(200, true, $responsemsg);
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "badges" => $badges]));