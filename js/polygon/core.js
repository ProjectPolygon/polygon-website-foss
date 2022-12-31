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
	if (options.options == undefined) options.options = "show";
	if (options.fade == undefined) $(".global.modal").addClass("fade");
	else if (!options.fade) $(".global.modal").removeClass("fade");
	var footer = $(".global.modal .modal-footer .mx-auto");
	$(".global.modal .modal-title").html(options.header);
	$(".global.modal .modal-body").html(options.body);

	footer.empty();
	$.each(options.buttons, function(_, button)
	{
		var buttonCode = '<button type="button" class="' + button.class + ' btn-lg text-center mx-1"';
		// todo - improve how attributes are handled
		// right now its like {"attr": "data-whatever", "val": 1} instead of just being like {"data-whatever": 1}
		if (button.attributes != undefined) $.each(button.attributes, function(_, attr)
		{
			buttonCode += ' ' + attr.attr + '="' + attr.val + '"';
		});
		if (button.dismiss) buttonCode += ' data-dismiss="modal"';
		buttonCode += '><h4 class="font-weight-normal pb-0">' + button.text + '</h4></button>';
		footer.append(buttonCode);
	});

	if (options.footer) footer.append('<p class="text-muted mt-3 mb-0">' + options.footer + '</p>');

	$(".global.modal").modal(options.options);
};

polygon.populate = function(data, template, container, keepWrapper)
{
	if(keepWrapper == null) keepWrapper = true;
	
	$.each(data, function(_, item)
	{
		var templateCode = $(template).clone();
		templateCode.html(function(_, html)
		{
			for (let key in item) html = html.replace(new RegExp("\\$" + key, "g"), item[key]);
			return html;
		});

		if (templateCode.find("img").attr("preload-src"))
			templateCode.find("img").attr("src", templateCode.find("img").attr("preload-src"));

		if(!keepWrapper) templateCode = templateCode.contents().unwrap();

		templateCode.appendTo(container);
	});
}

polygon.populateRow = function(control, data)
{
	polygon.populate(data, "." + control + "-container .template .item", "." + control + "-container .items");
	polygon.registerHandlers(control);
}

polygon.populateAccordion = function(control, data)
{
	polygon.populate(data, "." + control + "-container .accordion-template", "." + control + "-container .accordion", false);
	polygon.registerHandlers(control);
}

polygon.registerHandlers = function(control)
{
	if(control == undefined)
	{
		if($("[data-toggle='tooltip']").length)
			$("[data-toggle='tooltip']").tooltip();

		if($("input[data-toggle='toggle']").length)
			$("input[data-toggle='toggle']").bootstrapToggle();
	}
	else
	{
		if($("." + control + "-container [data-toggle='tooltip']").length)
			$("." + control + "-container [data-toggle='tooltip']").tooltip();

		if($("." + control + "-container input[data-toggle='toggle']").length)
			$("." + control + "-container input[data-toggle='toggle']").bootstrapToggle();

		if($("." + control + "-container .item .details").length)
			$("." + control + "-container .item").hover(function(){ $(this).find(".details").removeClass("d-none"); }, function(){ $(this).find(".details").addClass("d-none"); });

		$("." + control + "-container img").each(function()
		{ 
			if($(this).attr("preload-src") === undefined) return;
			$(this).attr("src", $(this).attr("preload-src")); 
			$(this).removeAttr("preload-src");
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
	register: function(object, threshold)
	{
		if(!$("."+object.control+"-container").length) return false;

		$("body").on("click", "."+object.control+"-container .show-more", function(){ object.load(true); });

		$(window).scroll(function() 
		{
			if(object.loading || object.reached_end) return;
			if($(window).scrollTop() + $(window).height() < $(document).height() - threshold) return;
			object.load(true);
		});

		$(function(){ object.load(false) });

		return true;
	},

	handle: function(object, data)
	{
		if(object.page < data.pages)
		{
			$("."+object.control+"-container .show-more").removeClass("d-none");
			object.reached_end = false;
		}
		else
		{
			object.reached_end = true;
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