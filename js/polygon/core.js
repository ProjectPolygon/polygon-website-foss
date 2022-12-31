$.ajaxSetup({ headers: { 'x-polygon-csrf': $('meta[name="polygon-csrf"]').attr('content') } });

/* todo - dont be lazy and work on this
polygon.ajax = function(url, method, data, trusted, successCallback, errorCallback)
{
	var ajaxOptions = {type: method, data: data};

	if(trusted)
	{
		ajaxOptions.url = window.location.origin + url;
		ajaxOptions.headers: {'x-polygon-csrf': $('meta[name="polygon-csrf"]').attr('content')};
	}
	else { ajaxOptions.url = url; }


}*/

polygon.button = 
{
	busy: function(button)
	{
		$(button).attr("disabled", "disabled").find(".spinner-border").removeClass("d-none");
	},

	active: function(button)
	{
		$(button).removeAttr("disabled").find(".spinner-border").addClass("d-none");
	}
};

polygon.insertAlert = function(options)
{
	var alertCode = '';
	if (options.alertClasses == undefined) options.alertClasses = '';

	if (options.parentClasses) alertCode += '<div class="' + options.parentClasses + '">';
	alertCode += '<div class="alert alert-danger ' + options.alertClasses + ' px-2 py-1" style="width: fit-content;" role="alert">' + options.text + '</div>';
	if (options.parentClasses) alertCode += '</div>';

	$(options.parent).append(alertCode);
}

polygon.buildModal = function(options)
{
	if (options.options == undefined) 
		options.options = "show";

	if (options.fade == undefined) 
		$(".global.modal").addClass("fade");
	else if (!options.fade) 
		$(".global.modal").removeClass("fade");

	if (options.image != undefined)
		options.body = '<div class="row"><div class="col-3"><img src="' + options.image + '" class="img-fluid"></div><div class="col-9 text-left">' + options.body + '</div></div>';

	var footer = $(".global.modal .modal-footer .mx-auto");
	footer.empty();

	$(".global.modal .modal-title").html(options.header);
	$(".global.modal .modal-body").html(options.body);

	$.each(options.buttons, function(_, button)
	{
		var buttonCode = '<button type="button" class="' + button.class + ' btn-lg text-center mx-1"';

		if (button.attributes != undefined) 
		{
			$.each(button.attributes, function(Attribute, Value)
			{
				buttonCode += ' ' + Attribute + '="' + Value + '"';
			});
		}

		if (button.dismiss) buttonCode += ' data-dismiss="modal"';

		buttonCode += '><h4 class="font-weight-normal my-';

		if (polygon.user.theme == "2014") buttonCode += '0';
		else buttonCode += '1';

		buttonCode += '">' + button.text + '</h4></button>';

		footer.append(buttonCode);
	});

	if (options.footer) footer.append('<p class="text-muted mt-3 mb-0">' + options.footer + '</p>');

	$(".global.modal").modal(options.options);
};

polygon.CreateControl = function(Options)
{
	// Properties:
	// Options.Container - specified the name of the container
	// Options.Properties - specifies additional properties
	// Options.PopulateCallback - callback for populateRow
	// Options.AjaxOptions - sets the options for $.ajax
	// Options.ExtraComponents - components to show/hide that are not included here by default

	if (Options.Container == undefined) return false;
	if (Options.AjaxConfig == undefined) return false;

	var Control = {};

	if (Options.Properties != undefined)
	{
		$.each(Options.Properties, function(Property, Value)
		{
			Control[Property] = Value;
		});
	}

	Control.Page = 1;
	Control.Container = Options.Container;

	Control.Display = function(Page)
	{
		if (Page != undefined) Control.Page = Page;

		$("." + Options.Container + "-container .items").empty();
		$("." + Options.Container + "-container .no-items").addClass("d-none");
		$("." + Options.Container + "-container .pagination").addClass("d-none");
		$("." + Options.Container + "-container .loading").removeClass("d-none");

		if (Options.ExtraComponents != undefined)
		{
			$.each(Options.ExtraComponents, function(_, Component)
			{
				$("." + Options.Container + "-container " + Component).addClass("d-none");
			});
		}

		Control.AjaxConfig = Options.AjaxConfig(Control);
		Control.AjaxConfig.data.Page = Control.Page;
		Control.AjaxConfig.success = function(Data)
		{
			$("." + Options.Container + "-container .loading").addClass("d-none");

			if (Options.ExtraComponents != undefined)
			{
				$.each(Options.ExtraComponents, function(_, Component)
				{
					$("." + Options.Container + "-container " + Component).removeClass("d-none");
				});
			}

			polygon.pagination.handle(Options.Container, Control.Page, Data.pages);

			if (Data.items == undefined)
			{
				$("." + Options.Container + "-container .no-items").html(Data.message);
				$("." + Options.Container + "-container .no-items").removeClass("d-none");
				return;
			}

			polygon.populateRow(Options.Container, Data.items, Options.PopulateCallback);
		}

		$.ajax(Control.AjaxConfig);
	}

	Control.Initialize = function()
	{
		if (!$("." + Options.Container + "-container").length) return;

		$(function()
		{
			if (Options.Initializers != undefined) Options.Initializers(Control);
			polygon.pagination.register(Options.Container, Control.Display); 
			Control.Display();
		});
	}

	return Control;
}

polygon.populate = function(Data, TemplateName, Container, ExtendTemplate)
{	
	$.each(Data, function(_, Item)
	{
		var Template = $(TemplateName).clone();

		if (ExtendTemplate != undefined)
		{
			TemplateExtension = ExtendTemplate(Item, Template);
			Item = TemplateExtension.Item;
			Template = TemplateExtension.Template;
		}

		Template.html(function(_, html)
		{
			for (let key in Item) html = html.replace(new RegExp("\\$" + key, "g"), Item[key]);
			return html;
		});

		if (Template.find("img").attr("data-src"))
			Template.find("img").attr("src", Template.find("img").attr("data-src"));

		Template = Template.contents().unwrap();

		Template.appendTo(Container);
	});
}

polygon.populateRow = function(Control, Data, ExtendTemplate)
{
	polygon.populate(Data, "." + Control + "-container .template", "." + Control + "-container .items", ExtendTemplate);
	polygon.registerHandlers(Control);
}

polygon.registerHandlers = function(control)
{
	if(control == undefined)
	{
		if($("[data-toggle='tooltip']").length)
			$("[data-toggle='tooltip']").tooltip();

		if($("input[data-toggle='toggle']").length)
			$("input[data-toggle='toggle']").bootstrapToggle();

		$(".app img").each(function()
		{ 
			if($(this).attr("data-src") === undefined) return;
			if($(this).attr("data-src").charAt(0) == "$") return;
			
			$(this).attr("src", $(this).attr("data-src")); 
			$(this).removeAttr("data-src");
		});
	}
	else
	{
		if($("." + control + "-container [data-toggle='tooltip']").length)
			$("." + control + "-container [data-toggle='tooltip']").tooltip();

		if($("." + control + "-container input[data-toggle='toggle']").length)
			$("." + control + "-container input[data-toggle='toggle']").bootstrapToggle();

		if($("." + control + "-container .item .details").length)
			$("." + control + "-container .item").hover(function(){ $(this).find(".details").removeClass("d-none"); }, function(){ $(this).find(".details").addClass("d-none"); });

		$("." + control + "-container  .items img").each(function()
		{ 
			if($(this).attr("data-src") === undefined) return;
			$(this).attr("src", $(this).attr("data-src")); 
			$(this).removeAttr("data-src");
		});

		if($("." + control + "-container .accordion").length)
		{
			if($.ui == undefined) throw "Accordion widget detected for " + control + " applet but jQuery UI is not loaded";

			if($("." + control + "-container .accordion").hasClass("ui-accordion"))
				$("." + control + "-container .accordion").accordion("destroy");

			$("." + control + "-container .accordion").accordion({ autoHeight: false, collapsible: true });
		}
	}
}

polygon.pagination = 
{
	register: function(control, callback)
	{
		var pagination = "." + control + "-container .pagination";
		var page;

		if (!$(pagination).length) return;

		$(pagination + " .back").click(function()
		{
			callback(+$(pagination + " .page").val() - 1);
		});
		$(pagination + " .next").click(function()
		{
			callback(+$(pagination + " .page").val() + 1);
		});

		$(pagination + " .page").on("focusout keypress", this, function(event)
		{
			page = $(this).val();

			if (isNaN(page) || page < 1) page = 1;

			if (event.type == "keypress")
				if (event.which == 13) $(this).blur(); else return;

			if (page == $(this).attr("data-last-page")) return;
			$(this).attr("data-last-page", page);
			callback(page);
		});
	},

	handle: function(control, page, pages)
	{
		var pagination = "." + control + "-container .pagination";

		if (page > pages) page = pages;
		if (isNaN(page) || page < 1) page = 1;

		if (!$(pagination).length) return;
		if (pages <= 1 || pages == undefined) return $(pagination).addClass("d-none");

		$(pagination).removeClass("d-none");
		$(pagination + " .pages").text(pages);

		if ($(pagination + " .page").prop("tagName") == "INPUT") $(pagination + " .page").val(page);
		else $(pagination + " .page").text(page);

		if (page <= 1) $(pagination + " .back").attr("disabled", "disabled");
		else $(pagination + " .back").removeAttr("disabled");

		if (page >= pages) $(pagination + " .next").attr("disabled", "disabled");
		else $(pagination + " .next").removeAttr("disabled");
	}
}

polygon.appendination =
{ 
	register: function(Control, Threshold)
	{
		if (!$("."+Control.Control+"-container").length) return false;

		$("body").on("click", "."+Control.Control+"-container .show-more", function(){ Control.Display(true); });

		$(window).scroll(function() 
		{
			if (Control.Loading || Control.ReachedEnd) return;
			if ($(window).scrollTop() + $(window).height() < $(document).height() - Threshold) return;
			Control.Display(true);
		});

		$(function(){ Control.Display(false) });

		return true;
	},

	handle: function(Control, Data)
	{
		if (Control.Page < Data.pages)
		{
			$("."+Control.Control+"-container .show-more").removeClass("d-none");
			Control.ReachedEnd = false;
		}
		else
		{
			Control.ReachedEnd = true;
		}
	}
}

toastr.options = 
{
	"closeButton": false,
	"debug": false,
	"newestOnTop": false,
	"progressBar": true,
	"positionClass": "toast-top-right",
	"preventDuplicates": false,
	"onclick": null,
	"showDuration": "300",
	"hideDuration": "1000",
	"timeOut": "10000",
	"extendedTimeOut": "1000",
	"showEasing": "swing",
	"hideEasing": "linear",
	"showMethod": "fadeIn",
	"hideMethod": "fadeOut"
}

$(function()
{
	polygon.registerHandlers();
	if (polygon.user.logged_in)
	{
		setInterval(function()
		{
			if (document.hidden) return;
			$.post("/api/account/update-ping", function(data)
			{
				if (data.friendRequests) $(".friend-requests-indicator").text(data.friendRequests).removeClass("d-none");
				else $(".friend-requests-indicator").addClass("d-none");
				if (data.status == 401) window.location.reload();
			});
		}, 30000);
	}	
});