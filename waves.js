if (hasWebGLSupportWithExtensions(['OES_texture_float', 'OES_texture_float_linear'])) {

    var simulatorCanvas = document.getElementById(SIMULATOR_CANVAS_ID),
        overlayDiv = document.getElementById(OVERLAY_DIV_ID),
        uiDiv = document.getElementById(UI_DIV_ID);

    var camera = new Camera(),
        projectionMatrix = makePerspectiveMatrix(new Float32Array(16), FOV, MIN_ASPECT, NEAR, FAR);

    var simulator = new Simulator(simulatorCanvas, window.innerWidth, window.innerHeight);
    window.wavesSimulator = simulator;

    var width = window.innerWidth,
        height = window.innerHeight;

    var lastMouseX = 0;
    var lastMouseY = 0;
    var mode = NONE;

    var onMouseDown = function (event) {
        event.preventDefault();

        var mousePosition = getMousePosition(event, uiDiv);
        mode = ORBITING;
        lastMouseX = mousePosition.x;
        lastMouseY = mousePosition.y;
    };
    overlayDiv.addEventListener('mousedown', onMouseDown, false);

    overlayDiv.addEventListener('mousemove', function (event) {
        event.preventDefault();

        var mousePosition = getMousePosition(event, uiDiv),
            mouseX = mousePosition.x,
            mouseY = mousePosition.y;

        if (mode === ORBITING) {
            overlayDiv.style.cursor = 'grabbing';
            camera.changeAzimuth((mouseX - lastMouseX) / width * SENSITIVITY);
            camera.changeElevation((mouseY - lastMouseY) / height * SENSITIVITY);
            lastMouseX = mouseX;
            lastMouseY = mouseY;
        } else {
            overlayDiv.style.cursor = 'grab';
        }
    });

    overlayDiv.addEventListener('mouseup', function (event) {
        event.preventDefault();
        mode = NONE;
    });

    window.addEventListener('mouseout', function (event) {
        var from = event.relatedTarget || event.toElement;
        if (!from || from.nodeName === 'HTML') {
            mode = NONE;
        }
    });

    var onresize = function () {
        var windowWidth = window.innerWidth,
            windowHeight = window.innerHeight;

        overlayDiv.style.width = windowWidth + 'px';
        overlayDiv.style.height = windowHeight + 'px';

        if (windowWidth / windowHeight > MIN_ASPECT) {
            makePerspectiveMatrix(projectionMatrix, FOV, windowWidth / windowHeight, NEAR, FAR);
            simulator.resize(windowWidth, windowHeight);
            uiDiv.style.width = windowWidth + 'px';
            uiDiv.style.height = windowHeight + 'px';
            simulatorCanvas.style.top = '0px';
            uiDiv.style.top = '0px';
            width = windowWidth;
            height = windowHeight;
        } else {
            var newHeight = windowWidth / MIN_ASPECT;
            makePerspectiveMatrix(projectionMatrix, FOV, windowWidth / newHeight, NEAR, FAR);
            simulator.resize(windowWidth, newHeight);
            simulatorCanvas.style.top = (windowHeight - newHeight) * 0.5 + 'px';
            uiDiv.style.top = (windowHeight - newHeight) * 0.5 + 'px';
            uiDiv.style.width = windowWidth + 'px';
            uiDiv.style.height = newHeight + 'px';
            width = windowWidth;
            height = newHeight;
        }
    };

    window.addEventListener('resize', onresize);
    onresize();

    var lastTime = (new Date()).getTime();
    var render = function render(currentTime) {
        var deltaTime = (currentTime - lastTime) / 1000 || 0.0;
        lastTime = currentTime;

        simulator.render(deltaTime, projectionMatrix, camera.getViewMatrix(), camera.getPosition());
        requestAnimationFrame(render);
    };
    render();

} else {
    document.getElementById('error').style.display = 'block';
}
