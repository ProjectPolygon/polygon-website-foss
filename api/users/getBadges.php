<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if(!isset($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID not set"); }
if(!is_numeric($_GET['userID'])){ api::respond(400, false, "Invalid Request - userID is not numeric"); }
if(!isset($_GET['type'])){ api::respond(400, false, "Invalid Request - badge type not set"); }
if(!in_array($_GET['type'], ["user-badges", "polygon-badges"])){ api::respond(400, false, "Invalid Request - invalid badge type"); }

$selfProfile = isset($_GET['selfProfile']) && $_GET['selfProfile'] == "true" ? true : false;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$userinfo = users::getUserInfoFromUid($_GET['userID']);
if(!$userinfo){ api::respond(400, false, "User does not exist"); }

$badges = [];

if($_GET['type'] == "polygon-badges")
{
	if($userinfo->adminlevel)
	{
		$badges[] = ["badgeName" => "Administrator", "badgeId" => 1];
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
	$responsemsg = $selfProfile ? "You haven't earned any" : $userinfo->username." hasn't earned any";
	$responsemsg .= $_GET['type'] == "user-badges" ? " player-made badges" : " Polygon badges";
}
else
{
	$responsemsg = $badges;
}

api::respond(200, true, $responsemsg);