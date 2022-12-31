<?php

namespace pizzaboxer\ProjectPolygon;

use pizzaboxer\ProjectPolygon\Database;

class Forum
{
	static function GetThreadInfo($id)
	{
		return Database::singleton()->run("SELECT * FROM forum_threads WHERE id = :id", [":id" => $id])->fetch(\PDO::FETCH_OBJ);
	}

	static function GetReplyInfo($id)
	{
		return Database::singleton()->run("SELECT * FROM forum_replies WHERE id = :id", [":id" => $id])->fetch(\PDO::FETCH_OBJ);
	}

	static function GetThreadReplies($id)
	{
		return Database::singleton()->run("SELECT COUNT(*) FROM forum_replies WHERE threadId = :id AND NOT deleted", [":id" => $id])->fetchColumn() ?: "-";
	}

	static function GetSubforumInfo($id)
	{
		return Database::singleton()->run("SELECT * FROM forum_subforums WHERE id = :id", [":id" => $id])->fetch(\PDO::FETCH_OBJ);
	}

	static function GetSubforumThreadCount($id, $includeReplies = false)
	{
		$threads = Database::singleton()->run("SELECT COUNT(*) FROM forum_threads WHERE subforumid = :id", [":id" => $id])->fetchColumn();
		if(!$includeReplies) return $threads ?: '-';

		$replies = Database::singleton()->run("SELECT COUNT(*) from forum_replies WHERE threadId IN (SELECT id FROM forum_threads WHERE subforumid = :id)", [":id" => $id])->fetchColumn();
		$total = $threads + $replies;

		return $total ?: '-';
	}
}