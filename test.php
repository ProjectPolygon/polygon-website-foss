<?php 

if(!isset($_GET['uuid'])){ die("uuid not set"); }
$uuid = str_replace("-", "", $_GET['uuid']);
$usernameData = json_decode(file_get_contents("https://api.mojang.com/user/profiles/".$uuid."/names"));
if($usernameData == NULL){ die("user doesnt exist"); } //user doesnt exist
$key = "GBAUTH-".strtoupper(str_rot13(strrev($uuid))); //best i could come up for a reversible encryption method lol
echo $key;
