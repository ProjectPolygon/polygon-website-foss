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
	// when core.js is moved to its own file instead of being plopped directly into
	// the html, this wont be necessary
	public static array $polygonScripts = [];

	public static array $CSSdependencies = 
	[
		"/css/fontawesome-pro-v5.15.2/css/all.css",
		"/css/toastr.css",
		"/css/polygon.css?t=4"
	];

	public static array $pageConfig = 
	[
		"title" => false,
		"og:site_name" => SITE_CONFIG["site"]["name"],
		"og:url" => "https://polygon.pizzaboxer.xyz",
		"og:description" => "yeah its a website about shapes and squares and triangles and stuff and ummmmm",
		"og:image" => "https://chef.pizzaboxer.xyz/img/ProjectPolygon.png",
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
		// ideally i should probably have this loaded in from 
		// core.php instead of doing the php query on the fly here
		global $pdo, $announcements, $markdown;
		if(SESSION && SESSION["adminLevel"]) $pendingAssets = db::run("SELECT COUNT(*) FROM assets WHERE NOT approved AND type != 1")->fetchColumn();
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
		<?php if(SESSION && SESSION["userInfo"]["theme"] == "dark") { ?>
		<link rel="stylesheet" href="/css/polygon-dark.css?t=<?=time()?>">
		<?php } ?>
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
				     	<li class="nav-item">
				        	<a class="nav-link" href="/browse">People</a>
				     	</li>
				     	<!--li class="nav-item dropdown">
					        <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</a>
					        <div class="dropdown-menu mt-0" aria-labelledby="moreDropdown">
					          <a class="dropdown-item" href="/browse">People</a>
					          <div class="dropdown-divider"></div>
					          <?php if(SESSION) { ?><a class="dropdown-item" href="/discord">Discord</a><?php } ?>
					          <a class="dropdown-item" href="https://twitter.com/boxerpizza">Twitter</a>
					        </div>
					    </li-->
				    </ul>
				    <div class="navbar-nav">
<?php if(SESSION) { ?>
				        <a class="nav-link mr-2" href="/user?ID=<?=SESSION["userId"]?>"><?=SESSION["userName"]?></a>
				       	<div class="navbar-button-container">
				    		<a class="btn btn-sm btn-light py-0 px-1 friend-requests-indicator<?=!SESSION["friendRequests"]?' d-none':''?>"><?=SESSION["friendRequests"]?></a>
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" title="My Friends" href="/friends" data-toggle="tooltip" data-html="true" data-placement="bottom">
				    			<i class="fas fa-user-friends"></i>
				    		</a>
				    	</div>
				    	<div class="navbar-button-container">
				    		<a class="btn btn-sm btn-outline-light my-1 mr-2" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?=SESSION["currency"]?> <?=SITE_CONFIG["site"]["currency"]?> <br> Next stipend in <?=timeSince("@".SESSION["nextCurrencyStipend"], true, false, false, true)?>" href="/my/money"><i class="fal fa-pizza-slice"></i> <?=SESSION["currency"]?></a>
				    	</div>
				    	<div class="navbar-button-container">
				        	<a class="btn btn-sm btn-light my-1 mr-2 px-3" href="/logout">Logout</a>
				        </div>
<?php } else { ?>
				        <a class="nav-link" href="/register">Sign Up</a>
				     	<a class="nav-link darken px-0">or</a>
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
<?php } foreach($announcements as $announcement){ ?>
		<div class="alert py-2 mb-0 rounded-0 text-center text-<?=$announcement["textcolor"]?>" role="alert" style="background-color: <?=$announcement["bgcolor"]?>">
		  <?=$markdown->text($announcement["text"])?>
		</div>
<?php } } ?>
		<noscript>
		  <div class="alert py-2 mb-0 rounded-0 text-center text-light bg-danger" role="alert">
			disabling javascript breaks the ux in half so dont do it pls
		  </div>
		</noscript>
		<div class="app container py-4"<?=self::$pageConfig['app-attributes']?>>
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
					<span><small class="px-2">Copyright © <?=SITE_CONFIG["site"]["name"]?> 2020-<?=date('Y')?></small> | <small class="px-2"><?=db::run("SELECT COUNT(*) FROM users")->fetchColumn()?> users registered</small> | <a href="/info/privacy" class="text-light px-2">Privacy Policy</a> | <a href="/info/terms-of-service" class="text-light px-2">Terms of Service</a></span>
				</div>
			</div>
		</nav>
<?php } ?>
		<div class="global modal fade" tabindex="-1" role="dialog" aria-labelledby="primaryModalCenter" aria-hidden="true">
		  	<div class="modal-dialog modal-dialog-centered" role="document">
		    	<div class="modal-content">
		      		<div class="modal-header card-header py-2">
		        		<h3 class="col-12 modal-title text-center font-weight-normal" id="primaryModalTitle"></h3>
		      			</div>
		      			<div class="modal-body text-center text-break" style="white-space: pre-line">
		      				your smell
		      			</div>
		      			<div class="modal-footer text-center">
		      			<div class="mx-auto">
		      			</div>
		      		</div>
		    	</div>
		  	</div>
		</div>
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
		        	<h5 class="font-weight-normal">Seems like you don't have the <span class="year">2010</span> client installed</h5>
		        	<a class="btn btn-success btn-block mx-auto mt-3 install" style="max-width:18rem">Download Client</a>
		      	</div>
		      	<div class="modal-footer text-center py-2">
		      		<small class="mx-auto">If you do have the client installed, just ignore this</small>
		      	</div>
		  	</div>
		</div>
		<script src="/js/bootstrap.bundle.min.js"></script>
		<script>
		  //core.js

		  $.ajaxSetup({ headers: { 'x-polygon-csrf': $('meta[name="polygon-csrf"]').attr('content') } });

		  /* todo - dont be lazy and work on this
		  polygon.ajax = function(url, method, data, trusted, successCallback, errorCallback)
		  {
		  	var ajaxOptions = {type: method, data: data};

		  	if(trusted)
		  	{
		  		ajaxOptions.url = window.location.origin + url;
		  		ajaxOptions.headers: {'x-polygon-csrf': $('meta[name="polygon-csrf"]').attr('content')};
		  	}
		  	else { ajaxOptions.url = url; }


		  }*/

		  polygon.button = 
		  {
		  	busy: function(button)
		  	{
		  		$(button).attr("disabled", "disabled").find(".spinner-border").removeClass("d-none");
		  	},
		  	active: function(button)
		  	{
		  		$(button).removeAttr("disabled").find(".spinner-border").addClass("d-none");
		  	}
		  };

		  polygon.insertAlert = function(options)
		  {
		  	var alertCode = '';
		  	if(options.alertClasses == undefined) options.alertClasses = '';

		  	if(options.parentClasses) alertCode += '<div class="'+options.parentClasses+'">';
		  	alertCode += '<div class="alert alert-danger '+options.alertClasses+' px-2 py-1" style="width: fit-content;" role="alert">'+options.text+'</div>';
		  	if(options.parentClasses) alertCode += '</div>';

		  	$(options.parent).append(alertCode);
		  }

		  polygon.buildModal = function(options)
		  { 
		  	if(options.options == undefined) options.options = "show";
		  	if(options.fade == undefined) $(".global.modal").addClass("fade");
		  	else if(!options.fade) $(".global.modal").removeClass("fade");
		  	var footer = $(".global.modal .modal-footer .mx-auto");
		  	$(".global.modal .modal-title").html(options.header);
		  	$(".global.modal .modal-body").html(options.body);

		  	footer.empty();
		  	$.each(options.buttons, function(_, button)
		  	{
		  		var buttonCode = '<button type="button" class="'+button.class+' text-center mx-1"';
		  		// todo - improve how attributes are handled
		  		// right now its like {"attr": "data-whatever", "val": 1} instead of just being like {"data-whatever": 1}
		  		if(button.attributes != undefined) $.each(button.attributes, function(_, attr){ buttonCode += ' '+attr.attr+'="'+attr.val+'"'; });
		  		if(button.dismiss) buttonCode += ' data-dismiss="modal"';
		  		buttonCode += '><h4 class="font-weight-normal pb-0">'+button.text+'</h4></button>';
		  		footer.append(buttonCode);
		  	});

		  	if(options.footer) footer.append('<p class="text-muted mt-3 mb-0">'+options.footer+'</p>');

		  	$(".global.modal").modal(options.options);
		  };

		  polygon.populate = function(data, template, container)
		  {
		  	$.each(data, function(_, item)
			{
				var templateCode = $(template).clone();
				templateCode.html(function(_, html)
				{ 
					console.log(html);
					// todo - this isnt very flexible
					for (let key in item) html = html.replace(new RegExp("\\$"+key, "g"), item[key]);
					return html;
				});
				if(templateCode.find("img").attr("preload-src")) 
					templateCode.find("img").attr("src", templateCode.find("img").attr("preload-src"));
				templateCode.appendTo(container);
			});
		  }

		  polygon.populateControl = function(control, data)
		  {
		  	return polygon.populate(data, "."+control+"-container .template .item", "."+control+"-container .items");
		  }

		  polygon.pagination = 
		  {
		  	register: function(control, callback)
			{
				var pagination = "."+control+"-container .pagination";

				if(!$(pagination).length) return;

				$(pagination+" .back").click(function(){ callback(+$(pagination+" .page").val()-1); });
				$(pagination+" .next").click(function(){ callback(+$(pagination+" .page").val()+1); });

				$(pagination+" .page").on("focusout keypress", this, function(event)
				{ 
					if(event.type == "keypress") if(event.which == 13) $(this).blur(); else return;
					if($(this).val() == $(this).attr("data-last-page")) return;
					$(this).attr("data-last-page", $(this).val());
					callback($(this).val());
				});
			},

			handle: function(control, page, pages)
			{
				var pagination = "."+control+"-container .pagination";

				if(!$(pagination).length) return;
				if(pages <= 1 || pages == undefined) return $(pagination).addClass("d-none");

				$(pagination).removeClass("d-none");
				$(pagination+" .pages").text(pages);

				if($(pagination+" .page").prop("tagName") == "INPUT") $(pagination+" .page").val(page);
				else $(pagination+" .page").text(page);

				if(page <= 1) $(pagination+" .back").attr("disabled", "disabled");
				else $(pagination+" .back").removeAttr("disabled");

				if(page >= pages) $(pagination+" .next").attr("disabled", "disabled");
				else $(pagination+" .next").removeAttr("disabled");
			}
		  }

		  toastr.options = 
		  {
			  "closeButton": false,
			  "debug": false,
			  "newestOnTop": false,
			  "progressBar": true,
			  "positionClass": "toast-top-right",
			  "preventDuplicates": false,
			  "onclick": null,
			  "showDuration": "300",
			  "hideDuration": "1000",
			  "timeOut": "10000",
			  "extendedTimeOut": "1000",
			  "showEasing": "swing",
			  "hideEasing": "linear",
			  "showMethod": "fadeIn",
			  "hideMethod": "fadeOut"
			}

		  $(function()
		  {
		  	if(polygon.user.logged_in)
		  	{ 
		  		setInterval(function()
		  		{ 
		  			if(document.hidden) return;
		  			$.post("/api/account/ping", function(data)
		  			{  
		  				if(data.friendRequests) $(".friend-requests-indicator").text(data.friendRequests).removeClass("d-none");
						else $(".friend-requests-indicator").addClass("d-none");
						if(data.status == 401) window.location.reload();
		  			}); 
		  		}, 30000); 
		  	}
		  	$("[data-toggle='tooltip']").tooltip();
		  });
		</script>
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
<?php }

	static function errorCode($code)
	{
		http_response_code($code);
		$text = 
		[
			400 => ["title" => "Bad request", "text" => "There was a problem with your request"],
			404 => ["title" => "Requested page not found", "text" => "You may have clicked an expired link or mistyped the address"],
			420 => ["title" => "Website is currently under maintenance", "text" => "check back later"],
			500 => ["title" => "Unexpected error with your request", "text" => "Please try again after a few moments"]
		];
		self::buildHeader();
	?>
	<div class="card mx-auto" style="max-width:640px;">
	  <div class="card-body text-center">
	  	<img src="/img/error.png">
	    <h2 class="font-weight-normal"><?=$text[$code]["title"]?></h2>
	    <?=$text[$code]["text"]?>
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