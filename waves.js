if (hasWebGLSupportWithExtensions(['OES_texture_float', 'OES_texture_float_linear'])) {

    try {

    var simulatorCanvas = document.getElementById(SIMULATOR_CANVAS_ID),
        overlayDiv = document.getElementById(OVERLAY_DIV_ID),
        uiDiv = document.getElementById(UI_DIV_ID),
        isWideLayout = document.body.classList.contains('layout-wide'),
        reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        orbitSensitivity = reducedMotion ? SENSITIVITY * 0.35 : SENSITIVITY;

    var camera = new Camera(),
        projectionMatrix = makePerspectiveMatrix(new Float32Array(16), FOV, MIN_ASPECT, NEAR, FAR);

    var simulator = new Simulator(simulatorCanvas, window.innerWidth, window.innerHeight);
    window.wavesSimulator = simulator;

    var width = window.innerWidth,
        height = window.innerHeight;

    var lastPointerX = 0;
    var lastPointerY = 0;
    var isDragging = false;

    function pointerPosition(event) {
        if (event.touches && event.touches.length > 0) {
            return {
                x: event.touches[0].clientX - uiDiv.getBoundingClientRect().left,
                y: event.touches[0].clientY - uiDiv.getBoundingClientRect().top
            };
        }

        return getMousePosition(event, uiDiv);
    }

    function onPointerDown(event) {
        if (event.type === 'touchstart') {
            event.preventDefault();
        }

        var position = pointerPosition(event);
        isDragging = true;
        lastPointerX = position.x;
        lastPointerY = position.y;
        overlayDiv.style.cursor = 'grabbing';
    }

    function onPointerMove(event) {
        if (!isDragging) {
            overlayDiv.style.cursor = 'grab';
            return;
        }

        if (event.type === 'touchmove') {
            event.preventDefault();
        }

        var position = pointerPosition(event);
        camera.changeAzimuth((position.x - lastPointerX) / width * orbitSensitivity);
        camera.changeElevation((position.y - lastPointerY) / height * orbitSensitivity);
        lastPointerX = position.x;
        lastPointerY = position.y;
        overlayDiv.style.cursor = 'grabbing';
    }

    function onPointerUp(event) {
        if (event.type === 'touchend') {
            event.preventDefault();
        }

        isDragging = false;
        overlayDiv.style.cursor = 'grab';
    }

    overlayDiv.addEventListener('mousedown', onPointerDown, false);
    overlayDiv.addEventListener('mousemove', onPointerMove, false);
    overlayDiv.addEventListener('mouseup', onPointerUp, false);
    overlayDiv.addEventListener('mouseleave', onPointerUp, false);
    overlayDiv.addEventListener('touchstart', onPointerDown, { passive: false });
    overlayDiv.addEventListener('touchmove', onPointerMove, { passive: false });
    overlayDiv.addEventListener('touchend', onPointerUp, { passive: false });
    overlayDiv.addEventListener('touchcancel', onPointerUp, { passive: false });

    var keyOrbitStep = 0.02;

    function onKeyDown(event) {
        var target = event.target;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT')) {
            return;
        }

        var step = orbitSensitivity * keyOrbitStep;
        switch (event.key) {
            case 'ArrowLeft':
                camera.changeAzimuth(-step);
                event.preventDefault();
                break;
            case 'ArrowRight':
                camera.changeAzimuth(step);
                event.preventDefault();
                break;
            case 'ArrowUp':
                camera.changeElevation(-step);
                event.preventDefault();
                break;
            case 'ArrowDown':
                camera.changeElevation(step);
                event.preventDefault();
                break;
            default:
                break;
        }
    }

    window.addEventListener('keydown', onKeyDown, false);
    overlayDiv.focus();

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
            if (!isWideLayout) {
                simulatorCanvas.style.top = '0px';
                uiDiv.style.top = '0px';
            }
            width = windowWidth;
            height = windowHeight;
        } else {
            var newHeight = windowWidth / MIN_ASPECT;
            makePerspectiveMatrix(projectionMatrix, FOV, windowWidth / newHeight, NEAR, FAR);
            simulator.resize(windowWidth, newHeight);
            if (!isWideLayout) {
                simulatorCanvas.style.top = (windowHeight - newHeight) * 0.5 + 'px';
                uiDiv.style.top = (windowHeight - newHeight) * 0.5 + 'px';
            }
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

    } catch (error) {
        console.error(error);
        document.getElementById('error').textContent = 'Unable to initialize the wave simulation.';
        document.getElementById('error').style.display = 'block';
    }

} else {
    document.getElementById('error').style.display = 'block';
}
