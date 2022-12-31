<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST"]);

$UserID = API::GetParameter("POST", "UserID", "int");
$BadgeType = API::GetParameter("POST", "BadgeType", ["Place", "Polygon"]);
$selfProfile = isset($_SERVER['HTTP_REFERER']) && str_ends_with($_SERVER['HTTP_REFERER'], "/user");

$userinfo = Users::GetInfoFromID($UserID);
if(!$userinfo) API::respond(400, false, "User does not exist");

$badges = [];

if($BadgeType == "Polygon")
{
	if($userinfo->adminlevel == Users::STAFF_ADMINISTRATOR)
		$badges[] = 
		[
			"name" => "Administrator", 
			"image" => "/img/ProjectPolygon.png", 
			"info" => "This badge identifies an account as belonging to a ".SITE_CONFIG["site"]["name"]." administrator. Only official ".SITE_CONFIG["site"]["name"]." administrators will possess this badge. If someone claims to be an admin, but does not have this badge, they are potentially trying to mislead you."
		];

	if($userinfo->adminlevel == Users::STAFF_MODERATOR)
		$badges[] = 
		[
			"name" => "Moderator", 
			"image" => "/img/badges/Moderator.png", 
			"info" => "Users with this badge are moderators. Moderators have special powers on ".SITE_CONFIG["site"]["name"]." that allow them to moderate users and catalog items that other users upload. Users who are exemplary citizens on ".SITE_CONFIG["site"]["name"]." over a long period of time may be invited to be moderators. This badge is granted by invitation only."
		];

	if($userinfo->adminlevel == Users::STAFF_CATALOG)
		$badges[] = 
		[
			"name" => "Catalog Manager", 
			"image" => "/img/badges/CatalogManager.png",
			"info" => "Users with this badge are catalog managers. Catalog managers have special powers on ".SITE_CONFIG["site"]["name"]." that allow them to create and moderate catalog items that other users upload. Users who are exemplary citizens on ".SITE_CONFIG["site"]["name"]." over a long period of time may be invited to be catalog managers. This badge is granted by invitation only."
		];

	if(Users::GetFriendCount($userinfo->id) >= 20)
		$badges[] = 
		[
			"name" => "Friendship", 
			"image" => "/img/badges/Friends.png", 
			"info" => "This badge is given to players who have embraced the ".SITE_CONFIG["site"]["name"]." community and have made at least 20 friends. People who have this badge are good people to know and can probably help you out if you are having trouble."
		];

	if(time() >= strtotime("1 year", $userinfo->jointime))
		$badges[] = 
		[
			"name" => "Veteran", 
			"image" => "/img/badges/Veteran.png", 
			"info" => "This decoration is awarded to all citizens who have played ".SITE_CONFIG["site"]["name"]." for at least a year. It recognizes stalwart community members who have stuck with us over countless releases and have helped shape ".SITE_CONFIG["site"]["name"]." into the game that it is today. These medalists are the true steel, the core of the Polygonian history ... and its future."
		];
}
else
{
	// TODO: add when we get dedicated servers
}


if($badges == [])
{  
	$responsemsg = ($selfProfile?"You have":$userinfo->username." has")."n't earned any ";
	$responsemsg .= $BadgeType == "Polygon" ? SITE_CONFIG["site"]["name"]." badges" : "player badges";
	API::respond(200, true, $responsemsg);
}

die(json_encode(["status" => 200, "success" => true, "message" => "OK", "pages" => 1, "items" => $badges]));