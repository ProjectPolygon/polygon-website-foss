<?php require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Toolbox</title>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
        <link href="/css/rbxclient/Tooolbox.css" type="text/css" rel="stylesheet" />
        <script id="Functions" type="text/jscript">
    			function insertContent(id)
    			{
    				try
                	{
                    	window.external.Insert("http://<?=$_SERVER['HTTP_HOST']?>/Asset/?id=" + id);
                    }
	                catch(x)
	                {
	                    alert("Could not insert the requested item");
	                }
    			}
    			function dragRBX(id)
    			{
    				try
    				{
    					event.dataTransfer.setData("Text", "http://<?=$_SERVER['HTTP_HOST']?>/Asset/?id=" + id);
    				}
	                catch(x)
	                {
	                    alert("Sorry Could not drag the requested item");
	                }
    			}
    			function clickButton(e, buttonid)
    			{
    				var bt = document.getElementById(buttonid);
    				if (typeof bt == 'object')
    				{
    					if (navigator.appName.indexOf("Microsoft Internet Explorer")>(-1))
    					{
    						if (event.keyCode == 13)
    						{
    							bt.click();
    							return false;
    						}
    					}
    				}
    			}

    			function getToolbox(type, keyword, page) 
    			{
                    $.post("/api/ide/toolbox", {category:type, page:page, keyword:keyword}, function(data) 
                    {
                        $("#ToolBoxPage").html("");
                        $("#ToolBoxPage").html(data);
                    });
    			}

    			$(function() 
    			{
                    $("#ddlToolboxes").change(function() 
                    {   
                        var category = $(this).val();
                        if(category == "FreeDecals" || category == "FreeModels") $("#pSearch").show();
                        else $("#pSearch").hide();
                        getToolbox(category, "", 1);
                        $("#tbSearch").val("");
                    });

                    getToolbox("FreeModels", "", 1);
    			});
        </script>
    </head>
    <body class="Page" bottommargin="0" rightmargin="0">
        <div id="ToolboxContainer">
            <div id="ToolboxControls">
                <div id="ToolboxSelector">
                    <select name="ddlToolboxes" id="ddlToolboxes" class="Toolboxes">
                        <!--option value="0" selected="selected">Bricks</option>
                        <option value="1">Robots</option>
                        <option value="2">Chassis</option>
                        <option value="3">Furniture</option>
                        <option value="4">Roads</option>
                        <option value="5">Billboards</option>
                        <option value="6">Game Objects</option-->
                        <?php if(SESSION){ ?><option value="MyDecals">My Decals</option><?php } ?>
                        <option value="FreeDecals">Free Decals</option>
                        <?php if(SESSION){ ?><option value="MyModels">My Models</option><?php } ?>
                        <option value="FreeModels" selected="selected">Free Models</option>
                    </select>
                </div>
                <div id="pSearch" style="display:none;margin-bottom:6px;">
                    <div id="ToolboxSearch">
                        <input name="tbSearch" type="text" id="tbSearch" class="Search" onkeypress="clickButton(event, 'Button');" />
                        <input type="button" name="lbSearch" class="ButtonText" id="Button" style="height:19px" value="Search" onclick="getToolbox($('#ddlToolboxes').val(), $('#tbSearch').val(), 1);">
                    </div>
                    <br>
                </div>
            </div>
            <div id="ToolBoxPage"></div>
        </div>
    </body>
</html>