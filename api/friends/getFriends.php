<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
header("content-type: application/json");

if($_SERVER['REQUEST_METHOD'] != 'POST'){ api::respond(405, false, "Method Not Allowed"); }
if(!isset($_POST['userID'])){ api::respond(400, false, "Invalid Request - userID not set"); }
if(!is_numeric($_POST['userID'])){ api::respond(400, false, "Invalid Request - userID is not numeric"); }
if(!users::getUserInfoFromUid($_POST['userID'])){ api::respond(400, false, "User does not exist"); }
if(isset($_POST['page']) && !is_numeric($_POST['page'])){ api::respond(400, false, "Invalid Request - page is not numeric"); }
if(isset($_POST['limit']) && !is_numeric($_POST['limit'])){ api::respond(400, false, "Invalid Request - limit is not numeric"); }

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$limit = isset($_POST['limit']) ? $_POST['limit'] : 18;

$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1");
$query->bindParam(":uid", $_POST['userID'], PDO::PARAM_INT);
$query->execute();

$pages = ceil($query->fetchColumn()/$limit);
$offset = ($page - 1)*$limit;

$query = $pdo->prepare("SELECT * FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1 LIMIT :limit OFFSET :offset");
$query->bindParam(":uid", $_POST['userID'], PDO::PARAM_INT);
$query->bindParam(":limit", $limit, PDO::PARAM_INT);
$query->bindParam(":offset", $offset, PDO::PARAM_INT);
$query->execute();

$friends = [];

while($row = $query->fetch(PDO::FETCH_OBJ))
{ 
	$friendId = $row->requesterId == $_POST['userID'] ? $row->receiverId : $row->requesterId;
	$friends[] = ["userName" => users::getUserNameFromUid($friendId), "userId" => $friendId]; 
}

ob_start(); ?>

<nav aria-label="Friend Pagination">
  <ul class="pagination justify-content-center mb-0">
	<li class="page-item<?=$page<=1?' disabled':''?>">
	  <a class="page-link" href="#" aria-label="Previous" data-control="friends-pager" data-page="<?=$page-1?>">
		<span aria-hidden="true">&laquo;</span>
		<span class="sr-only">Previous</span>
	  </a>
	</li>
	<?php $paginator = 1; while($paginator <= $pages){ ?>
	<li class="page-item<?=($paginator == $page)?' active':''?>"><a class="page-link" href="#" data-control="friends-pager" data-page="<?=$paginator?>"><?=$paginator?></a></li>
	<?php $paginator++; } ?>
	<li class="page-item<?=$page>=$pages?' disabled':''?>">
	  <a class="page-link" href="#" aria-label="Next" data-control="friends-pager" data-page="<?=$page+1?>">
		<span aria-hidden="true">&raquo;</span>
		<span class="sr-only">Next</span>
	  </a>
	</li>
  </ul>
</nav>

<?php
$pager = ob_get_clean();

die(json_encode(
	[
		"status" => 200, 
		"success" => true, 
		"message" => "OK", 
		"username" => users::getUserNameFromUid($_POST['userID']),
		"friendCount" => $query->rowCount(), 
		"friends" => $friends,
		"pages" => $pages,
		"pager" => $pages > 1 ? $pager : false
	]));