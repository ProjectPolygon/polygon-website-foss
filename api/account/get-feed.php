<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
Polygon::ImportClass("Groups");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "logged_in" => true]);

$FeedResults = db::run(
	"SELECT feed.*, users.username FROM feed 
	INNER JOIN users ON users.id = feed.userId WHERE userId = :uid
	OR groupId IS NULL AND userId IN 
	(
		SELECT (CASE WHEN requesterId = :uid THEN receiverId ELSE requesterId END) FROM friends 
		WHERE :uid IN (requesterId, receiverId) AND status = 1
	) 
	OR groupId IN 
	(
		SELECT groups_members.GroupID FROM groups_members 
	    INNER JOIN groups_ranks ON groups_ranks.GroupID = groups_members.GroupID AND groups_ranks.Rank = groups_members.Rank
	    WHERE groups_members.UserID = :uid AND groups_ranks.permissions LIKE '%\"CanViewGroupStatus\":true%'
	) 
	ORDER BY feed.id DESC LIMIT 15",
	[":uid" => SESSION["userId"]]
);

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

/* $news[] = 
[
	"header" => "Groups have been released!",
	"img" => "/img/ProjectPolygon.png",
	"message" => "Groups have now been fully released, with more functionality than you could ever imagine. Groups don't cost anything to make, and you can join up to 20 of them. <br> If you haven't yet, come join the <a href=\"/groups?gid=1\">official group</a>!"
]; */

$news[] = 
[
	"header" => "",
	"img" => "https://media.discordapp.net/attachments/745025397749448814/835635922590629888/HDKolobok-256px-3.gif",
	"message" => "What you know about KOLONBOK. ™ "
];

while($row = $FeedResults->fetch(PDO::FETCH_OBJ))
{ 
	$timestamp = timeSince($row->timestamp);

	if($row->groupId == NULL)
	{
		$feed[] = 
		[
			"userName" => $row->username, 
			"img" => Thumbnails::GetAvatar($row->userId, 100, 100), 
			"header" => "<p class=\"m-0\"><a href=\"/user?ID={$row->userId}\">{$row->username}</a> - <small>{$timestamp}</small></p>",
			"message" => Polygon::FilterText($row->text)
		]; 
	}
	else
	{
		$GroupInfo = Groups::GetGroupInfo($row->groupId, true, true);
		$GroupInfo->name = htmlspecialchars($GroupInfo->name);

		$feed[] = 
		[
			"userName" => $GroupInfo->name, 
			"img" => Thumbnails::GetAssetFromID($GroupInfo->emblem, 420, 420), 
			"header" => "<p class=\"m-0\"><a href=\"/groups?gid={$GroupInfo->id}\">{$GroupInfo->name}</a> - <small>posted by <a href=\"/user?ID={$row->userId}\">{$row->username}</a></small> - <small>{$timestamp}</small></p>",
			"message" => Polygon::FilterText($row->text)
		];
	}
}

$FeedCount = $FeedResults->rowCount();

if($FeedCount < 15)
{
	$feed[] = 
	[
		"userName" => "Your feed is currently empty!", 
		"img" => "/img/feed/friends.png", 
		"header" => "<h4 class=\"font-weight-normal\">Looks like your feed's empty</h4>",
		"message" => "If you haven't made any friends yet, <a href='/browse'>go make some</a>! <br> If you already have some, why don't you kick off the discussion?"
	]; 

	if($FeedCount < 14)
	{
		$feed[] = 
		[
			"userName" => "Customize your character", 
			"img" => "/img/feed/cart.png", 
			"header" => "<h4 class=\"font-weight-normal\">Customize your character</h4>",
			"message" => "Log in every day and earn 10 pizzas. Pizzas can be used to buy clothing in our <a href=\"/catalog\">catalog</a>. You can also create your own clothing on the <a href=\"/develop\">Build page</a>."
		]; 
	}
}

api::respond_custom(["status" => 200, "success" => true, "message" => "OK", "feed" => $feed, "news" => $news]);