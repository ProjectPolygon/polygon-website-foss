<?php
require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$method = $_GET['method'] ?? false;
$groupId = $_GET['groupid'] ?? false;
$userId = $_GET['playerid'] ?? false;
$userId2 = $_GET['userid'] ?? false;

if($method == "IsFriendsWith" || $method == "IsBestFriendsWith")
{
	$IsFriends = db::run(
		"SELECT COUNT(*) FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = 1", 
		[":uid1" => $userId, ":uid2" => $userId2]
	)->rowCount();

	if($IsFriends) die('<Value Type="boolean">true</Value>');
	else die('<Value Type="boolean">false</Value>');
}
else if($method == "IsInGroup")
{
	$IsInGroup = db::run(
		"SELECT * FROM groups_members WHERE GroupID = :GroupID AND UserID = :UserID", 
		[":GroupID" => $groupId, ":UserID" => $userId]
	)->rowCount();

	if($IsInGroup) die('<Value Type="boolean">true</Value>');
	else die('<Value Type="boolean">false</Value>');
}
else if($method == "GetGroupRank")
{
	$GroupRank = db::run(
		"SELECT Rank FROM groups_members WHERE GroupID = :GroupID AND UserID = :UserID", 
		[":GroupID" => $groupId, ":UserID" => $userId]
	);

	if($GroupRank->rowCount()) die('<Value Type="integer">' . $GroupRank->fetchColumn() . '</Value>');
	else die('<Value Type="integer">0</Value>');
}
else if($method == "GetGroupRole")
{
	$GroupRole = db::run(
		"SELECT groups_ranks.Name FROM groups_members
		INNER JOIN groups_ranks ON groups_ranks.Rank = groups_members.Rank AND groups_ranks.GroupID = groups_members.GroupID
		WHERE groups_members.GroupID = :GroupID AND groups_members.UserID = :UserID", 
		[":GroupID" => $groupId, ":UserID" => $userId]
	);

	if($GroupRole->rowCount()) die($GroupRole->fetchColumn());
	else die('Guest');
}

echo '<Value Type="boolean">false</Value>';