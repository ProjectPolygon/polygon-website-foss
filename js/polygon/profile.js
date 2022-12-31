polygon.Profile = {};

polygon.Profile.Places = polygon.CreateControl(
{
	Container: "user-places",

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/games/fetch",
			data: { CreatorID: $(".app").attr("data-user-id") }
		}
	},

	PopulateCallback: function(Item, Template)
	{
		if (!Item.CanPlayGame) 
		{
			Template.find(".VisitButton.VisitButtonPlay").remove();
			Template.find(".VisitButton.VisitButtonEdit").remove();
			Template.find(".VisitButton.VisitButtonSolo").remove();
		}
		else if (!Item.Uncopylocked && $(".app").attr("data-self-profile") != "true")
		{
			Template.find(".VisitButton.VisitButtonEdit").remove();
			Template.find(".VisitButton.VisitButtonSolo").remove();
		}

		return {Item: Item, Template: Template};
	}
});

polygon.Profile.Badges = polygon.CreateControl(
{
	Container: "badges",
	Properties: { BadgeType: "Polygon" },

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/users/get-badges",
			data: 
			{ 
				UserID: $(".app").attr("data-user-id"), 
				BadgeType: Control.BadgeType 
			}
		}
	},

	Initializers: function(Control)
	{
		$(".badges-container .selector").click(function()
		{ 
			Control.BadgeType = $(this).attr("data-badge-type");
			Control.Display(1); 
		});
	}
});

polygon.Profile.Groups = polygon.CreateControl(
{
	Container: "groups",

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/users/get-groups",
			data: { UserID: $(".app").attr("data-user-id") }
		}
	}
});