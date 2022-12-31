<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

api::initialize(["method" => "POST", "logged_in" => true, "secure" => true]);

if (!Polygon::$GamesEnabled) api::respond(200, false, "Games are currently closed. See <a href=\"/forum\">this announcement</a> for more information.");

$ServerID = api::GetParameter("POST", "ServerID", "int");
$Username = api::GetParameter("POST", "Username", "string");
$Action = api::GetParameter("POST", "Action", ["Add", "Remove"]);

$ServerInfo = db::run("SELECT * FROM selfhosted_servers WHERE id = :ServerID", [":ServerID" => $ServerID])->fetch(PDO::FETCH_OBJ);
if (!$ServerInfo || !Users::IsAdmin(Users::STAFF_ADMINISTRATOR) && $ServerInfo->hoster != SESSION["userId"]) api::respond(200, false, "You do not have permission to configure this server");
if ($ServerInfo->Privacy != "Private") api::respond(200, false, "The privacy of this server must first be set to Private");
if ($ServerInfo->LastWhitelistEdit+30 > time()) api::respond(200, false, "Please wait ".GetReadableTime($ServerInfo->LastWhitelistEdit, ["RelativeTime" => "30 seconds"])." before editing your whitelist");

$Whitelist = ($ServerInfo->PrivacyWhitelist == null) ? [] : json_decode($ServerInfo->PrivacyWhitelist);

$UserInfo = Users::GetInfoFromName($Username);
if (!$UserInfo) api::respond(200, false, "That username is not on Project Polygon");

if ($Action == "Add")
{
    if ((int) $UserInfo->id == SESSION["userId"]) api::respond(200, false, "You cannot add yourself to the whitelist");
    if (in_array((int) $UserInfo->id, $Whitelist)) api::respond(200, false, "That user is already on the whitelist");
    $Whitelist[] = (int) $UserInfo->id;
}
else if ($Action == "Remove")
{
    if (!in_array((int) $UserInfo->id, $Whitelist)) api::respond(200, false, "That user is not on the whitelist");
    
    $Location = array_search((int) $UserInfo->id, $Whitelist);
    if ($Location === false) api::respond(200, false, "An unexpected error occurred");
    
    unset($Whitelist[$Location]);
}

db::run(
    "UPDATE selfhosted_servers SET LastWhitelistEdit = UNIX_TIMESTAMP(), PrivacyWhitelist = :Whitelist WHERE id = :ServerID", 
    [":ServerID" => $ServerID, ":Whitelist" => json_encode($Whitelist)]
);

if ($Action == "Add")
    api::respond(200, true, "$Username has been added to the whitelist");
else if ($Action == "Remove")
    api::respond(200, true, "$Username has been removed from the whitelist");