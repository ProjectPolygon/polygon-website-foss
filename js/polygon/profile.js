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
		if (Item.Uncopylocked || !window.location.search.includes("ID=")) return {Item, Template};
				
		Template.find(".VisitButton.VisitButtonEdit").remove();
		Template.find(".VisitButton.VisitButtonSolo").remove();

		return {Item, Template};
	}
});

polygon.Profile.Badges = polygon.CreateControl(
{
	Container: "badges",
	Properties: { BadgeType: "polygon" },

	AjaxConfig: function(Control)
	{
		return {
			type: "POST",
			url: "/api/users/get-badges",
			data: 
			{ 
				userID: $(".app").attr("data-user-id"), 
				type: Control.BadgeType 
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
			data: { userID: $(".app").attr("data-user-id") }
		}
	}
});

polygon.Profile.Places.Initialize();
polygon.Profile.Badges.Initialize();
polygon.Profile.Groups.Initialize();