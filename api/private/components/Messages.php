<?php

class Messages 
{
    static function getMessageInfoFromId($MessageId)
    {
        return db::run("SELECT * FROM messages WHERE ID = :msgId", [":msgId" => $MessageId])->fetch(PDO::FETCH_OBJ);
    }
    static function getAllSentMessages($userId)
    {
        return db::run("SELECT * FROM messages WHERE SenderID = :uId", [":uId" => $userId]);
    }
}