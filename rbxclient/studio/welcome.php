<?php require $_SERVER["DOCUMENT_ROOT"] . "/api/private/core.php";

use pizzaboxer\ProjectPolygon\Database;
use pizzaboxer\ProjectPolygon\Polygon;
use pizzaboxer\ProjectPolygon\Thumbnails;
use pizzaboxer\ProjectPolygon\PageBuilder;

if (SESSION) 
{
    $Projects = Database::singleton()->run(
        "SELECT * FROM assets WHERE type = 9 AND creator = :UserID ORDER BY created DESC", 
        [":UserID" => SESSION["user"]["id"]]
    );
}

$TemplatePlaces = Database::singleton()->run(
    "SELECT * FROM assets WHERE TemplateOrder IS NOT NULL ORDER BY TemplateOrder"
)->fetchAll();

$pageBuilder = new PageBuilder(["title" => "Welcome", "ShowNavbar" => false, "ShowFooter" => false]);
$pageBuilder->addAppAttribute("class", "app nav-content");
$pageBuilder->addResource("scripts", "/js/protocolcheck.js");
$pageBuilder->addResource("polygonScripts", "/js/polygon/games.js");
$pageBuilder->buildHeader();
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-top bg-dark">
    <span class="navbar-brand">
        <img src="/img/PolygonStudio.png" style="max-width:64px;" class="d-inline-block">
        <h1 class="mx-2 d-inline-block align-middle">Project Polygon</h1>
	</span>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#primaryNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
    <div class="collapse navbar-collapse" id="primaryNavbar">
        <ul class="navbar-nav header-links mr-auto">
            <li class="nav-item">
                <a href="/develop" class="text-light">Switch to classic view</a>
            </li>
        </ul>
        <div class="navbar-nav">
            <?php if (SESSION) { ?>            
            <span class="nav-link text-light mr-2">Logged in as <?=SESSION["user"]["username"]?></span>
            <div class="navbar-button-container">
                <a class="btn btn-sm btn-light my-1 mr-2 px-3" href="/logout">Logout</a>
            </div>
            <?php } else { ?>
			<a href="/" class="btn btn-sm btn-light my-1 mx-2">Sign Up</a>
			<span class="nav-link text-light px-1">or</span>
			<a class="btn btn-sm btn-light my-1 mx-2 px-4" href="/login?ReturnUrl=<?=urlencode($_SERVER['REQUEST_URI'])?>">Login</a>
            <?php } ?>
        </div>
    </div>	
</nav>
<div class="row mx-0 my-3">
	<div class="col-lg-2 col-md-3 pl-3 pb-3 pr-md-0 divider-right" style="/*! max-width: 12rem; */">
		<ul class="nav nav-tabs flex-column" id="welcomeTabs" role="tablist">
            <li class="nav-item">
			    <a class="nav-link active" id="templates-tab" data-toggle="tab" href="#templates" role="tab" aria-controls="templates" aria-selected="false">New Project</a>
			</li>
            <li class="nav-item">
			    <a class="nav-link" id="projects-tab" data-toggle="tab" href="#projects" role="tab" aria-controls="projects" aria-selected="false">My Projects</a>
			</li>	
		</ul>
	</div>
	<div class="col-lg-10 col-md-9 p-0 pl-3 pr-4">
		<div class="tab-content mb-4" id="welcomeTabsContent">
			<div class="tab-pane active" id="templates" role="tabpanel">
				<h3 class="font-weight-normal mt-1 mb-4">Place Templates</h3>
                <div class="row px-2">
                    <?php foreach ($TemplatePlaces as $TemplatePlace) { ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6 px-2 mb-3">
                        <div class="place-template card hover h-100 VisitButton VisitButtonEdit" role="button" placeid="<?=$TemplatePlace["id"]?>" placeversion="2012">
                            <img class="card-img-top img-fluid" title="<?=Polygon::FilterText($TemplatePlace["name"])?>" alt="<?=Polygon::FilterText($TemplatePlace["name"])?>" src="<?=Thumbnails::GetAsset((object)$TemplatePlace, 768, 432)?>">
                            <div class="card-body p-2 text-center">
                                <p class="mb-0 text-truncate" title="<?=Polygon::FilterText($TemplatePlace["name"])?>"><?=Polygon::FilterText($TemplatePlace["name"])?></p>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="tab-pane" id="projects" role="tabpanel">
				<h3 class="font-weight-normal mt-1 mb-4">My Published Projects</h3>
                <?php if (SESSION) { ?>
                <div class="row px-2">
                    <?php while ($Project = $Projects->fetch(\PDO::FETCH_OBJ)) { ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6 px-2 mb-3">
                        <div class="place-template card hover h-100 VisitButton VisitButtonEdit" role="button" placeid="<?=$Project->id?>" placeversion="2012">
                            <img class="card-img-top img-fluid" title="<?=Polygon::FilterText($Project->name)?>" alt="<?=Polygon::FilterText($Project->name)?>" src="<?=Thumbnails::GetAsset($Project, 768, 432)?>">
                            <div class="card-body p-2 text-center">
                                <p class="mb-0 text-truncate" title="<?=Polygon::FilterText($Project->name)?>"><?=Polygon::FilterText($Project->name)?></p>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <?php } else { ?>
                    <p>You must be logged in to view your published projects!</p>
                    <?php } ?>
                </div>
            </div>
		</div>
	</div>
</div>
<?php $pageBuilder->buildFooter(); ?>