<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Forum;
use pizzaboxer\ProjectPolygon\API;

API::initialize(["method" => "POST", "admin" => [Users::STAFF_MODERATOR, Users::STAFF_ADMINISTRATOR], "admin_ratelimit" => true, "secure" => true]);

$postType = API::GetParameter("POST", "postType", ["thread", "reply"]);
$postId = API::GetParameter("POST", "postId", "int");

$isThread = ($postType == "thread");
$post = $isThread ? Forum::GetThreadInfo($postId) : Forum::GetReplyInfo($postId);

if (!$post) API::respond(200, false, "Post does not exist");

if ($isThread)
{
	Database::singleton()->run("UPDATE forum_threads SET deleted = 1 WHERE id = :postId", [":postId" => $postId]);
	Database::singleton()->run("UPDATE users SET ForumThreads = ForumThreads - 1 WHERE id = :userId", [":userId" => $post->author]);
	Users::LogStaffAction("[ Forums ] Deleted forum thread ID {$postId}"); 
}
else
{
	Database::singleton()->run("UPDATE forum_replies SET deleted = 1 WHERE id = :postId", [":postId" => $postId]);
	Database::singleton()->run("UPDATE users SET ForumReplies = ForumReplies - 1 WHERE id = :userId", [":userId" => $post->author]);
	Users::LogStaffAction("[ Forums ] Deleted forum reply ID {$postId}"); 
}

API::respond(200, true, "OK");