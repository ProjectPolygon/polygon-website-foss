<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;
use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;

Users::RequireAdmin(Users::STAFF_ADMINISTRATOR);

$pageBuilder = new PageBuilder(["title" => "Post News"]);
$pageBuilder->buildHeader();

$query = Database::singleton()->run("SELECT * FROM feed_news WHERE time_deleted IS NOT NULL ORDER BY id DESC");

$latest_query = Database::singleton()->run("SELECT * FROM feed_news WHERE time_deleted IS NOT NULL ORDER BY id DESC LIMIT 1");

$recent = $latest_query->fetch(PDO::FETCH_OBJ);
?>
<h2 class="font-weight-normal">Post News</h2>
<div class="row">
  <div class="col-lg-6 py-4 divider-right">
    <div class="form-group row">
      <label for="title" class="col-sm-3 col-form-label">Title</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="title" placeholder="Give a name for your post.">
      </div>
    </div>
    <div class="form-group row">
      <label for="body" class="col-sm-3 col-form-label">Body</label>
      <div class="col-sm-9">
        <textarea class="form-control" id="body" placeholder="Stay on topic, be brief and concise."></textarea>
      </div>
    </div>
    <div class="row">
      <button class="btn btn-success btn-block mx-3" data-control="postNews"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span> Post News</button>
    </div>
  </div>
  <div class="col-lg-6 pt-3">
    <h2 class="font-weight-normal">Some notes</h2>
  <ul class="list-group">
	  <li class="list-group-item">Stay brief and concise.</li>
	  <li class="list-group-item">All posts will be shown on site, so be careful about what you post.</li>
	  <li class="list-group-item">Posts will be logged.</li>
	</ul>
  </div>
</div>
<hr>
  <h2 class="font-weight-normal">All Posts</h2>
  <table class="table table-hover table-responsive">
      <thead class="thead-light">
        <tr>
          <th scope="col">Title</th>
          <th scope="col">Body</th>
          <th scope="col">Author</th>
          <th scope="col">Posted On</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php while($row = $query->fetch(\PDO::FETCH_OBJ)) { ?>
            <tr class="<?=$recent->id === $row->id ? "table-primary" : "table-light"?>">
              <td>
                <?=Polygon::FilterText($row->title)?>
              </td>
              <td>
              <button class="btn btn-primary" data-title="Body of <?=$row->title?>" data-text="<?=Polygon::FilterText($markdown->setEmbedsEnabled(true)->text($row->body),false)?>" data-control="openModal">View</button>
              </td>
              <td>
                <a href="/user?ID=<?=$row->user_id?>"><?=Users::GetNameFromID($row->user_id)?></a>
              </td>
              <td>
                <?=date("j/n/Y", $row->time_created)?>
              </td>
              <td>
                <button class="btn btn-danger" data-post-id="<?=$row->id?>" data-control="deletePost"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"> Delete</button>
              </td>
            </tr>
          <?php } ?>
        </tr>
      </tbody>
    </table>

<script>
  //admin.js
  $('button[data-control$="postNews"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/post-news', {"title":$("#title").val(), "body":$("#body").val()}, function(data)
    {
      if(data.success){ location.reload() } 
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('button[data-control$="deletePost"]').on('click', this, function()
  {
    var button = this; 
    $(button).attr("disabled", "disabled").find("span").show();
    $.post('/api/admin/delete-news', {"id":$(this).attr("data-post-id")}, function(data)
    {
      if(data.success){ location.reload() } 
      else{ toastr["error"](data.message); }
      $(button).removeAttr("disabled").find("span").hide();
    });
  });

  $('button[data-control$="openModal"]').on('click', this, function()
  {
    polygon.buildModal({ 
      header: $(this).attr("data-title"), 
      body: $(this).attr("data-text"), 
      buttons: [{class: 'btn btn-outline-secondary', dismiss: true, text: 'Close'}] 
    });
  });
</script>
<?php $pageBuilder->buildFooter(); ?>
