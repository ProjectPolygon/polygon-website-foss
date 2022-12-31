<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';

Polygon::ImportClass("Catalog");
Polygon::ImportClass("Thumbnails");

api::initialize(["method" => "POST", "admin" => Users::STAFF, "admin_ratelimit" => true, "secure" => true]);

$assetId = $_POST['assetID'] ?? false;
$action = $_POST['action'] ?? false;
$action_sql = $action == "approve" ?: 2;
$reason = $_POST['reason'] ?? false;
$asset = Catalog::GetAssetInfo($assetId);

if (!in_array($action, ["approve", "decline"])) api::respond(400, false, "Invalid request");
if (!$asset) api::respond(200, false, "Asset does not exist");
if ($action == "approve" && $asset->approved == 1) api::respond(200, false, "This asset has already been approved");
if ($action == "disapprove" && $asset->approved == 2) api::respond(200, false, "This asset has already been disapproved");
if ($action == "approve" && $asset->approved == 2) api::respond(200, false, "Disapproved assets cannot be reapproved");

db::run(
	"UPDATE assets SET approved = :action WHERE id IN (:id, :image)", 
	[":action" => $action_sql, ":id" => $asset->id, ":image" => $asset->imageID]
);

if ($action == "decline")
{
	Thumbnails::DeleteAsset($asset->id);
	Catalog::DeleteAsset($asset->id);

	if ($asset->imageID != NULL)
	{
		Catalog::DeleteAsset($asset->imageID);		
		Thumbnails::DeleteAsset($asset->imageID);
	}
}

Users::LogStaffAction('[ Asset Moderation ] '.ucfirst($action).'d "'.$asset->name.'" [ID '.$asset->id.']'.($reason ? ' with reason: '.$reason : '')); 
api::respond(200, true, '"'.htmlspecialchars($asset->name).'" has been '.$action.'d');