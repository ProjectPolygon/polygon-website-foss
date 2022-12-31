<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
api::initialize(["method" => "POST", "logged_in" => true]);

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

/*$news[] = 
[
	"header" => '<h4 class="font-weight-normal">lol</h4>',
	"message" => 'fucked your mom'
];*/

/*$news[] = 
[
	"header" => '<h4 class="font-weight-normal">this isn\'t dead!!!! (probably)</h4>',
	"message" => "ive been more inclined to work on polygon now after like 4 months, so i guess development has resumed <br><br> 2fa has been implemented, and next on the roadmap is the catalog system. so yeah, stay tuned for that"
];*/

/* $news[] = 
[
	"header" => "",
	"img" => "https://media.discordapp.net/attachments/745025397749448814/835635922590629888/HDKolobok-256px-3.gif",
	// "message" => "What you know about KOLONBOK. ™ "
	"message" => "KOLONBOK. ™ Has fix 2009"
]; */

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$feed[] = 
	[
		"userName" => users::getUserNameFromUid($row->userId), 
		"img" => Thumbnails::GetAvatar($row->userId, 100, 100), 
		"header" => '<p class="m-0"><a href="/user?ID='.$row->userId.'">'.users::getUserNameFromUid($row->userId).'</a> - <small>'.timeSince('@'.$row->timestamp).'</small></p>',
		"message" => polygon::filterText($row->text)
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
api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "feed" => $feed, "news" => $news]);