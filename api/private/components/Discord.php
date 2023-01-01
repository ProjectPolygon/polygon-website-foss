<?php

class Discord
{
	const WEBHOOK_POLYGON = "https://discord.com/api/webhooks/";
	const WEBHOOK_KUSH = "https://discord.com/api/webhooks/";

	static function GetUserInfo($UserID)
	{
		$ch = curl_init();
		curl_setopt_array($ch, 
		[
			CURLOPT_URL => "https://discord.com/api/v8/users/$UserID", 
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => ["Authorization: Bot"]
		]);

		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpcode != 200) return false;

		$response = json_decode($response);
		if ($response == NULL) return false;

		return (object)
		[
			"username" => $response->username,
			"tag" => $response->discriminator,
			"id" => $response->id,
			"avatar" => "https://cdn.discordapp.com/avatars/{$response->id}/{$response->avatar}.png",
			"color" => $response->accent_color,
			"banner" => $response->banner,
			"banner_color" => $response->banner_color
		];
	}

	static function SendToWebhook($Payload, $Webhook, $EscapeContent = true) 
	{
	    // example payload:
	    // $payload = ["username" => "test", "content" => "test", "avatar_url" => "https://polygon.pizzaboxer.xyz/thumbs/avatar?id=1&x=100&y=100"];
	    
	    if($EscapeContent)
	    {
		    $Payload["content"] = str_ireplace(["\\", "`"], ["\\\\", "\\`"], $Payload["content"]);
			$Payload["content"] = str_ireplace(["@everyone", "@here"], ["`@everyone`", "`@here`"], $Payload["content"]);
			$Payload["content"] = preg_replace("/(<@[0-9]+>)/i", "`$1`", $Payload["content"]);
		}

	    $ch = curl_init();  

	    curl_setopt($ch, CURLOPT_URL, $Webhook);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['payload_json' => json_encode($Payload)]));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	    $response = curl_exec($ch);
	    curl_close($ch);

	    return $response;
	}
}