<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin();

$Alert = false;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$LastTicket = Database::singleton()->run(
		"SELECT TimeCreated FROM InviteTickets WHERE CreatedBy = :UserID AND TimeCreated + 5 > UNIX_TIMESTAMP()", 
		[":UserID" => SESSION["user"]["id"]]
	);

	if($LastTicket->rowCount()) 
	{
		$Alert = ["Text" => "Please wait ".GetReadableTime($LastTicket->fetchColumn(), ["RelativeTime" => "5 seconds"])." before creating a new invite ticket", "Color" => "danger"];
	}
	else
	{
		$Ticket = sprintf("PolygonTicket(%s)", bin2hex(random_bytes(16)));

		Database::singleton()->run(
			"INSERT INTO InviteTickets (Ticket, TimeCreated, CreatedBy) VALUES (:Ticket, UNIX_TIMESTAMP(), :UserID)", 
			[":Ticket" => $Ticket, ":UserID" => SESSION["user"]["id"]]
		);

		Users::LogStaffAction(sprintf(
			"[ Create Invite Ticket ] %s created an invite ticket with the key \"%s\"", 
			SESSION["user"]["username"], $Ticket
		)); 

		$Alert = ["Text" => sprintf("Your key has been created! <code>%s</code>", $Ticket), "Color" => "success"];
	}
}

$Page = $_GET["Page"] ?? 1;
$TicketCount = Database::singleton()->run("SELECT COUNT(*) FROM InviteTickets")->fetchColumn();

$Pagination = Pagination($Page, $TicketCount, 20);

$Tickets = Database::singleton()->run(
	"SELECT InviteTickets.*, Users1.username AS CreatedByName, Users2.username AS UsedByName FROM InviteTickets 
	INNER JOIN users AS Users1 ON Users1.id = InviteTickets.CreatedBy 
	LEFT JOIN users AS Users2 ON Users2.id = InviteTickets.UsedBy
	ORDER BY TimeCreated DESC LIMIT 20 OFFSET :Offset", 
	[":Offset" => $Pagination->Offset]
);

// idk y i decided to code this on my 15th birthday for mercury but i did
$InviteTree = [];

function GenerateTree($UserName, $UserID, &$Node)
{
    $CurrentNode = ["UserName" => $UserName, "UserID" => $UserID, "Invited" => []];

    // $query = $pdo->prepare("SELECT users.username, users.id FROM invitekeys INNER JOIN users ON users.id = usedById WHERE creatorId = :uid AND keyUsed");
    // $query->bindParam(":uid", $UserID, PDO::PARAM_INT);
    // $query->execute();

    $query = Database::singleton()->run(
        "SELECT UsedBy.username, UsedBy.id FROM InviteTickets 
        INNER JOIN users AS UsedBy ON UsedBy.id = InviteTickets.UsedBy
        WHERE CreatedBy = :UserID ORDER BY id DESC",
        [":UserID" => $UserID]
    );

    while ($row = $query->fetch(PDO::FETCH_OBJ))
    {
        GenerateTree($row->username, $row->id, $CurrentNode["Invited"]);
    }

    $Node[] = $CurrentNode;
}

function DisplayTree($Tree)
{
    echo "<ul>";
    foreach($Tree as $Node)
    {
        // echo "<li>" . $Node["UserName"] . "</li>";
        printf("<li><a href=\"/user?ID=%d\">%s</a></li>", $Node["UserID"], $Node["UserName"]);
        if (count($Node["Invited"]) > 0) DisplayTree($Node["Invited"]);
    }
    echo "</ul>";
}

$RootUsers = Database::singleton()->run("SELECT id, username FROM users WHERE keyUsed NOT LIKE \"PolygonTicket%\" OR keyUsed IS NULL");
while ($RootUser = $RootUsers->fetch(PDO::FETCH_OBJ))
{
	GenerateTree($RootUser->username, $RootUser->id, $InviteTree);	
}

$pageBuilder = new PageBuilder(["title" => "Invite Tickets"]);
$pageBuilder->buildHeader();
?>
<?php if($Alert !== false) { ?><div class="alert alert-<?=$Alert["Color"]?> px-2 py-1" role="alert"><?=$Alert["Text"]?></div><?php } ?>
<h2 class="font-weight-normal pb-0">Invitations</h2>
<div class="row pt-2">
	<div class="col-lg-2 col-md-3 pl-3 pr-md-0 pb-3 divider-right">
		<ul class="nav nav-tabs flex-column" id="generalTabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="tickets-tab" data-toggle="tab" href="#tickets" role="tab" aria-controls="tickets" aria-selected="true">Invite Tickets</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="tree-tab" data-toggle="tab" href="#tree" role="tab" aria-controls="tree" aria-selected="false">Invite Tree</a>
			</li>
		</ul>
        <div class="mt-3 mr-0 mr-md-3">
            <form method="post">
                <button type="submit" class="btn btn-sm btn-success btn-block">Create Ticket</button>
            </form>
        </div>
	</div>
    <div class="col-lg-10 col-md-9 px-3">
		<div id="generalTabsContent" class="tab-content">
			<div class="tab-pane active" id="tickets" role="tabpanel" aria-labelledby="tickets-tab">
                <table class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th class="font-weight-normal py-2" scope="col">Ticket</th>
							<th class="font-weight-normal py-2" scope="col">Creator</th>
                            <th class="font-weight-normal py-2" scope="col">Used By</th>
                            <th class="font-weight-normal py-2" scope="col">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($Ticket = $Tickets->fetch(\PDO::FETCH_OBJ)) { ?>
                        <tr>
                            <td><code><?=htmlspecialchars($Ticket->Ticket)?></code></td>
							<td><a href="/user?ID=<?=$Ticket->CreatedBy?>"><?=$Ticket->CreatedByName?></a></td>
                            <?php if ($Ticket->UsedBy == NULL) { ?>
                            <td>No One</td>
                            <?php } else { ?>
                            <td><a href="/user?ID=<?=$Ticket->UsedBy?>"><?=$Ticket->UsedByName?></a></td>
                            <?php } ?>
                            <td title="<?=date('j/n/Y g:i:s A \G\M\T', $Ticket->TimeCreated)?>"><?=date('j/n/Y', $Ticket->TimeCreated)?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php if($Pagination->Pages > 1) { ?>
                <div class="pagination form-inline justify-content-center">
                    <a class="btn btn-light back<?=$Pagination->Page<=1?' disabled':'" href="?Page='.($Pagination->Page-1)?>"><h5 class="mb-0"><i class="fal fa-caret-left"></i></h5></a>
                    <span class="px-3">Page <?=$Pagination->Page?> of <?=$Pagination->Pages?></span>
                    <a class="btn btn-light next<?=$Pagination->Page>=$Pagination->Pages?' disabled':'" href="?Page='.($Pagination->Page+1)?>"><h5 class="mb-0"><i class="fal fa-caret-right"></i></h5></a>
                </div>
                <?php } ?>
            </div>
			<div class="tab-pane" id="tree" role="tabpanel" aria-labelledby="tree-tab">
                <?php DisplayTree($InviteTree); ?>
            </div>
        </div>
    </div>
</div>
<?php $pageBuilder->buildFooter(); ?>
