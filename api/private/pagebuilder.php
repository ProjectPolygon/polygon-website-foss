<?php

class pageBuilder
{
	public static array $JSdependencies = 
	[
		"https://code.jquery.com/jquery-3.0.0.min.js",
		"/js/toastr.js",
		"/js/bootstrap-datepicker.min.js"
	];

	public static array $CSSdependencies = 
	[
		"/css/fontawesome-pro-v5.10.1/css/all.css",
		"/css/toastr.js.css",
		"/css/bootstrap-datepicker.min.css"
	];

	public static array $pageConfig = 
	[
		"title" => "a",
		"og:site_name" => SITE_CONFIG["site"]["name"],
		"og:url" => "https://polygon.pizzaboxer.xyz",
		"og:description" => "yeah its a website about shapes and squares and triangles and stuff and ummmmm",
		"og:image" => "",
		"containerWidth" => "w-100",
		"includeNav" => true
	];

	static function buildHeader()
	{
		global $pdo;
		$announcements = $pdo->query("SELECT * FROM announcements WHERE activated ORDER BY id DESC");

		$markdown = new Parsedown();
		$markdown->setMarkupEscaped(true);
		$markdown->setBreaksEnabled(true);
		$markdown->setSafeMode(true);
		$markdown->setUrlsLinked(true);

		ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="polygon-csrf" content="<?=SESSION?SESSION["csrfToken"]:'false'?>"> 
		<meta http-equiv="X-UA-Compatible" content="IE=edge"> 
		<link rel='shortcut icon' type='image/x-icon' href='/img/TinyBcIcon.ico' />
		<title><?=self::$pageConfig["title"]?> - <?=SITE_CONFIG["site"]["name"]?></title>
		<meta name="theme-color" content="#eb4034">
		<meta property="og:title" content="<?=self::$pageConfig["title"]?>">
		<meta property="og:site_name" content="<?=self::$pageConfig["og:site_name"]?>">
		<meta property="og:url" content="<?=self::$pageConfig["og:url"]?>">
		<meta property="og:description" content="<?=self::$pageConfig["og:description"]?>">
		<meta property="og:type" content="Website">
		<meta property="og:image" content="<?=self::$pageConfig["og:image"]?>">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<?php foreach(self::$CSSdependencies as $url){ ?>
		<link rel="stylesheet" href="<?=$url?>">
<?php } ?>
<?php foreach(self::$JSdependencies as $url){ ?>
		<script src="<?=$url?>"></script>
<?php } ?>
		<style>
			.navbar-orange,
			.nav-item .dropdown-menu
			{
			    background-color: #eb4034;
			}

			/* change the brand and text color */
			.navbar-orange .navbar-brand,
			.navbar-text,
			.navbar-nav .nav-link,
			.nav-item .dropdown-menu .dropdown-item
			{
			    color: rgba(255,255,255,1)!important;
			}

			/* change the color of active or hovered links */
			.navbar-top .nav-item:hover .nav-link,
			.darken
			{
			    color: rgba(255,255,255,0.75)!important;
			}

			.nav-item .dropdown-menu .dropdown-item:hover
			{
				background-color: transparent;
				color: rgba(255,255,255,0.75)!important;
			}

			.nav-item .dropdown-menu
			{
				min-width: 0;
				border: 1px solid #343a40;
				border-top: none;
				border-top-left-radius: 0px;
				border-top-right-radius: 0px;
			}

			.navbar-orange .navbar-toggler 
			{
				color: rgba(255,255,255,1)!important;
			}

			@media (min-width: 768px) 
			{
				.divider-right 
				{
				  border-right: 1px solid #ccc;
				}
			}

			@media (max-width: 768px) 
			{
				.divider-right 
				{
				  border-bottom: 1px solid #ccc;
				}
			}

			.divider-top 
			{
			  border-top: 1px solid #ccc;
			}

			.divider-bottom 
			{
			  border-bottom: 1px solid #ccc;
			}

			.hover:hover
			{
			  /*transform: scale(1.005);*/
			  box-shadow: 0 10px 20px rgba(0,0,0,.08), 0 4px 8px rgba(0,0,0,.06);
			}

			.alert p { margin-bottom: 0; }
		</style>
		<script>var pageLoadAnimation = <?=!SESSION || SESSION && SESSION["pageAnim"]?'true':'false'?>; var loggedIn = <?=SESSION?'true':'false'?>;</script>
	</head>
	<body>
<?php if(self::$pageConfig["includeNav"]) { ?>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-orange navbar-top py-0">
			<div class="container">
				<a class="navbar-brand" href="/"><?=SITE_CONFIG["site"]["name"]?></a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				    <span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="primaryNavbar">
				    <ul class="navbar-nav mr-auto">
				      	<li class="nav-item">
				        	<a class="nav-link" href="/">Home</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/">Games</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/">Catalog</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/">Develop</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/forum">Forum</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/browse">People</a>
				     	</li>
				     	<li class="nav-item dropdown">
					        <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</a>
					        <div class="dropdown-menu mt-0" aria-labelledby="moreDropdown">
					          <!--a class="dropdown-item" href="/browse">People</a>
					          <div class="dropdown-divider"></div-->
					          <a class="dropdown-item" href="/discord">Discord</a>
					          <a class="dropdown-item" href="https://twitter.com/boxerpizza">Twitter</a>
					        </div>
					    </li>
				    </ul>
				    <ul class="navbar-nav">
<?php if(SESSION) { ?>
				    	<li class="nav-item">
				        	<a class="nav-link mr-2" href="/user?ID=<?=SESSION["userId"]?>"><?=SESSION["userName"]?></a>
				     	</li>
				    	<li class="nav-item">
				    		<a class="btn btn-sm btn-light py-0 px-1" style="position:absolute; margin-left:25px; <?=!SESSION["friendRequests"]?'display:none':''?>"><?=SESSION["friendRequests"]?></a>
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" href="/friends">
				    			<i class="fas fa-user-friends"></i>
				    		</a>
				    	</li>
				    	<div class="divider-right mr-2 my-2"></div>
				    	<a class="nav-link darken px-0 mr-2" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?=SESSION["currency"]?> <?=SITE_CONFIG["site"]["currencyName"]?> <br> Your next stipend is in <?=general::time_elapsed("@".SESSION["nextCurrencyStipend"], true, false)?>"><?=SESSION["currency"]?> <i class="fal fa-pizza-slice"></i></a>
				    	<div class="divider-right mr-2 my-2"></div>
				    	<li class="nav-item">
				        	<a class="btn btn-sm btn-light my-1 mr-2 px-3" href="/logout">Logout</a>
				     	</li>
<?php } else { ?>
				      	<li class="nav-item">
				        	<a class="nav-link" href="/register">Sign Up</a>
				     	</li>
				     	<a class="nav-link darken px-0">or</a>
				     	<li class="nav-item">
				        	<a class="btn btn-sm btn-light my-1 mx-2 px-3" href="/login">Login</a>
				     	</li>
<?php } ?>
				    </ul>
				</div>
			</div>
		</nav>
<?php if(SESSION) { ?>
		<nav class="navbar navbar-expand-lg navbar-top bg-dark py-0">
			<div class="container">
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#secondaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				    <span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="secondaryNavbar">
				    <ul class="navbar-nav mr-auto">
				      	<li class="nav-item">
				        	<a class="nav-link py-1" href="/user">Profile</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/">Character</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/friends">Friends</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/">Inventory</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/">Money</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/my/account">Account</a>
				     	</li>
<?php if(SESSION && SESSION["adminLevel"]) { ?>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/admin">Admin</a>
				     	</li>
<?php } ?>
				    </ul>
				</div>
			</div>
		</nav>
<?php } while($row = $announcements->fetch(PDO::FETCH_OBJ)){ ?>
		<div class="alert py-2 mb-0 rounded-0 text-center <?=$row->textcolor?>" role="alert" style="background-color: <?=$row->bgcolor?>">
		  <?=$markdown->text($row->text)?>
		</div>
<?php } } ?>
		<noscript>
		  <div class="alert py-2 mb-0 rounded-0 text-center text-light bg-danger" role="alert">
			This site relies on Javascript quite heavily, so many site features won't work without it. Consider enabling it or switching to a newer browser (please)?
		  </div>
		</noscript>
		<div class="app container py-5 <?=self::$pageConfig["containerWidth"]?>"<?=!SESSION || SESSION && SESSION["pageAnim"]?' style="display:none"':''?>>
	<?php
		echo ob_get_clean();
	}

	static function buildFooter()
	{
		ob_start();
	?>
		</div>
		<div class="modal fade" id="primaryModal" tabindex="-1" role="dialog" aria-labelledby="primaryModalCenter" aria-hidden="true">
		  <div class="modal-dialog modal-dialog-centered" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="col-11 modal-title text-center pl-5" id="primaryModalTitle">undefined</h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
		      </div>
		      <div class="modal-body text-center text-break">
		      	undefined
		      </div>
		      <div class="modal-footer">
		      	<div class="mx-auto">
		      	</div>
		      </div>
		    </div>
		  </div>
		</div>
		<script src="/js/bootstrap.bundle.min.js"></script>
		<script>
		  //core.js
		  $.ajaxSetup(
		  {
			headers: { 'x-polygon-csrf': $('meta[name="polygon-csrf"]').attr('content') }
		  });

		  $(document).ready(function()
		  {
		  	if(loggedIn){ setInterval(function(){ $.post("/api/account/updateping"); }, 30000); }
		  	if(pageLoadAnimation){ $(".app").fadeIn(350); }
		  	$("[data-toggle='tooltip']").tooltip();
		  });

		  function showModal(title, body, buttons)
		  {
		  	var modalFooter = $("#primaryModal").find(".modal-footer").find(".mx-auto");
		  	$("#primaryModal").find(".modal-title").text(title);
		  	$("#primaryModal").find(".modal-body").text(body);
		  	$("#primaryModal").modal("show");

		  	modalFooter.empty();

		  	$.each(buttons, function(i, button)
		  	{
		  		var buttonHtml = '<button type="button" class="'+button.class+' text-center mx-1"';
		  		if(button.isDismissButton){ buttonHtml += ' data-dismiss="modal"'; }
		  		if(button.onclick){ buttonHtml += ' onclick="'+button.onclick+'"'; }
		  		buttonHtml += '>'+button.text+'</button>';

		  		modalFooter.html(modalFooter.html()+buttonHtml);
		  	});
		  }
		</script>
		<script>
			//friends.js
			var polygon_friends = 
			  {
			      getFriends: function(userid, page, limit)
			      {
			      	return $.post('/api/friends/getfriends', {"userID": userid, "page": page, "limit": limit});
			      },

			      getFriendRequests: function(page)
			      {
			      	return $.post('/api/friends/getfriendrequests', {"page": page});
			      },

			      friendAction: function(userid, type)
			      {
			      	switch(type)
			      	{
			      		case "sendRequest":
				      		return $.post('/api/friends/sendrequest', {"userID": userid});
				      		break;

			      		case "acceptRequest":
			      			return $.post('/api/friends/acceptrequest', {"userID": userid});
			      			break;

			      		case "revokeRequest":
			      			return $.post('/api/friends/revokefriend', {"userID": userid});
			      			break;
			      	}
			      },
			  };

			  function registerFriendActionHandler()
			  {
				  $('a[aria-controls$="Request"]').on('click', this, function()
				  {
				    polygon_friends.friendAction($(this).attr("aria-userid"), $(this).attr("aria-controls")).done(function(data)
				    {
				    	if(data.success)
				    	{
				    		if(window.location.pathname.toLowerCase() == "/user"){ location.reload(); }
				    		else{ refreshFriends(); refreshFriendRequests(); }
				    	}
				    	else{ toastr["error"](data.message); }
				    });			    
				  });

				  $('a[data-control$="friends-pager"]').on('click', this, function(event)
				  {
				  	event.preventDefault();
					displayFriends($(this).attr("data-page"));    
				  });
			  }

			  function displayFriends(page)
			  {
			  	userId = $("#friends").attr("data-uid");
			  	limit = $("#friends").attr("data-limit");
			  	self = $("#friends").attr("data-selfpage");

			  	$(".friends-tab-content").empty();
			  	$(".friends-pager").empty();
			  	$("#friends").find(".spinner-border").show();

			  	polygon_friends.getFriends(userId, page, limit).done(function(data)
			  	{ 
			  		if(data.success)
			  		{ 
			  			$("#friends").find(".spinner-border").hide();
			  			if(data.pager){ $(".friends-pager").html($(".friends-pager").html()+data.pager); }

			  			if(Object.keys(data.friends).length)
			  			{
			  				$.each(data.friends, function(i, friend)
					  		{
					  			var friendCard = $(".friend-card-template").clone().removeClass("d-none friend-card-template");
					  			friendCard.html(friendCard.html().replace(/\$username/g, friend.userName));
					  			friendCard.html(friendCard.html().replace(/\$userid/g, friend.userId));
					  			friendCard.appendTo(".friends-tab-content");
					  		});

					  		registerFriendActionHandler();
			  			}
			  			else
			  			{
			  				if(self == 'true'){ $(".friends-tab-content").html('<p class="pl-4">You don\'t have any friends. <a href="/browse">Go make some!</a>'); }
			  				else{ $(".friends-tab-content").html('<p class="pl-4">'+data.username+' does not have any friends.</p>'); }
			  			}
			  		}
			  		else
			  		{ 
			  			toastr["error"]("Failed to fetch friends: "+data.message);
			  		}
			  	});
			  }

			  function displayFriendRequests(page)
			  {
			  	polygon_friends.getFriendRequests(page).done(function(data)
			  	{ 
			  		if(data.success)
			  		{ 
			  			if(Object.keys(data.requests).length)
			  			{
			  				$(".requestsIndicator").removeClass("d-none");
			  				$(".requestsIndicator").text(Object.keys(data.requests).length);
			  				$.each(data.requests, function(i, request)
					  		{
					  			var requestCard = $(".request-card-template").clone().removeClass("d-none request-card-template");
					  			requestCard.html(requestCard.html().replace(/\$username/g, request.userName));
					  			requestCard.html(requestCard.html().replace(/\$userid/g, request.userId));
					  			requestCard.appendTo(".requests-tab-content");
					  		});

					  		registerFriendActionHandler();
			  			}
			  			else
			  			{
			  				$(".requestsIndicator").addClass("d-none");
			  				$(".requests-tab-content").html('<p class="pl-4">Looks like there aren\'t any friend requests at the moment.');
			  			}
			  		}
			  		else{ alert("Failed to fetch friends: "+data.message); }
			  	});
			  }

			  $(document).ready(function(){ registerFriendActionHandler(); })
		</script>
		<?php if(SESSION && SESSION["adminLevel"]){ ?>
		<script>
		//admin.js
		$('a[data-control$="moderateForum"]').on('click', this, function()
		{
			if(confirm("Are you sure you want to delete this forum post?"))
			{
				var postType = $(this).attr("data-type");
				var postId = $(this).attr("data-id");
				$.post('/api/admin/deleteforumpost', {"postType": postType, "postId": postId}, function(data){
					if(data.success)
					{
						toastr["success"](postType+" ID "+postId+" has been successfully deleted");
						setTimeout(function(){ location.reload(); }, 1500);
					}
					else
					{
						toastr["error"](data.message);
					}
				});
			}
		});
		</script>
		<?php } ?>
		<script>
			toastr.options = {
			  "closeButton": true,
			  "debug": false,
			  "newestOnTop": true,
			  "progressBar": true,
			  "positionClass": "toast-bottom-center",
			  "preventDuplicates": true,
			  "onclick": null,
			  "showDuration": "300",
			  "hideDuration": "1000",
			  "timeOut": "5000",
			  "extendedTimeOut": "1000",
			  "showEasing": "swing",
			  "hideEasing": "linear",
			  "showMethod": "fadeIn",
			  "hideMethod": "fadeOut"
			}
		</script>
	</body>
</html>
	<?php
		echo ob_get_clean();
	}

	static function errorCode($code)
	{
		http_response_code($code);
		$text = 
		[
			404 => ["name" => "Page Not Found", "text" => "the page you are looking for does not exist <br> check that there's no mistakes in the url"],
			403 => ["name" => "Forbidden", "text" => "you cannot access this page"],
			420 => ["name" => "Page Under Construction", "text" => "this page is under construction and is only accessible to admins for now <br> check back later <br> <small>*obviously not a real code lol but i had to put something</small>"]
		];
		self::buildHeader();
	?>
	<div class="card mx-auto" style="max-width:640px;">
	  <div class="card-body text-center">
	    <h1 class="font-weight-normal"><?=$code?> / <?=$text[$code]["name"]?></h1>
	    <?=$text[$code]["text"]?> <br>
	    <a class="btn btn-outline-primary mt-2 px-4" onclick="window.history.back();">‹ Back</a> 
	    <a class="btn btn-outline-secondary mt-2 px-4" href="/">Home</a>
	  </div>
	</div>
	<?php
		self::buildFooter();
		die();
	}
}