		</div>
<?php if ($this->config["ShowFooter"]) { ?>
		<footer>
			<div class="container text-center py-2">
				<hr>
				<div class="row mt-4">
					<div class="col-xl-6 col-lg-4 text-lg-left">
<?php if (SESSION) { ?>						
						<p><a href="/info/terms-of-service" class="px-2" style="color:inherit">Terms of Service</a> | <a href="/info/privacy" class="px-2" style="color:inherit">Privacy Policy</a> | <a href="/discord" class="px-2" style="color:inherit">Discord</a></p>
<?php } else { ?>
						<p><a href="/info/terms-of-service" class="px-2" style="color:inherit">Terms of Service</a> | <a href="/info/privacy" class="px-2" style="color:inherit">Privacy Policy</a></p>
<?php } ?>
					</div>
					<div class="col-xl-6 col-lg-8 text-lg-right">
						<p><small>© <?= date("Y") ?> Project Polygon. We are in no way associated with Roblox Corporation.</small></p>
					</div>
				</div>
			</div>
		</footer>
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
<?php if (SESSION) { ?>
		<div class="placelauncher modal" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content"></div>
			</div>
			<div class="launch template d-none">
				<div class="modal-body text-center">
					<span class="jumbo spinner-border text-danger mb-3" role="status"></span>
					<h5 class="font-weight-normal mb-3">Starting <?= SITE_CONFIG["site"]["name"] ?>...</h5>
					<a class="btn btn-sm btn-outline-danger btn-block px-4 cancel-join" data-dismiss="modal">Cancel</a>
				</div>
			</div>
			<div class="install template d-none">
				<div class="modal-body text-center pb-0">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<img src="/img/ProjectPolygon.png" class="img-fluid pl-3 py-3 pr-1" style="max-width: 150px">
					<h2 class="font-weight-normal">Welcome to <?= SITE_CONFIG["site"]["name"] ?>!</h2>
					<h5 class="font-weight-normal">Seems like you don't have <span class="year">2010</span> installed</h5>
					<a class="btn btn-success btn-block mx-auto mt-3 install" style="max-width:18rem">Download</a>
				</div>
				<div class="modal-footer text-center py-2">
					<small class="mx-auto">If you do have the client installed, just ignore this</small>
				</div>
			</div>
		</div>
<?php } // endif (SESSION) ?>
		<script src="/js/bootstrap.bundle.min.js"></script>
<?php if (SESSION && SESSION["user"]["adminlevel"]) { ?>
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
						buttons: [{class:'btn btn-danger px-4 post-delete-confirm', attributes:{'data-type':type, 'data-id':id}, dismiss:true, text:'Yes'}, {class:'btn btn-secondary px-4', dismiss:true, text:'No'}]
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
						{class: 'btn btn-outline-danger disabled', attributes: {"disabled":"disabled"}, text: 'Run Again'}
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
<?php } /* endif (SESSION && SESSION["user"]["adminlevel"]) */ ?>
<?php foreach ($this->polygonScripts as $url) { ?>
		<script type="text/javascript" src="<?= $url ?>"></script>
<?php } ?>
		<?=$this->footerAdditions?> 
	</body>
</html>