<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
if (!Polygon::IsGameserverAuthorized()) PageBuilder::errorCode(404);

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("content-type: text/plain; charset=utf-8");

$UserID = api::GetParameter("GET", "UserID", "int");
db::run("UPDATE users SET Wipeouts = Wipeouts + 1 WHERE id = :UserID", [":UserID" => $UserID]);