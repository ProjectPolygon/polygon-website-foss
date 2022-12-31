<?php

class pageBuilder
{
	public static string $additionalFooterStuff = "";

	public static array $JSdependencies = 
	[
		"https://code.jquery.com/jquery-3.0.0.min.js",
		"/js/toastr.js"
	];

	// this is separate from js dependencies as these MUST be loaded at the bottom 
	public static array $polygonScripts = ["/js/polygon/core.js?t=35"];

	public static array $CSSdependencies = 
	[
		"/css/fontawesome-pro-v5.15.2/css/all.css",
		"/css/toastr.css",
		"/css/polygon.css?t=15"
	];

	public static array $pageConfig = 
	[
		"title" => false,
		"og:site_name" => SITE_CONFIG["site"]["name"],
		"og:url" => "https://polygon.pizzaboxer.xyz",
		"og:description" => "yeah its a website about shapes and squares and triangles and stuff and ummmmm",
		"og:image" => "https://polygon.pizzaboxer.xyz/img/ProjectPolygon.png",
		"includeNav" => true,
		"includeFooter" => true,
		"app-attributes" => ""
	];

	// todo - in retrospect this shouldnt even be a thing and so
	// whatever uses this probably smells so uh should work on that
	static function showStaticNotification($type, $text)
	{
		self::$additionalFooterStuff .= '<script type="text/javascript">$(function(){ toastr["'.$type.'"]("'.$text.'"); });</script>';
	}

	// todo - change this to use the newer polygon.buildmodal function
	static function showStaticModal($options)
	{
		//$body = trim(preg_replace('/\s\s+/', ' ', $body));
		//$body = str_replace('"', '\"', $body);
		self::$additionalFooterStuff .= '<script type="text/javascript">$(function(){ polygon.buildModal('.json_encode($options).'); });</script>';
	}

	static function buildHeader()
	{
		$theme = "light";

		// ideally i should probably have this loaded in from 
		// core.php instead of doing the php query on the fly here
		global $pdo, $announcements, $markdown;
		if(SESSION)
		{
			if(SESSION["adminLevel"]) 
			{
				$pendingAssets = db::run("SELECT COUNT(*) FROM assets WHERE NOT approved AND type != 1")->fetchColumn();
			}

			$theme = SESSION["userInfo"]["theme"];

			if($theme == "dark") self::$CSSdependencies[] = "/css/polygon-dark.css?t=4";
			else if($theme == "2013") self::$CSSdependencies[] = "/css/polygon-2013.css";
			else if($theme == "hitius") self::$CSSdependencies[] = "/css/polygon-hitius.css";
			else if($theme == "2014") 
			{
				self::$CSSdependencies[] = "/css/polygon-2014.css?t=".time();
				self::$JSdependencies[] = "/js/polygon/Navigation2014.js?t=".time();
				self::$pageConfig["app-attributes"] .= " id=\"navContent\"";
			}
		}

		ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="polygon-csrf" content="<?=SESSION?SESSION["csrfToken"]:'false'?>"> 
		<link rel='shortcut icon' type='image/x-icon' href='/img/ProjectPolygon.ico' />
		<title><?=(self::$pageConfig["title"] ? self::$pageConfig["title"] . " - " : "") . SITE_CONFIG["site"]["name"]?></title>
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
		<?php } foreach(self::$JSdependencies as $url){ ?>
		<script type="text/javascript" src="<?=$url?>"></script>
		<?php } ?>
		<script>
			var polygon = {};
			
			polygon.user = 
			{
				<?php if(SESSION) { ?> 
				logged_in: true,
				name: "<?=SESSION["userName"]?>",
				id: <?=SESSION["userId"]?>,
				money: <?=SESSION["currency"]?>,
				<?php } else { ?> 
				logged_in: false,
				<?php } ?> 
			};
		</script>
	</head>
	<?php if($theme == "2014") { ?>
	<body class="layout-2014 mt-5">
		<div class="nav-container no-gutter-ads">
			<div class="navigation" id="navigation" onselectstart="return false;">
				<div class="navigation-container">
					<ul>
						<li>
							<div class="user">
								<div class="menu-item">
									<div class="username"><a href="/user?ID=<?=SESSION["userId"]?>"><?=SESSION["userName"]?></a></div>
								</div>
							</div>
						</li>
	                	<li class="nav2014-my-roblox">
		                	<a class="menu-item" href="/home">
		                    	<span class="icon"></span>Home
		                	</a>
		            	</li>
		            	<li class="nav2014-profile">
		                	<a class="menu-item" href="/user">
		                    	<span class="icon"></span>Profile
		                	</a>
		            	</li>
		            	<!--li class="nav2014-messages">
		                	<a class="menu-item" href="/user">
		                    	<span class="icon"></span>Messages
		                	</a>
		            	</li-->
		            	<li class="nav2014-friends">
		                	<a class="menu-item" href="/friends">
		                    	<span class="icon"></span>Friends
		                    	<span class="notification-icon text-light friend-requests-indicator<?=!SESSION["friendRequests"]?' d-none':''?>"><?=SESSION["friendRequests"]?></span>
		                	</a>
		            	</li>
		            	<li class="nav2014-character">
		                	<a class="menu-item" href="/my/character">
		                    	<span class="icon"></span>Character
		                	</a>
		            	</li>
		            	<li class="nav2014-inventory">
		                	<a class="menu-item" href="/my/stuff">
		                    	<span class="icon"></span>Inventory
		                	</a>
		            	</li>
		            	<li class="nav2014-develop">
		                	<a class="menu-item" href="/develop">
			                    <span class="icon"></span>Develop
			                </a>
			            </li>
			            <li class="nav2014-groups">
			                <a class="menu-item" href="/groups">
			                    <span class="icon"></span>Groups
			                </a>
			            </li>
			            <li class="nav2014-forum">
			                <a class="menu-item" href="/forum">
			                    <span class="icon"></span>Forum
			                </a>
			            </li>
			            <?php if(SESSION["adminLevel"]) { ?>
			            <li class="nav2014-profile">
		                	<a class="menu-item" href="/admin">
		                    	<span class="icon"></span>Admin
		                    	<span class="notification-icon text-light <?=!$pendingAssets?' d-none':''?>"><?=$pendingAssets?></span>
		                	</a>
		            	</li>
			            <?php } ?>
			            <!--li class="nav2014-blog">
			                <a class="menu-item" href="https://web.archive.org/web/20140821165031/http://blog.roblox.com/">
			                    <span class="icon"></span>Blog
			                </a>
			            </li>
		                <li class="upgrade-now">
		                    <a href="/web/20140821165031/http://www.roblox.com/Upgrades/BuildersClubMemberships.aspx" class="nav-button" id="builders-club-button">Upgrade Now</a>
		                </li>
		                            <li class="nav2014-events">
		                    <span class="events-text">Events</span>
		                </li>
						<li class="nav2014-sponsor">
							<a class="menu-item" href="/web/20140821165031/http://www.roblox.com/event/clanbattle" title="Clan Battle">
								<img src="https://web.archive.org/web/20140821165031im_/http://images.rbxcdn.com/6d4be1b1b1e67971c837da0dae72e82d">
							</a>
						</li-->
		        	</ul>
			    </div>
			</div>
		    <nav class="navbar navbar-expand-lg navbar-dark navbar-orange header-2014 fixed-top py-0">
		    	<div class="nav-icon" onselectstart="return false;"></div>
				<a class="navbar-brand pt-0" href="/"><img src="/img/2013/roblox_logo.png"></a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				    <span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="primaryNavbar">
				    <ul class="navbar-nav header-links mr-auto">
				    	<li class="nav-item">
				        	<a class="nav-link" href="/games">Games</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/catalog">Catalog</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/develop">Develop</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/forum">Forum</a>
				     	</li>
				     	<div class="search">
            				<div class="search-input-container">
								<input type="text" placeholder="Search">
            				</div>
            				<div class="search-icon"></div>
            				<div class="universal-search-dropdown">
                        		<div class="universal-search-option" data-searchurl="/games?Keyword=">
                            		<div class="universal-search-text">Search <span class="universal-search-string"></span> in Games</div>
								</div>
								<div class="universal-search-option" data-searchurl="/browse?Category=People&SearchTextBox=">
									<div class="universal-search-text">Search <span class="universal-search-string"></span> in People</div>
								</div>
								<div class="universal-search-option selected" data-searchurl="/browse?Category=Groups&SearchTextBox=">
                    				<div class="universal-search-text">Search <span class="universal-search-string"></span> in Groups</div>
                				</div>
								<div class="universal-search-option" data-searchurl="/catalog?Keyword=">
									<div class="universal-search-text">Search <span class="universal-search-string"></span> in Catalog</div>
								</div>
								<div class="universal-search-option" data-searchurl="/catalog?CurrencyType=0&SortType=1&Category=6&Keyword=">
									<div class="universal-search-text">Search <span class="universal-search-string"></span> in Library</div>
								</div>
							</div>
						</div>
					</ul>
				    <div class="navbar-nav">
				    	<div class="navbar-button-container">
				    		<a class="btn btn-sm mr-4" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?=SESSION["currency"]?> <?=SITE_CONFIG["site"]["currency"]?> <br> Next stipend in <?=GetReadableTime(SESSION["nextCurrencyStipend"], ["Full" => true, "Ending" => false, "Abbreviate" => true])?>" href="/my/money"><i class="fas fa-pizza-slice mr-1"></i> <?=SESSION["currency"]?></a>
				    	</div>
				    	<div class="navbar-button-container dropdown-hover">
				    		<a class="btn btn-sm" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog"></i></a>
							<div class="dropdown-menu dropdown-menu-right mx-2" aria-labelledby="settings-dropdown">
								<a class="dropdown-item" href="/my/account">Settings</a>
								<a class="dropdown-item" href="/logout">Logout</a>
							</div>
				    	</div>
				    </div>
				</div>
			</nav>
		</div>
		<noscript>
			<div id="ctl00_Announcement">
				<div id="ctl00_SystemAlertDiv" class="SystemAlert" style="background-color:red">
					<div id="ctl00_SystemAlertTextColor" class="SystemAlertText">
						<div id="ctl00_LabelAnnouncement">Please enable Javascript to use all the features on this site.</div>
					</div>
				</div>
			</div>
		</noscript>
		<?php foreach($announcements as $announcement) { ?>
		<div id="ctl00_Announcement">
			<div id="ctl00_SystemAlertDiv" class="SystemAlert" style="background-color:<?=$announcement["bgcolor"]?>">
				<div id="ctl00_SystemAlertTextColor" class="SystemAlertText">
					<div id="ctl00_LabelAnnouncement"><?=$markdown->line($announcement["text"])?></div>
				</div>
			</div>
		</div>
		<?php } /* foreach($announcements as $announcement) */ ?>
		<div class="app container py-4 nav-content"<?=self::$pageConfig['app-attributes']?>>
	<?php } else { ?>
	<body>
		<?php if(self::$pageConfig["includeNav"]) { ?>	
		<nav class="navbar navbar-expand-lg navbar-dark navbar-orange navbar-top py-0">	
			<div class="container">
				<a class="navbar-brand" href="/"><?=SITE_CONFIG["site"]["name"]?></a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				    <span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="primaryNavbar">
				    <ul class="navbar-nav header-links mr-auto">
				     	<?php if(SESSION) { ?>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/games">Games</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/catalog">Catalog</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/develop">Develop</a>
				     	</li>
				     	<?php } ?>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/forum">Forum</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link" href="/browse">People</a>
				     	</li>
				     	<?php if(SESSION) { ?>
				     	<li class="nav-item dropdown">
					        <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</a>
					        <div class="dropdown-menu mt-0" aria-labelledby="moreDropdown">
					          <!--a class="dropdown-item" href="/browse">People</a>
					          <div class="dropdown-divider"></div-->
					          <a class="dropdown-item" href="https://discord.com/invite/ZXbhxcQfUW">Discord</a>
					          <!--a class="dropdown-item" href="https://twitter.com/boxerpizza">Twitter</a-->
					        </div>
					    </li>
						<?php } ?>
				    </ul>
				    <div class="navbar-nav">
						<?php if(SESSION) { ?>	
				        <a class="nav-link mr-2" href="/user?ID=<?=SESSION["userId"]?>"><?=SESSION["userName"]?></a>
				        <?php if (Users::IsAdmin()) { ?>
						<div class="navbar-button-container">
							<a class="btn btn-sm btn-light py-0 px-1 unread-messages-indicator<?=!SESSION["unreadMessages"]?' d-none':''?>"><?=SESSION["unreadMessages"]?></a>
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" title="Messages" href="/my/messages" data-toggle="tooltip" data-html="true" data-placement="bottom">
								<i class="fas fa-envelope"></i>
				    		</a>
				    	</div>
				    	<?php } ?>
				       	<div class="navbar-button-container">
				    		<a class="btn btn-sm btn-light py-0 px-1 friend-requests-indicator<?=!SESSION["friendRequests"]?' d-none':''?>"><?=SESSION["friendRequests"]?></a>
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" title="My Friends" href="/friends" data-toggle="tooltip" data-html="true" data-placement="bottom">
				    			<i class="fas fa-user-friends"></i>
				    		</a>
				    	</div>
				    	<div class="navbar-button-container">
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?=SESSION["currency"]?> <?=SITE_CONFIG["site"]["currency"]?> <br> Next stipend in <?=GetReadableTime(SESSION["nextCurrencyStipend"], ["Full" => true, "Ending" => false, "Abbreviate" => true])?>" href="/my/money"><i class="fal fa-pizza-slice"></i> <?=SESSION["currency"]?></a>
				    	</div>
				    	<div class="navbar-button-container">
				        	<a class="btn btn-sm btn-light my-1 mr-2 px-3" href="/logout">Logout</a>
				        </div>
						<?php } else { ?>
				        <a class="nav-link" href="/">Sign Up</a>
				     	<span class="nav-link darken px-0">or</span>
				        <a class="btn btn-sm btn-light my-1 mx-2 px-4" href="/login">Login</a>
						<?php } ?>
				    </div>
				</div>
			</div>
		</nav>
		<?php if(SESSION) { ?>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-top bg-dark py-0">
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
				        	<a class="nav-link py-1" href="/my/character">Character</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/friends">Friends</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/groups">Groups</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/my/stuff">Inventory</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/my/money">Money</a>
				     	</li>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/my/account">Account</a>
				     	</li>
						<?php if(SESSION && SESSION["adminLevel"]) { ?>
				     	<li class="nav-item">
				        	<a class="nav-link py-1" href="/admin">Admin <span class="btn btn-sm btn-outline-light py-0<?=!$pendingAssets?' d-none':''?>" style="margin-top:-3px"><?=$pendingAssets?></span></a>
				     	</li>
						<?php } ?>
				    </ul>
				</div>
			</div>
		</nav>
		<?php } /* if(SESSION) */ ?>
		</div>
		<noscript>
		  <div class="alert py-2 mb-0 rounded-0 text-center text-light bg-danger" role="alert">
			disabling javascript breaks the ux in half so dont do it pls
		  </div>
		</noscript>
		<?php foreach($announcements as $announcement) { ?>
		<div class="alert py-2 mb-0 rounded-0 text-center text-<?=$announcement["textcolor"]?>" role="alert" style="background-color: <?=$announcement["bgcolor"]?>">
		  <?=$markdown->text($announcement["text"])?>
		</div>
		<?php } /* foreach($announcements as $announcement) */ ?>
		<?php } /* if(self::$pageConfig["includeNav"]) */ ?>
		<div class="app container py-4"<?=self::$pageConfig['app-attributes']?>>
		<?php } /* if($theme == "2014") */ ?>
		<?php } static function buildFooter() { ?>
		</div>
		<?php if(self::$pageConfig["includeFooter"]) { ?>
		<nav class="footer navbar navbar-light navbar-orange">
			<div class="container py-2 text-light text-center">
				<!--div class="row" style="width:100%">
					<div class="col-sm-3 text-center pt-1" style="border-right: 1px solid #ccc;">
						<h3 class="font-weight-normal"><?=SITE_CONFIG["site"]["name"]?></h3>
					</div>
					<div class="col-sm-9 pl-4">
						<p class="font-weight-normal mb-0 pl-2">made by pizzaboxer</p>
						<p class="font-weight-normal mb-0 pl-2">copyright © <?=SITE_CONFIG["site"]["name"]?> 2020</p>
					</div>
				</div-->
				<div class="mx-auto">
					<span><small class="px-2">Copyright © <?=SITE_CONFIG["site"]["name"]?> 2020-<?=date('Y')?></small> | <small class="px-2"><?=db::run("SELECT COUNT(*)+1 FROM users")->fetchColumn()?> users registered</small> | <a href="/info/privacy" class="text-light px-2">Privacy Policy</a> | <a href="/info/terms-of-service" class="text-light px-2">Terms of Service</a></span>
				</div>
			</div>
		</nav>
		<?php } ?>
		<div class="global modal fade" tabindex="-1" role="dialog" aria-labelledby="primaryModalCenter" aria-hidden="true">
		  	<div class="modal-dialog modal-dialog-centered" role="document">
		    	<div class="modal-content">
		      		<div class="modal-header card-header bg-cardpanel py-2">
		        		<h3 class="col-12 modal-title text-center font-weight-normal"></h3>
		      		</div>
		      		<div class="modal-body text-center text-break">
		      				your smell
		      		</div>
		      		<div class="modal-footer text-center">
		      			<div class="mx-auto">
		      			</div>
		      		</div>
		    	</div>
		  	</div>
		</div>
		<?php if(SESSION) { ?>
		<div class="placelauncher modal" tabindex="-1" role="dialog">
		  	<div class="modal-dialog modal-dialog-centered" role="document">
		    	<div class="modal-content"></div>
		  	</div>
		  	<div class="launch template d-none">
		      	<div class="modal-body text-center">
		      		<span class="jumbo spinner-border text-danger mb-3" role="status"></span>
		        	<h5 class="font-weight-normal mb-3">Starting <?=SITE_CONFIG["site"]["name"]?>...</h5>
		        	<a class="btn btn-sm btn-outline-danger btn-block px-4" data-dismiss="modal">Cancel</a>
		      	</div>
		  	</div>
		  	<div class="install template d-none">
		      	<div class="modal-body text-center pb-0">
		      		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        	<img src="/img/ProjectPolygon.png" class="img-fluid pl-3 py-3 pr-1" style="max-width: 150px">
		        	<h2 class="font-weight-normal">Welcome to <?=SITE_CONFIG["site"]["name"]?>!</h2>
		        	<h5 class="font-weight-normal">Seems like you don't have <span class="year">2010</span> installed</h5>
		        	<a class="btn btn-success btn-block mx-auto mt-3 install" style="max-width:18rem">Download</a>
		      	</div>
		      	<div class="modal-footer text-center py-2">
		      		<small class="mx-auto">If you do have the client installed, just ignore this</small>
		      	</div>
		  	</div>
		</div>
		<?php } ?>
		<script src="/js/bootstrap.bundle.min.js"></script>
		<?php if(SESSION && SESSION["adminLevel"]){ ?>
		<script>
		//admin.js
		if (polygon.admin == undefined) polygon.admin = {};

		polygon.admin.forum = 
		{
			moderate_post_prompt: function(type, id)
			{
				polygon.buildModal({ 
					header: "Delete Post", 
					body: 'Are you sure you want to delete this post?', 
					buttons: [{class:'btn btn-danger px-4 post-delete-confirm', attributes:[{attr:'data-type', val:type}, {attr: 'data-id', val:id}], dismiss:true, text:'Yes'}, {class:'btn btn-secondary px-4', dismiss:true, text:'No'}]
				});
			},

			moderate_post: function(type, id)
			{
				$.post('/api/admin/delete-post', {"postType": type, "postId": id}, function(data)
				{
					if(data.success)
					{
						toastr["success"]("Post has been deleted");
						setTimeout(function(){ window.location.reload(); }, 3);
					}
					else
					{
						toastr["error"](data.message);
					}
				});
			}
		}

		polygon.admin.gitpull = function()
		{
			polygon.buildModal({
				header: "<i class=\"fab fa-git-alt text-danger\"></i> Git Pull",
				body: "<span class=\"spinner-border spinner-border-sm text-danger\" role=\"status\" aria-hidden=\"true\"></span> Executing Git Pull...",
				buttons:
				[
					{class: 'btn btn-outline-primary', dismiss: true, text: 'Close'},
					{class: 'btn btn-outline-danger disabled', attributes: [{attr: "disabled", val: "disabled"}], text: 'Run Again'}
				]
			});

			$.get("/api/admin/git-pull", function(data)
			{
				polygon.buildModal({
					header: "<i class=\"fab fa-git-alt text-danger\"></i> Git Pull",
					body: "<pre class=\"mb-0\">"+data+"</pre>",
					buttons:
					[
						{class: 'btn btn-outline-primary', dismiss: true, text: 'Close'},
						{class: 'btn btn-outline-success gitpull', text: 'Run Again'}
					]
				});
			});
		}

		$("body").on("click", ".gitpull", polygon.admin.gitpull);

		$("body").keydown(function(event) 
		{
			if (event.originalEvent.ctrlKey && event.originalEvent.key == "/") polygon.admin.gitpull();
		});

		polygon.admin.request_render = function(type, id)
		{
			$.post('/api/admin/request-render', {"renderType": type, "assetID": id}, function(data)
			{
				if(data.success) toastr["success"](data.message);
				else toastr["error"](data.message);
			});
		}
		
		$("body").on("click", ".post-delete", function(){ polygon.admin.forum.moderate_post_prompt($(this).attr("data-type"), $(this).attr("data-id")); });
		$("body").on("click", ".post-delete-confirm", function(){ polygon.admin.forum.moderate_post($(this).attr("data-type"), $(this).attr("data-id")); });
		$("body").on("click", ".request-render", function(){ polygon.admin.request_render($(this).attr("data-type"), $(this).attr("data-id")); });
		</script>
		<?php } ?>
		<?php foreach(self::$polygonScripts as $url){ ?>
		<script type="text/javascript" src="<?=$url?>"></script>
		<?php } ?>
		<?=self::$additionalFooterStuff?> 
	</body>
</html>
<?php 
		ob_end_flush();
	}

	static function errorCode($code, $customText = false)
	{
		http_response_code($code);
		
		$text = 
		[
			400 => ["title" => "Bad request", "text" => "There was a problem with your request"],
			404 => ["title" => "Requested page not found", "text" => "You may have clicked an expired link or mistyped the address"],
			420 => ["title" => "Website is currently under maintenance", "text" => "check back later"],
			500 => ["title" => "Unexpected error with your request", "text" => "Please try again after a few moments"]
		];

		if(!isset($text[$code])) $code = 500;
		
		if (is_array($customText) && count($customText)) $text[$code] = $customText;

		self::buildHeader();
	?>
	<div class="card mx-auto" style="max-width:640px;">
	  <div class="card-body text-center">
	  	<img src="/img/error.png">
	    <h2 class="font-weight-normal"><?=$text[$code]["title"]?></h2>
	    <?=$text[$code]["text"]?>
<?php if (!is_array($customText) && !empty($customText) && $code == 500) { ?>
		<pre class="mt-4"><?=$customText?></pre>
<?php } ?>
	    <hr>
	    <a class="btn btn-outline-primary mx-1 mt-1 py-1" onclick="window.history.back()">Go to Previous Page</a> 
	    <a class="btn btn-outline-primary mx-1 mt-1 py-1" href="/">Return Home</a>
	  </div>
	</div>
	<?php
		self::buildFooter();
		die();
	}
}