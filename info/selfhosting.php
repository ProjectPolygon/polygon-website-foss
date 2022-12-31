<?php 
$bypassModeration = true;
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

pageBuilder::buildHeader();
?>
<div style="max-width: 50rem">
	<h2 class="font-weight-normal">About self-hosted servers</h2>
	<p>In <?=SITE_CONFIG["site"]["name"]?>, games are hosted by players on the website that other players connect to directly, instead of conventionally connecting to a server hosted by us. This is called self-hosting, but it's not without its caveats. There's some small security stuff that you should keep in mind while playing or hosting a game.</p>
	<h4 class="font-weight-normal">For players</h4>
	<p>When you connect to a server, if the server hoster knows a thing or two, they can get your IP address using a network analyzer or something. <br> This shouldn't really be something you have to be concerned about as they need to have actual malicious intent to use special tools to actually get it. It's not just right there in front of them to easily take (like it admittedly was with GoodBlox). However if you want to be cautious then it's recommended you use a VPN when playing, or you only join servers hosted by people you trust.</p>
	<h4 class="font-weight-normal">For server hosters</h4>
	<p>If a player also knows a thing or two, they can get your IP address using a web debugging proxy. It should be noted that clients connected to a server can only get the IP of the server hoster, and not the IP addresses of anyone else connected to the server.</p>
	<h4 class="font-weight-normal">With all of these in mind, have fun!</h4>
	<h2 class="font-weight-normal">Some additional information</h2>
	<h4 class="font-weight-normal">Resetting</h4>
	<p>Kind of like the ;ec command in Finobe, you can say ;hxiuh (or ;hx for short) to reset. That's about it.</p>
	<h4 class="font-weight-normal">Assets not loading</h4>
	<p>In order to help improve security, roblox.com had to be removed from the client's trust check code. Because of this, assets using www.roblox.com/asset/?id= will not load. <br> To get assets in your map to load, open up the map you want to host in a text editor (like notepad++) and do a find/replace operation for <code>www.roblox.com/asset</code> with <code>chef.pizzaboxer.xyz/asset</code>. <br> We may eventually automate this inside the client, but for now this is what you have to do.</p>
</div>
<?php pageBuilder::buildFooter(); ?>
