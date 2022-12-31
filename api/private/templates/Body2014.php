<?php if ($this->config["ShowNavbar"]) { ?>
	<body class="layout-2014 mt-5">
		<div class="nav-container no-gutter-ads">
			<div class="navigation" id="navigation" onselectstart="return false;">
				<div class="navigation-container">
					<ul>
						<li>
							<div class="user">
								<div class="menu-item">
									<div class="username"><a href="/user?ID=<?= SESSION["user"]["id"] ?>"><?= SESSION["user"]["username"] ?></a></div>
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
							<span class="notification-icon text-light friend-requests-indicator<?= !SESSION["user"]["PendingFriendRequests"]?' d-none':'' ?>"><?= SESSION["user"]["PendingFriendRequests"] ?></span>
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
						<li class="nav2014-groups">
							<a class="menu-item" href="/my/invites">
							<span class="icon"></span>Invitations
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
<?php if (SESSION["user"]["adminlevel"]) { ?>
						<li class="nav2014-profile">
							<a class="menu-item" href="/admin">
							<span class="icon"></span>Admin
							<span class="notification-icon text-light <?= $this->templateVariables["PendingAssets"] == 0 ? ' d-none' : '' ?>"><?= $this->templateVariables["PendingAssets"] ?></span>
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
							<a class="btn btn-sm mr-4" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?= SESSION["user"]["currency"] ?> <?= SITE_CONFIG["site"]["currency"] ?> <br> Next stipend in <?= GetReadableTime(SESSION["user"]["nextCurrencyStipend"], ["Full" => true, "Ending" => false, "Abbreviate" => true]) ?>" href="/my/money"><i class="fas fa-pizza-slice mr-1"></i> <?= SESSION["user"]["currency"] ?></a>
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
<?php foreach ($this->templateVariables["Announcements"] as $announcement) { ?>
		<div id="ctl00_Announcement">
			<div id="ctl00_SystemAlertDiv" class="SystemAlert" style="background-color:<?= $announcement["bgcolor"] ?>">
				<div id="ctl00_SystemAlertTextColor" class="SystemAlertText">
					<div id="ctl00_LabelAnnouncement"><?= $this->templateVariables["Markdown"]->line($announcement["text"]) ?></div>
				</div>
			</div>
		</div>
<?php } /* foreach($announcements as $announcement) */ ?>
<?php } else { /* ($this->config["ShowNavbar"]) */ ?>
	<body class="layout-2014">
<?php } /* ($this->config["ShowNavbar"]) */ ?>
		<div<?php foreach($this->appAttributes as $attribute => $value) echo " {$attribute}=\"{$value}\""; ?>>