<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
api::requireLogin();

$userid = SESSION["userId"];

$query = $pdo->prepare("
	SELECT * FROM feed 
	WHERE userId = :uid
	OR userId IN (SELECT receiverId FROM friends WHERE requesterId = :uid AND status = 1) 
	OR userId IN (SELECT requesterId FROM friends WHERE receiverId = :uid AND status = 1) 
	ORDER BY id DESC LIMIT 15");
$query->bindParam(":uid", $userid, PDO::PARAM_INT);
$query->execute();

$feed = [];
$news = [];

$news[] = 
[
	"header" => '<h4 class="font-weight-normal">lol</h4>',
	"message" => 'fucked your mom'
];

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$feed[] = 
	[
		"userName" => users::getUserNameFromUid($row->userId), 
		"img" => "/thumbnail/user?ID=".$row->userId, 
		"header" => '<p class="m-0"><a href="/user?ID='.$row->userId.'">'.users::getUserNameFromUid($row->userId).'</a> - <small>'.general::time_elapsed('@'.$row->timestamp).'</small></p>',
		"message" => general::filterText($row->text)
	]; 
}

if($query->rowCount() < 15)
{
	$feed[] = 
	[
		"userName" => "Your feed is currently empty!", 
		"img" => "/img/feed-starter.png", 
		"header" => '<h4 class="font-weight-normal">Looks like your feed\'s empty</h4>',
		"message" => "If you haven't made any friends yet, <a href='/browse'>go make some</a>! <br> If you already have some, why don't you kick off the discussion?"
	]; 
}
die(json_encode(["status" => 200, "success" => true, "message" => "OK", "feed" => $feed, "news" => $news]));