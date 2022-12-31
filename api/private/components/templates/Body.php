	<body>
<?php if (self::$Config["ShowNavbar"]) { ?>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-orange navbar-top py-0">
			<div class="container">
				<a class="navbar-brand" href="/"><?=SITE_CONFIG["site"]["name"]?></a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="primaryNavbar">
					<ul class="navbar-nav header-links mr-auto">
<?php if (SESSION) { ?>
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
<?php if (SESSION) { ?>
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</a>
							<div class="dropdown-menu mt-0" aria-labelledby="moreDropdown">
								<!--a class="dropdown-item" href="/browse">People</a>
									<div class="dropdown-divider"></div-->
								<a class="dropdown-item" href="/discord">Discord</a>
								<!--a class="dropdown-item" href="https://twitter.com/boxerpizza">Twitter</a-->
							</div>
						</li>
<?php } ?>
					</ul>
					<div class="navbar-nav">
<?php if (SESSION) { ?>
						<a class="nav-link mr-2" href="/user?ID=<?=SESSION["user"]["id"]?>"><?=SESSION["user"]["username"]?></a>
<?php if (Users::IsAdmin()) { ?>
						<div class="navbar-button-container">
							<a class="btn btn-sm btn-light py-0 px-1 unread-messages-indicator<?=!SESSION["unreadMessages"]?' d-none':''?>"><?=SESSION["unreadMessages"]?></a>
							<a class="btn btn-sm btn-outline-light my-1 mr-2" title="Messages" href="/my/messages" data-toggle="tooltip" data-html="true" data-placement="bottom">
							<i class="fas fa-envelope"></i>
							</a>
						</div>
<?php } /* endif (Users::isAdmin()) */ ?>
						<div class="navbar-button-container">
							<a class="btn btn-sm btn-light py-0 px-1 friend-requests-indicator<?=!SESSION["friendRequests"]?' d-none':''?>"><?=SESSION["friendRequests"]?></a>
							<a class="btn btn-sm btn-outline-light my-1 mr-2" title="My Friends" href="/friends" data-toggle="tooltip" data-html="true" data-placement="bottom">
							<i class="fas fa-user-friends"></i>
							</a>
						</div>
						<div class="navbar-button-container">
							<a class="btn btn-sm btn-outline-light my-1 mr-2" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?=SESSION["user"]["currency"]?> <?=SITE_CONFIG["site"]["currency"]?> <br> Next stipend in <?=GetReadableTime(SESSION["user"]["nextCurrencyStipend"], ["Full" => true, "Ending" => false, "Abbreviate" => true])?>" href="/my/money"><i class="fal fa-pizza-slice"></i> <?=SESSION["user"]["currency"]?></a>
						</div>
						<div class="navbar-button-container">
							<a class="btn btn-sm btn-light my-1 mr-2 px-3" href="/logout">Logout</a>
						</div>
<?php } else { ?>
						<a class="nav-link" href="/">Sign Up</a>
						<span class="nav-link darken px-0">or</span>
						<a class="btn btn-sm btn-light my-1 mx-2 px-4" href="/login">Login</a>
<?php } /* endif (SESSION) */ ?>
					</div>
				</div>
			</div>
		</nav>
<?php if (SESSION) { ?>
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
<?php if (SESSION && SESSION["user"]["adminlevel"]) { ?>
						<li class="nav-item">
							<a class="nav-link py-1" href="/admin">Admin <span class="btn btn-sm btn-outline-light py-0<?=self::$TemplateVariables["PendingAssets"] == 0 ? ' d-none' : ''?>" style="margin-top:-3px"><?=self::$TemplateVariables["PendingAssets"]?></span></a>
						</li>
<?php } ?>
					</ul>
				</div>
			</div>
		</nav>
<?php } /* endif (SESSION) */ ?>
		<noscript>
			<div class="alert py-2 mb-0 rounded-0 text-center text-light bg-danger" role="alert">
				disabling javascript breaks the ux in half so dont do it pls
			</div>
		</noscript>
<?php foreach (self::$TemplateVariables["Announcements"] as $Announcement) { ?>
		<div class="alert py-2 mb-0 rounded-0 text-center text-<?=$Announcement["textcolor"]?>" role="alert" style="background-color: <?=$Announcement["bgcolor"]?>">
			<?=self::$TemplateVariables["Markdown"]->text($Announcement["text"])?>
		</div>
<?php } /* foreach ($announcements as $announcement) */ ?>
<?php } /* endif (self::$Config["ShowNavbar"]) */ ?>
		<div<?php foreach(self::$Config['AppAttributes'] as $Attribute => $Value) echo " {$Attribute}=\"{$Value}\""; ?>>