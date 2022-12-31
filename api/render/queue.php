<?php require $_SERVER["DOCUMENT_ROOT"]."/api/private/core.php";
Polygon::RequireAPIKey("RenderServer");
header("content-type: text/plain");

if(SITE_CONFIG["site"]["thumbserver"] != "RCCService2015") die(http_response_code(403));

$RenderJobs = db::run("SELECT * FROM renderqueue WHERE renderStatus = 0 ORDER BY timestampRequested DESC");

while ($RenderJob = $RenderJobs->fetch(PDO::FETCH_OBJ))
{
	printf("%s:%d:%s;\n", $RenderJob->renderType, $RenderJob->assetID, $RenderJob->jobID);
}