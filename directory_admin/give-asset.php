<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Users;
use pizzaboxer\ProjectPolygon\Catalog;
use pizzaboxer\ProjectPolygon\PageBuilder;

Users::RequireAdmin([Users::STAFF_CATALOG, Users::STAFF_ADMINISTRATOR]);

$Alert = false;
$Conditions = 
[
	"AssetID" => ["Type" => "Integer"], 
	"UserID" => ["Type" => "Integer"]
];

function SetError($text)
{
	global $Alert;
	$Alert = ["text" => $text, "color" => "danger"];
}

if($_SERVER["REQUEST_METHOD"] == "POST")
{
	$AssetID = $_POST["AssetID"] ?? "";
	$Condition = $_POST["Condition"] ?? "";
	$ConditionData = $_POST["ConditionData"] ?? "";

	if (empty($AssetID)) SetError("Asset ID cannot be empty");
	else if (!isset($Conditions[$Condition])) SetError("Condition is not valid");
	else if (empty($ConditionData)) SetError("Condition data must be set");

	else if ($Conditions[$Condition]["Type"] == "Integer" && !is_numeric($ConditionData)) SetError("Condition data must be a number");
	else if (!Catalog::GetAssetInfo($AssetID)) SetError("The asset you're trying to give does not exist");

	if ($Alert === false)
	{
		$ItemName = Catalog::GetAssetInfo($AssetID)->name;
		$ConditionString = "";
		$UserIDs = [];
		$TagID = generateUUID();

		if ($Condition == "UserID")
		{
			$ConditionString = "had the user ID $ConditionData";

			$UserIDs = Database::singleton()->run(
				"SELECT id FROM users WHERE id = :ConditionData 
				AND NOT (SELECT COUNT(*) FROM ownedAssets WHERE userId = users.id AND assetId = :AssetID)",
				[":AssetID" => $AssetID, ":ConditionData" => $ConditionData]
			)->fetchAll(\PDO::FETCH_COLUMN);
		}
		else if ($Condition == "AssetID")
		{
			$ConditionString = "purchased an asset with ID $ConditionData";

			$UserIDs = Database::singleton()->run(
				"SELECT id FROM users WHERE id IN (SELECT userId FROM ownedAssets WHERE assetId = :ConditionData) 
				AND NOT (SELECT COUNT(*) FROM ownedAssets WHERE userId = users.id AND assetId = :AssetID)",
				[":AssetID" => $AssetID, ":ConditionData" => $ConditionData]
			)->fetchAll(\PDO::FETCH_COLUMN);
		}

		foreach ($UserIDs as $UserID)
		{
			Database::singleton()->run(
				"INSERT INTO ownedAssets (assetId, userId, TagID, timestamp) VALUES (:AssetID, :UserID, :TagID, UNIX_TIMESTAMP())",
				[":UserID" => $UserID, ":AssetID" => $AssetID, ":TagID" => $TagID]
			);
		}

		Database::singleton()->run(
			"UPDATE assets SET Sales = Sales + :UserCount WHERE id = :AssetID", 
			[":AssetID" => $AssetID, ":UserCount" => count($UserIDs)]
		);

		$Alert = ["text" => sprintf("\"%s\" has been given to %d user(s) (Tag ID %s)", $ItemName, count($UserIDs), $TagID), "color" => "primary"];

		Users::LogStaffAction(sprintf(
			"[ Give Asset ] %s gave \"%s\" (ID %s) to %d user(s) who %s (Tag ID %s)", 
			SESSION["user"]["username"], $ItemName, $AssetID, count($UserIDs), $ConditionString, $TagID
		)); 
	}
}

$pageBuilder = new PageBuilder(["title" => "Give Asset"]);
$pageBuilder->buildHeader();
?>
<h2 class="font-weight-normal">Give Asset</h2>
<?php if($Alert !== false) { ?><div class="alert alert-<?=$Alert["color"]?> px-2 py-1" role="alert"><?=$Alert["text"]?></div><?php } ?>
<p class="mb-2"><i class="fas fa-exclamation-triangle text-warning"></i> Be careful about how you use this. Actions done here can be reverted, but may take a while to roll back.</p>
<form method="post">
	<span>Give <input class="form-control form-control-sm d-inline text-center px-0" name="AssetID" placeholder="Asset ID" style="width:100px;"> to anyone who has <select class="form-control form-control-sm d-inline text-center px-0" name="Condition" style="width:150px;"><option value="UserID">the User ID</option><option value="AssetID">purchased an asset with ID</option></select> <input class="form-control form-control-sm d-inline text-center px-0" name="ConditionData" style="width:150px;"> <button class="btn btn-sm btn-success mx-2" type="submit">Give</button></span>
</form>
<?php $pageBuilder->buildFooter(); ?>
