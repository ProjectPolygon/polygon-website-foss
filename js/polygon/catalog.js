polygon.catalog.show = function()
{
	var strEncoded = "/catalog?";

	if(this.Subcategory != null) strEncoded += ("Subcategory=" + this.Subcategory + "&");
	if(this.Keyword != null) strEncoded += ("Keyword=" + this.Keyword + "&");
	if(this.CurrencyType != null) strEncoded += ("CurrencyType=" + this.CurrencyType + "&");
	if(this.SortType != null) strEncoded += ("SortType=" + this.SortType + "&");
	if(this.PageNumber != null) strEncoded += ("PageNumber=" + this.PageNumber + "&");
	if(this.IncludeNotForSale != null && $("#includeNotForSaleCheckbox").length) strEncoded += ("IncludeNotForSale=" + this.IncludeNotForSale + "&");
	strEncoded += ("Category=" + this.Category);

	window.location = strEncoded;
}

// filters
$(".assetTypeFilter").click(function(event)
{ 
	event.preventDefault(); 
	if($(this).attr("data-category")) polygon.catalog.Category = $(this).attr("data-category"); 
	if($(this).attr("data-types")) polygon.catalog.Subcategory = $(this).attr("data-types"); 
	if(!$(this).attr("data-keepfilters"))
	{
		if($(this).attr("data-category")) polygon.catalog.Subcategory = null;
		polygon.catalog.PageNumber = null;
		polygon.catalog.Keyword = null;
		polygon.catalog.CurrencyType = 0;
		polygon.catalog.SortType = 1;
		polygon.catalog.IncludeNotForSale = null;
	}
	polygon.catalog.show(); 
});
$(".priceFilter").click(function(event){ event.preventDefault(); polygon.catalog.CurrencyType = $(this).attr("data-currencytype"); polygon.catalog.show(); });
$("#includeNotForSaleCheckbox").change(function(){ polygon.catalog.IncludeNotForSale = $(this).prop("checked"); polygon.catalog.show(); });
	
//search
$("select.categoriesForKeyword").change(function(){ polygon.catalog.Subcategory = null; polygon.catalog.Category = $(this).val(); polygon.catalog.show(); });
$(".keywordTextbox").keypress(function(event){ if(event.which != "13") return; polygon.catalog.PageNumber = null; polygon.catalog.Keyword = $(this).val(); polygon.catalog.show(); });
$(".submitSearchButton").click(function(){ polygon.catalog.PageNumber = null; polygon.catalog.Keyword = $(".keywordTextbox").val(); polygon.catalog.show(); });
$("select.Sort").change(function(){ polygon.catalog.SortType = $(this).val(); polygon.catalog.show(); });

//pagination
$(".pagination .back").click(function(){ polygon.catalog.PageNumber = +$(".pagination .page").val()-1; polygon.catalog.show(); });
$(".pagination .next").click(function(){ polygon.catalog.PageNumber = +$(".pagination .page").val()+1; polygon.catalog.show(); });
$(".pagination .page").on("focusout keypress", this, function(event)
{ 
	if(event.type == "keypress") if(event.which == 13) $(this).blur(); else return;
	polygon.catalog.PageNumber = $(this).val(); polygon.catalog.show();
});

/*$(".items .item").hover(
	function(){ $(this).find(".details").removeClass("d-none"); }, 
	function(){ $(this).find(".details").addClass("d-none"); });*/