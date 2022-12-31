<?php 
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

$username = isset($_POST['search']) ? "%".$_POST['search']."%" : "%";

$query = $pdo->prepare("SELECT * FROM users WHERE username LIKE :username AND NOT (SELECT COUNT(*) FROM bans WHERE userId = users.id AND NOT isDismissed) ORDER BY lastonline DESC");
$query->bindParam(":username", $username, PDO::PARAM_STR);
$query->execute();

pageBuilder::buildHeader();
?>
<form method="post">
	<div class="form-group row">
	    <label for="search" class="col-sm-2 col-form-label">Search: </label>
	    <div class="col-sm-8">
	      <input type="text" class="form-control" name="search" id="search" value="<?=$_POST['search'] ?? ''?>">
	    </div>
	    <button class="btn btn-light">Search Users</button>
	</div>
	<?php if($query->rowCount()) { ?>
	<table class="table table-hover">
	  <thead>
	    <tr>
	      <th scope="col" style="width:5%">Avatar</th>
	      <th scope="col" style="width:20%">Name</th>
	      <th scope="col" style="width:55%">Blurb</th>
	      <th scope="col" style="width:20%">Location / Last Seen</th>
	    </tr>
	  </thead>
	  <tbody class="">
	  	<?php while($row = $query->fetch(PDO::FETCH_OBJ)) { $onlineStatus = users::getOnlineStatus($row->id); ?>
	    <tr>
	      <td><img src="<?=users::getUserAvatar($row->id)?>" title="<?=$row->username?>" alt="<?=$row->username?>" width="63" height="63"></td>
	      <td><a href="/user?ID=<?=$row->id?>"><?=$row->username?></a></td>
	      <td><?=htmlspecialchars($row->blurb)?></td>
	      <td><?=($onlineStatus["online"]?'Online':'Offline').': '.$onlineStatus["text"]?></td>
	    </tr>
		<?php } ?>
	  </tbody>
	</table>
	<?php } else { ?>
	<p class="text-center">Could not find any results <a class="btn btn-light" href="/browse">Reset search</a></p>
	<?php } ?>
</form>
<?php pageBuilder::buildFooter(); ?>
