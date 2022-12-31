'use strict';
$(function() 
{
  function callback(obj) 
  {
    var mainHeader = $(".nav-open");
    if (obj !== undefined) 
    {
      mainHeader = mainHeader.not(obj);
    }

    mainHeader = mainHeader.not(".nav-container");
    if (item.hasClass("nav-open")) 
    {
      item.toggleClass("universal-search-open", false);
      item.addClass("closing").delay(300).queue(function(metadataCallback) 
      {
        $(this).removeClass("closing");
        metadataCallback();
      });
    }

    mainHeader.toggleClass("nav-open");
    if (obj !== undefined) 
    {
      obj.toggleClass("nav-open");
    }
  }

  function scoringValidation(event) 
  {
    var codes = $(".header-2014 .search .universal-search-option");
    var i = -1;

    $.each(codes, function(maxAtomIndex, nextElement) 
    {
      if ($(nextElement).hasClass("selected")) 
      {
        $(nextElement).removeClass("selected");
        i = maxAtomIndex;
      }
    });

    i = i + (event.which === 38 ? codes.length - 1 : 1);
    i = i % codes.length;

    $(codes[i]).addClass("selected");
  }

  var debuggerContainer = $(".navigation-container");
  if (debuggerContainer.length > 0) 
  {
    debuggerContainer.mCustomScrollbar({
      theme : "dark-3",
      scrollInertia : 0,
      autoHideScrollbar : false,
      autoExpandScrollbar : false,
      scrollButtons : 
      {
        enable : false
      }
    });
  }

  $("#navigation .notification-icon.tooltip-right").tipsy();
  $(".nav-icon .notification-icon.tooltip-bottom").tipsy();

  var expectedFloor = 1359;
  var viewportCenter = 1480;
  var actualCeil = Roblox.FixedUI.getWindowWidth();

  var satisfiesLowerLimit = actualCeil >= expectedFloor;
  var item = $(".header-2014 .search");
  var fakeInputElement = $(".header-2014 .search input");
  var mainHeader = $(".nav-container");

  if (Roblox.FixedUI.isMobile) 
  {
    $("#navigation").addClass("mobile");
  }

  if (!mainHeader.hasClass("no-gutter-ads") && actualCeil < viewportCenter) 
  {
    satisfiesLowerLimit = false;
  }

  if (satisfiesLowerLimit) 
  {
    mainHeader.addClass("nav-open-static");
  }

  if ($("#navigation").length == 0) 
  {
    $("#navContent").css(
    {
      "margin-left" : "0px",
      width : "100%"
    });

    $(".nav-container .nav-icon").css("display", "none");
    $(".header-2014 .logo").css("margin", "3px 0 0 45px");
    $("#navContent").addClass("nav-no-left");
  }

  $(".nav-icon").on("click", function() 
  {
    if (mainHeader.hasClass("nav-open-static")) 
    {
      mainHeader.removeClass("nav-open-static");
    } 
    else 
    {
      mainHeader.toggleClass("nav-open");
    }
  });

  $(".tickets-icon, .robux-icon, .tickets-amount, .robux-amount, .settings-icon").on("click mouseover mouseout", function(event) 
  {
    event.stopPropagation();
    event.preventDefault();
    callback($(this).parent());
  });

  $("#lsLoginStatus").on("click", function(event) 
  {
    event.stopPropagation();
    event.preventDefault();
    var clicked_element = $("#lsLoginStatus");
    var form = clicked_element.closest("form");
    if (form.length === 0) 
    {
      form = $("<form></form>").appendTo("body");
    }
    form.attr("action", clicked_element.attr("href"));
    form.attr("method", "post");
    form.submit();
  });

  $(".header-2014 .search-icon").on("click", function(event) 
  {
    var c;
    var divel;
    var s;
    event.stopPropagation();
    c = fakeInputElement.val();

    if (c.length > 2 && item.hasClass("universal-search-open")) 
    {
      divel = $(".header-2014 .search .universal-search-option.selected");
      s = divel.data("searchurl");
      window.location = s + encodeURIComponent(c);
    } 
    else 
    {
      callback(item);
      fakeInputElement.focus();
    }
  });

  $(window).resize(function() 
  {
    var reconnectTryTimes = Roblox.FixedUI.getWindowWidth();
    var interestingPoint = expectedFloor;

    if (!mainHeader.hasClass("no-gutter-ads")) 
    {
      interestingPoint = viewportCenter;
    }

    if (reconnectTryTimes >= interestingPoint && !(mainHeader.hasClass("nav-open") || mainHeader.hasClass("nav-open-static"))) 
    {
      mainHeader.addClass("nav-open");
    }

    item.toggleClass("universal-search-open", false);
    callback();
  });

  $(".search input").on("keydown", function(event) 
  {
    var expRecords = $(this).val();
    if ((event.which === 9 || event.which === 38 || event.which === 40) && expRecords.length > 0) 
    {
      event.stopPropagation();
      event.preventDefault();
      scoringValidation(event);
    }
  });

  $(".search input").on("keyup", function(event) 
  {
    var param = $(this).val();
    var divel;
    var url;
    if (event.which === 13) 
    {
      event.stopPropagation();
      event.preventDefault();
      divel = $(".header-2014 .search .universal-search-option.selected");
      url = divel.data("searchurl");
      if (param.length > 2) 
      {
        window.location = url + encodeURIComponent(param);
      }
    } 
    else 
    {
      if (param.length > 0) 
      {
        item.toggleClass("universal-search-open", true);
        $(".header-2014 .search .universal-search-dropdown .universal-search-string").text('"' + param + '"');
      } 
      else 
      {
        item.toggleClass("universal-search-open", false);
      }
    }
  });

  $(".header-2014 .search .universal-search-option").on("click touchstart", function(event) 
  {
    var hash;
    var u;
    event.stopPropagation();
    hash = fakeInputElement.val();
    if (hash.length > 2) 
    {
      u = $(this).data("searchurl");
      window.location = u + encodeURIComponent(hash);
    }
  });

  $(".header-2014 .search .universal-search-option").on("mouseover", function() 
  {
    $(".header-2014 .search .universal-search-option").removeClass("selected");
    $(this).addClass("selected");
  });

  $(".search input").on("focus", function() 
  {
    var expRecords = fakeInputElement.val();
    if (expRecords.length > 0) 
    {
      item.addClass("universal-search-open");
    }
  });

  $(".search input").on("click", function(event) 
  {
    event.stopPropagation();
  });

  $(".under-13").tipsy({ gravity : "n" });

  $(".nav-content, .navigation, .header-2014").on("click", function() 
  {
    callback(undefined);
    item.toggleClass("universal-search-open", false);
  });

  $(".navigation, .notifications-container, .tickets-container, .robux-container").on("click", "a, a > span", function(event) 
  {
    event.stopPropagation();
  });
});
