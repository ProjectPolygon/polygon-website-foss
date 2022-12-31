/*
    Usage:
    <div class="thumbnail-holder">
        <span class="thumbnail-span" data-3d-url="urlIsHere">
              <- Canvas will be added here
            <img>2D Image</img>
        </span>
    </div>

    $("element").load3DThumbnail(function(canvas) {
        //complete!
    });

    The element being called must have a data-3d-url attribute
    While it's loading the OBJ & MTL files, it creates a CSS loading spinner inside the new div.
*/

(function () {
    // constants
    var fieldOfView = 70;
    var minRenderDistance = 0.1;
    var maxRenderDistance = 1000;
    var loadingWaitBeforeShowingSpinner = 500;  // milliseconds - for UX, don't show the spinner immediately
    var maximumPollsToGet3DThumbnailJson = 10;
    var retryInterval = 2 * 1000;

    //WebGLRenderer can be reused by multiple scenes
    var renderer;

    //hack because IE8 doesn't like that three.min.js uses getters and setters. 
    //Adding these scripts in the PageScript would break the whole page.
    function getCachedScript(url) {
        // Use $.ajax() since it is more flexible than $.getScript
        // Return the jqXHR object so we can chain callbacks
        return $.ajax({
            dataType: "script",
            cache: true,
            url: url
        });
    };

    function getScripts(inserts, callback) {
        var nextInsert = inserts.shift();
        if (nextInsert != undefined) {
            getCachedScript(nextInsert).done(function () {
                getScripts(inserts, callback);
            });
        }
        else {
            if (callback != undefined)
                callback();
        }
    };

    var scriptsLoaded = false;
    function loadScriptsIfNeeded(callback, scriptsToLoadCsv) {
        if (scriptsLoaded) {
            callback();
        }
        else {
            // Bcause IE8 doesn't like that three.min.js uses getters and setters. 
            // Adding these scripts in the PageScript would break the whole page.

            var scriptsToLoad = scriptsToLoadCsv.split(",");

            getScripts(scriptsToLoad, function () {
                scriptsLoaded = true;
                callback();
            });
        }
    }

    function addLightsToScene(scene) {
        //TODO get these values in the thumbnail JSON from obj exporter
        var ambient = new THREE.AmbientLight(0x878780);
        scene.add(ambient);

        var sunLight = new THREE.DirectionalLight(0xACACAC);
        sunLight.position.set(-0.671597898, 0.671597898, 0.312909544).normalize();
        scene.add(sunLight);

        var backLight = new THREE.DirectionalLight(0x444444);
        var backLightPos = new THREE.Vector3().copy(sunLight.position).negate().normalize(); //inverse of sun direction
        backLight.position.set(backLightPos);
        scene.add(backLight);
    }

    function clearContainer(container) {
        container.find(".thumbnail-spinner").remove();
        container.find("canvas").remove();
    }

    $.fn.load3DThumbnail = function (callback, onError) {
        var cancelled = false;

        function cancelLoading() {
            cancelled = true;
        }

        function getThumbnailJson(url, callbackAfterJsonRetrieved) {
            var retries = 0;
            $.get(url, function (data) {
                if (data.Final) {                    
                    $.getJSON(data.Url, function (json) {
                        callbackAfterJsonRetrieved(json);
                    })
                    .fail(function () {
                        onError("3D Thumbnail failed to load");
                    });
                }
                else {
                    if (retries < maximumPollsToGet3DThumbnailJson && (cancelled == false)) {
                        setTimeout(function () {
                             getThumbnailJson(url, callbackAfterJsonRetrieved);
                        }, retryInterval);
                        retries += 1;
                    }
                }
            });
        }

        function loadObjAndMtl(modelHash, mtlHash, container, json, callbackAfterLoaderIsDone) {

            var baseUri = "/thumbnail/resolve-hash/"; //server should provide
            var containerHeight = container.width(); //container.height();
            var containerWidth = container.width();
            var camera = new THREE.PerspectiveCamera(fieldOfView, containerWidth / containerHeight, minRenderDistance, maxRenderDistance);
            var scene = new THREE.Scene();
            var controls;

            function render() {
                renderer.render(scene, camera);
            }

            function animate() {
                if (controls.enabled) {
                    controls.update();
                }

                TWEEN.update();
                render();
                requestAnimationFrame(animate);
            }

            function createCanvas() {
                renderer.setSize(containerWidth, containerHeight);
                var canvas = renderer.domElement;

                container.resize(function () {
                    camera.aspect = container.width() / container.height();
                    camera.updateProjectionMatrix();
                    renderer.setSize(container.width(), container.height());
                    controls.handleResize();
                });

                window.onbeforeunload = function () {
                    // canvas goes black when navigating to another page
                    $(canvas).hide();
                }
                return canvas;
            }

            function initializeControls() {
                // The controller that lets us spin the camera around an object   
                var orbitControls = new THREE.OrbitControls(camera, container.get(0), json);
                orbitControls.rotateSpeed = 1.5;
                orbitControls.zoomSpeed = 1.5;
                orbitControls.dynamicDampingFactor = 0.3;
                orbitControls.addEventListener("change", render);

                return orbitControls;
            }

            function objAndMtlLoaded(modelObject) {
                addLightsToScene(scene);
                scene.add(modelObject);
                var canvas = createCanvas();
                controls = initializeControls();
                render();
                animate();
                callbackAfterLoaderIsDone(canvas);
            };

            // ReSharper disable once InconsistentNaming
            var loader = new THREE.OBJMTLLoader();
            loader.load(baseUri, modelHash, mtlHash, objAndMtlLoaded, undefined, onError);
        }

        this.each(function () {
            try {
                var container = $(this);
                var scriptsCsv = "/js/3D/three.min.js,/js/3D/MTLLoader.js,/js/3D/OBJMTLLoader.js,/js/3D/tween.js,/js/3D/PolygonOrbitControls.js"; //container.data("js-files");
                var loadThreeDee = function () {

                    //Renderer can be reused by multiple scenes
                    if (!renderer) {
                        // ReSharper disable once InconsistentNaming
                        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                    }

                    var jsonUrl = container.data("3d-url");

                    clearContainer(container);

                    var placeholder = $("<div class='thumbnail-spinner'></div>");
                    placeholder.appendTo(container);
                    var loaderVisible = false;

                    function startLoading() {
                        loaderVisible = true;
                        placeholder.height(container.width());
                        placeholder.show();
                        placeholder.empty();
                        setTimeout(function () {
                            if (loaderVisible) {
                                placeholder.addClass("text-center");
                                // placeholder.html("<div class='loader' style='line-height:" + container.height().toString() + "px'>Loading</div><div></div>");
                                placeholder.html("<div class='loader' style='line-height:" + container.width().toString() + "px'>Loading</div><div></div>");
                            }
                        }, loadingWaitBeforeShowingSpinner);
                    }

                    function endLoading() {
                        loaderVisible = false;
                        placeholder.hide();
                        placeholder.empty();
                        placeholder.removeClass("text-center");
                    }

                    startLoading();

                    function updateContainer(json) {
                        loadObjAndMtl(json.obj, json.mtl, container, json, function (canvas) {
                            clearContainer(container);
                            if (cancelled == false) {
                                endLoading();
                                container.append(canvas);
                                callback(canvas);
                            }
                        });
                    };

                    getThumbnailJson(jsonUrl, updateContainer);
                };
                loadScriptsIfNeeded(loadThreeDee, scriptsCsv);
            }
            catch (e) {
                onError(e);
            }
        });
        return {
            cancel: cancelLoading
        }
    }
})();
