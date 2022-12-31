(function() {
  /**
   * @return {?}
   */
  function updateClockElement() {
    /** @type {string} */
    var myNav = navigator.userAgent.toLowerCase();
    return myNav.indexOf("msie") != -1 ? parseInt(myNav.split("msie")[1]) : false;
  }
  /**
   * @return {undefined}
   */
  function accept_specialinput() {
    if (updateClockElement() < 11 && updateClockElement() !== false) {
      // Roblox.GenericModal.open(_imageWin, "/images/Icons/img-alert.png", InsufficientIEText);
      polygon.buildModal({ header: _imageWin, image: "/img/error.png", body: InsufficientIEText, buttons: [{class: 'btn btn-primary px-4', dismiss: true, text: 'OK'}] });
    } else {
      // Roblox.GenericModal.open(_imageWin, "/images/Icons/img-alert.png", UnsupportedText);
      polygon.buildModal({ header: _imageWin, image: "/img/error.png", body: UnsupportedText, buttons: [{class: 'btn btn-primary px-4', dismiss: true, text: 'OK'}] });
    }
  }
  /**
   * @return {?}
   */
  function init() {
    try {
      /** @type {!Element} */
      var canvas = document.createElement("canvas");
      return !!window.WebGLRenderingContext && (canvas.getContext("webgl") || canvas.getContext("experimental-webgl"));
    } catch (t) {
      return false;
    }
  }
  /**
   * @return {?}
   */
  function updateStates() {
    return $(ThumbnailHolder).data("3d-thumbs-enabled") !== undefined ? true : false;
  }
  /**
   * @return {?}
   */
  function resolve() {
    return callback(CanUseThreeDee) === true;
  }
  /**
   * @param {boolean} data
   * @return {undefined}
   */
  function fieldsWatch(data) {
    getData(CanUseThreeDee, data);
  }
  /**
   * @param {string} db
   * @param {boolean} store
   * @return {undefined}
   */
  function getData(db, store) {
    if (typeof localStorage != "undefined" && localStorage != null) {
      localStorage.setItem(db, store);
    }
  }
  /**
   * @param {string} db
   * @return {?}
   */
  function callback(db) {
    if (typeof localStorage != "undefined" && localStorage != null) {
      var args = localStorage.getItem(db);
      return args == "true" || false;
    }
    return false;
  }
  /**
   * @return {undefined}
   */
  function start() {
    if (updateStates()) {
      /**
       * @param {boolean} dirMagnitude
       * @return {undefined}
       */
      var blockButtonFunc = function(dirMagnitude) {
        if (dirMagnitude === true) {
          ThreeDeeToggle.text("Disable 3D");
        } else {
          ThreeDeeToggle.text("Enable 3D");
        }
      };
      var dir = resolve();
      var ThreeDeeToggle = $(".enable-three-dee");
      blockButtonFunc(dir);
      ThreeDeeToggle.css("visibility", "visible");
      ThreeDeeToggle.on("click", function() {
        if (!init()) {
          accept_specialinput();
          return;
        }
        /** @type {boolean} */
        dir = !dir;
        fieldsWatch(dir);
        if (dir === false) {
          click();
        } else {
          upload();
        }
        blockButtonFunc(dir);
      });
    }
  }
  /**
   * @return {undefined}
   */
  function install() {
    if (f) {
      var e = ThumbnailContainer.find(ThumbnailHolder);
      var r = e.data("url");
      ThumbnailContainer.load(r, function() {
        ThumbnailImg = ThumbnailContainer.find(ThumbnailSpan);
        go();
      });
    }
  }
  /**
   * @return {undefined}
   */
  function go() {
    if (init() && resolve() && updateStates()) {
      upload();
      start();
    } else {
      click();
      start();
    }
  }
  /**
   * @return {undefined}
   */
  function upload() {
    createForm();
    try {
      e = ThumbnailImg.load3DThumbnail(function(labelElements) {
        ThumbnailContainer.find("canvas").not(labelElements).remove();
      }, function(error){ console.log(error); });
    } catch (conv_reverse_sort) {
      console.log("An error occurred while loading 3D thumb:");
      console.log(conv_reverse_sort);
      click();
    }
  }
  /**
   * @return {undefined}
   */
  function exec() {
    if (e !== undefined) {
      e.cancel();
    }
  }
  /**
   * @return {undefined}
   */
  function d() {
    exec();
    ThumbnailContainer.find("canvas").remove();
    ThumbnailContainer.find(".thumbnail-spinner").remove();
  }
  /**
   * @return {undefined}
   */
  function createForm() {
    var $trashTreeContextMenu = ThumbnailContainer.find(ThumbnailSpan + " > img");
    $trashTreeContextMenu.hide();
  }
  /**
   * @return {undefined}
   */
  function _updateVolumeImg() {
    if (f) {
      ThumbnailImg.find(" > img").attr("src", SpinnerLocation);
    }
  }
  /**
   * @return {undefined}
   */
  function click() {
    d();
    ThumbnailImg.find(" > img").show();
    var expRecords = ThumbnailContainer.find("span[data-retry-url]");
    if (expRecords.length > 0) {
      _updateVolumeImg();
      // expRecords.loadRobloxThumbnails();
    }
  }
  /** @type {boolean} */
  var f = false;
  var ThumbnailContainer;
  var ThumbnailImg;
  var e;
  /** @type {string} */
  var ThumbnailHolder = ".thumbnail-holder";
  /** @type {string} */
  var ThumbnailSpan = ".thumbnail-span";
  /** @type {string} */
  var SpinnerLocation = "/images/Spinners/ajax_loader_blue_300.gif";
  /** @type {string} */
  var _imageWin = "3D Thumbnails";
  /** @type {string} */
  var UnsupportedText = "Your browser or operating system does not support 3D thumbnails.";
  /** @type {string} */
  var InsufficientIEText = "Please upgrade to the latest version of <a href='http://www.microsoft.com/InternetExplorer'>Internet Explorer</a> in order to view 3D thumbnails.";
  /** @type {string} */
  var CanUseThreeDee = "RobloxUse3DThumbnails";

  $(function() {
    if ($(ThumbnailHolder).length > 0) {
      if ($(ThumbnailHolder).data("reset-enabled-every-page") !== undefined) {
        fieldsWatch(false);
      }
      ThumbnailContainer = $(ThumbnailHolder).parent();
      ThumbnailImg = ThumbnailContainer.find(ThumbnailSpan);
      go();
      /** @type {boolean} */
      f = true;
    }
  });

  return { showSpinner : _updateVolumeImg, reloadThumbnail : install };
})();
