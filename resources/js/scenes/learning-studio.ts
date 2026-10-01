import {
    AmbientLight,
    CanvasTexture,
    Color,
    DirectionalLight,
    Group,
    HemisphereLight,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    OrthographicCamera,
    PlaneGeometry,
    Scene,
    SphereGeometry,
    SRGBColorSpace,
    TorusGeometry,
    WebGLRenderer,
    type BufferGeometry,
    type Material,
} from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

export function mountLearningStudio(stage: HTMLElement): void {
    const viewport = stage.querySelector<HTMLElement>('[data-scene-viewport]');
    const controls = stage.querySelector<HTMLElement>('[data-scene-controls]');
    const motionButton = stage.querySelector<HTMLButtonElement>('[data-scene-motion]');
    const hint = stage.querySelector<HTMLElement>('[data-scene-hint]');
    if (!viewport || !controls || !motionButton || !hint) return;

    const geometries = new Set<BufferGeometry>();
    const materials = new Set<Material>();
    const textures: CanvasTexture[] = [];
    const events = new AbortController();
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let renderer: WebGLRenderer | undefined;
    let resizeObserver: ResizeObserver | undefined;
    let visibilityObserver: IntersectionObserver | undefined;
    let themeObserver: MutationObserver | undefined;
    let frame = 0;
    let disposed = false;
    let suspended = false;
    let visible = true;
    let hasSize = false;
    let paused = reducedMotion.matches;
    let pageMotionOff = stage.closest<HTMLElement>('[data-home-experience]')?.dataset.motion === 'off';
    let scrollProgress = Number(stage.dataset.scrollProgress) || 0;
    let lastFrame = 0;
    let elapsed = 0;
    let turn = 0;
    let tilt = 0;
    let pointerId: number | null = null;
    let pointerX = 0;

    const stop = () => {
        window.cancelAnimationFrame(frame);
        frame = 0;
        lastFrame = 0;
    };

    const dispose = () => {
        if (disposed) return;
        disposed = true;
        stop();
        events.abort();
        resizeObserver?.disconnect();
        visibilityObserver?.disconnect();
        themeObserver?.disconnect();
        geometries.forEach((geometry) => geometry.dispose());
        materials.forEach((material) => material.dispose());
        textures.forEach((texture) => texture.dispose());
        renderer?.dispose();
        renderer?.domElement.remove();
        stage.dataset.sceneState = 'fallback';
        controls.hidden = true;
        hint.textContent = 'One step opens the next.';
    };

    try {
        const canvas = document.createElement('canvas');
        canvas.setAttribute('aria-hidden', 'true');
        const context = canvas.getContext('webgl2', {
            alpha: true,
            antialias: true,
            powerPreference: 'low-power',
        });
        if (!context) return;

        renderer = new WebGLRenderer({ canvas, context, alpha: true, antialias: true });
        const gl = renderer;
        gl.debug.onShaderError = () => {
            throw new Error('The learning illustration shader could not be initialized.');
        };
        gl.outputColorSpace = SRGBColorSpace;
        gl.setClearColor(0x000000, 0);
        viewport.append(canvas);

        const scene = new Scene();
        const camera = new OrthographicCamera(-3.6, 3.6, 2.9, -2.9, 0.1, 40);
        camera.position.set(3.1, 2.25, 8);
        camera.lookAt(0, 0.1, 0);

        const sculpture = new Group();
        scene.add(sculpture);
        scene.add(new AmbientLight(0xffffff, 1.25));
        const hemisphere = new HemisphereLight(0xffffff, 0x8f9bad, 1.6);
        scene.add(hemisphere);
        const key = new DirectionalLight(0xffffff, 2.3);
        key.position.set(-3, 5, 6);
        scene.add(key);
        const rim = new DirectionalLight(0xa8baff, 1.8);
        rim.position.set(4, 2, -3);
        scene.add(rim);

        function mesh<G extends BufferGeometry, M extends Material>(geometry: G, material: M) {
            geometries.add(geometry);
            materials.add(material);
            return new Mesh(geometry, material);
        }

        const paper = new MeshStandardMaterial({ color: '#ffffff', roughness: 0.55 });
        const cobalt = new MeshStandardMaterial({ color: '#2448d8', roughness: 0.38, metalness: 0.12 });
        const lime = new MeshStandardMaterial({ color: '#e9f2b7', roughness: 0.48 });
        const pages = new MeshStandardMaterial({ color: '#e6e7e1', roughness: 0.85 });
        const orbitMaterial = new MeshStandardMaterial({ color: '#b8c2cc', roughness: 0.5, metalness: 0.3 });
        const cardMaterials = [paper, cobalt, lime];
        const labels = ['Explore.', 'Understand.', 'Create.'];
        const cards: { group: Group; y: number; surface: HTMLCanvasElement; texture: CanvasTexture }[] = [];

        for (let index = 0; index < 3; index++) {
            const card = new Group();
            const x = (index - 1) * 1.35;
            const y = (index - 1) * 0.69;
            card.position.set(x, y, -index * 0.18);
            card.rotation.set(-0.06, (index - 1) * -0.16, (index - 1) * -0.12);

            const cover = mesh(new RoundedBoxGeometry(1.6, 2.02, 0.18, 3, 0.09), cardMaterials[index]);
            card.add(cover);
            const pageBlock = mesh(new RoundedBoxGeometry(1.5, 1.91, 0.10, 2, 0.035), pages);
            pageBlock.position.z = -0.12;
            card.add(pageBlock);
            const back = mesh(new RoundedBoxGeometry(1.6, 2.02, 0.055, 2, 0.025), cardMaterials[index]);
            back.position.z = -0.20;
            card.add(back);

            const surface = document.createElement('canvas');
            surface.width = 512;
            surface.height = 640;
            const texture = new CanvasTexture(surface);
            texture.colorSpace = SRGBColorSpace;
            texture.anisotropy = Math.min(gl.capabilities.getMaxAnisotropy(), 4);
            textures.push(texture);
            const print = mesh(new PlaneGeometry(1.44, 1.8), new MeshBasicMaterial({ map: texture, transparent: true, depthWrite: false }));
            print.position.z = 0.096;
            card.add(print);
            sculpture.add(card);
            cards.push({ group: card, y, surface, texture });
        }

        const orbit = mesh(new TorusGeometry(2.65, 0.013, 6, 100), orbitMaterial);
        orbit.rotation.set(0.28, 0.3, -0.35);
        orbit.scale.y = 0.66;
        orbit.position.z = -0.85;
        sculpture.add(orbit);

        const orb = mesh(new SphereGeometry(0.19, 24, 16), cobalt);
        orb.position.set(-2.15, 1.23, 0.15);
        sculpture.add(orb);
        const seed = mesh(new SphereGeometry(0.105, 16, 12), lime);
        seed.position.set(2.1, -0.8, 0.5);
        sculpture.add(seed);

        // A local texture supplies a soft shadow without a shadow-map render pass.
        const shadowCanvas = document.createElement('canvas');
        shadowCanvas.width = shadowCanvas.height = 128;
        const shadowContext = shadowCanvas.getContext('2d');
        if (shadowContext) {
            const gradient = shadowContext.createRadialGradient(64, 64, 4, 64, 64, 64);
            gradient.addColorStop(0, 'rgba(15, 27, 40, 0.24)');
            gradient.addColorStop(1, 'rgba(15, 27, 40, 0)');
            shadowContext.fillStyle = gradient;
            shadowContext.fillRect(0, 0, 128, 128);
            const shadowTexture = new CanvasTexture(shadowCanvas);
            textures.push(shadowTexture);
            const shadow = mesh(new PlaneGeometry(5.7, 3.1), new MeshBasicMaterial({ map: shadowTexture, transparent: true, depthWrite: false }));
            shadow.rotation.x = -Math.PI / 2;
            shadow.position.y = -2.05;
            scene.add(shadow);
        }

        const canRender = () => !disposed && !suspended && visible && hasSize && !document.hidden;

        function render() {
            if (!canRender()) return;
            const scroll = pageMotionOff || paused ? 0 : scrollProgress;
            sculpture.rotation.y = turn + tilt + scroll * 0.45;
            sculpture.rotation.z = -scroll * 0.09;
            cards.forEach(({ group, y }, index) => {
                group.position.x = (index - 1) * (1.35 + scroll * 0.2);
                group.position.y = y + Math.sin(elapsed * 0.7 + index * 1.2) * 0.07 + (index - 1) * scroll * 0.18;
            });
            orbit.rotation.z = -0.35 + scroll * 0.25;
            orb.position.y = 1.23 + Math.sin(elapsed * 0.8) * 0.12;
            seed.position.y = -0.8 + Math.cos(elapsed * 0.6) * 0.1;
            try {
                gl.render(scene, camera);
                if (gl.getContext().isContextLost()) return;
                stage.dataset.sceneState = 'ready';
                controls!.hidden = false;
            } catch {
                dispose();
            }
        }

        function tick(timestamp: number) {
            frame = 0;
            if (!canRender() || paused || pageMotionOff) return;
            if (!lastFrame || timestamp - lastFrame >= 1000 / 30) {
                elapsed += lastFrame ? Math.min((timestamp - lastFrame) / 1000, 0.1) : 0;
                lastFrame = timestamp;
                render();
            }
            if (!disposed) frame = window.requestAnimationFrame(tick);
        }

        function refresh() {
            stop();
            render();
            if (canRender() && !paused && !pageMotionOff) frame = window.requestAnimationFrame(tick);
        }

        function updateMotionControl() {
            motionButton!.hidden = pageMotionOff;
            motionButton!.textContent = paused ? 'Play' : 'Pause';
            motionButton!.setAttribute('aria-label', `${paused ? 'Play' : 'Pause'} illustration animation`);
            hint!.textContent = reducedMotion.matches ? 'Explore at your own pace.' : 'Drag to find a new perspective.';
        }

        function theme() {
            if (disposed) return;
            const style = getComputedStyle(document.documentElement);
            const token = (name: string, fallback: string) => style.getPropertyValue(name).trim() || fallback;
            paper.color.set(token('--studio-surface', '#ffffff'));
            cobalt.color.set(token('--studio-action', '#2448d8'));
            orbitMaterial.color.set(token('--studio-control-border', '#89958d'));
            const dark = document.documentElement.classList.contains('dark');
            pages.color.set(dark ? '#526774' : '#e6e7e1');
            hemisphere.groundColor = new Color(dark ? '#243641' : '#8f9bad');
            cards.forEach(({ surface, texture }, index) => {
                const ctx = surface.getContext('2d');
                if (!ctx) return;
                const ink = index === 0 ? token('--studio-ink', '#172128') : index === 1 ? '#ffffff' : '#172128';
                ctx.clearRect(0, 0, 512, 640);
                ctx.fillStyle = ink;
                ctx.font = '500 112px Studio, sans-serif';
                ctx.fillText(`0${index + 1}`, 32, 148);
                ctx.font = '500 19px Studio, sans-serif';
                ctx.fillText('YOUR NEXT CHAPTER', 36, 207);
                ctx.globalAlpha = 0.25;
                ctx.fillRect(36, 258, 430, 2);
                ctx.fillRect(36, 300, 242, 7);
                ctx.fillRect(36, 326, 168, 7);
                ctx.globalAlpha = 1;
                ctx.font = '500 43px Studio, sans-serif';
                ctx.fillText(labels[index], 32, 512);
                ctx.font = '400 52px Studio, sans-serif';
                ctx.fillText('↗', 405, 596);
                texture.needsUpdate = true;
            });
            refresh();
        }

        function resize() {
            if (disposed) return;
            const width = viewport!.clientWidth;
            const height = viewport!.clientHeight;
            hasSize = width > 0 && height > 0;
            if (!hasSize) { stop(); return; }
            gl.setPixelRatio(Math.min(window.devicePixelRatio || 1, width < 480 ? 1.25 : 1.5));
            gl.setSize(width, height, false);
            const aspect = width / height;
            // Fit the full sculpture even in a narrow split-screen hero.
            const halfHeight = Math.max(2.75, 3.3 / aspect);
            camera.left = -halfHeight * aspect;
            camera.right = halfHeight * aspect;
            camera.top = halfHeight;
            camera.bottom = -halfHeight;
            camera.updateProjectionMatrix();
            refresh();
        }

        const options = { signal: events.signal };
        stage.addEventListener('home:scroll', (event) => {
            scrollProgress = Math.max(0, Math.min(1, Number((event as CustomEvent<number>).detail) || 0));
            // The capped scene loop consumes progress; scroll events do not add GPU frames.
        }, options);
        stage.addEventListener('home:motion', (event) => {
            pageMotionOff = !(event as CustomEvent<boolean>).detail;
            tilt = 0;
            updateMotionControl();
            refresh();
        }, options);
        motionButton.addEventListener('click', () => {
            paused = !paused;
            updateMotionControl();
            refresh();
        }, options);
        stage.querySelectorAll<HTMLButtonElement>('[data-scene-turn]').forEach((button) => {
            button.addEventListener('click', () => {
                turn = Math.max(-0.7, Math.min(0.7, turn + Number(button.dataset.sceneTurn) * 0.18));
                tilt = 0;
                render();
            }, options);
        });

        canvas.addEventListener('pointerdown', (event) => {
            if (!event.isPrimary || event.button !== 0) return;
            pointerId = event.pointerId;
            pointerX = event.clientX;
            canvas.setPointerCapture(pointerId);
        }, options);
        canvas.addEventListener('pointermove', (event) => {
            if (pointerId === event.pointerId) {
                turn = Math.max(-0.7, Math.min(0.7, turn + (event.clientX - pointerX) * 0.006));
                pointerX = event.clientX;
                tilt = 0;
                render();
            } else if (event.pointerType === 'mouse' && !reducedMotion.matches && !paused && !pageMotionOff) {
                const bounds = canvas.getBoundingClientRect();
                tilt = ((event.clientX - bounds.left) / bounds.width - 0.5) * 0.18;
            }
        }, options);
        const releasePointer = () => { pointerId = null; };
        canvas.addEventListener('pointerup', releasePointer, options);
        canvas.addEventListener('pointercancel', releasePointer, options);
        canvas.addEventListener('lostpointercapture', releasePointer, options);
        canvas.addEventListener('pointerleave', () => { tilt = 0; }, options);
        // Permanent fallback is safer than repeatedly recreating an exhausted GPU context.
        canvas.addEventListener('webglcontextlost', () => dispose(), options);
        document.addEventListener('visibilitychange', refresh, options);
        reducedMotion.addEventListener('change', () => {
            paused = reducedMotion.matches;
            tilt = 0;
            updateMotionControl();
            refresh();
        }, options);
        window.addEventListener('pagehide', (event) => {
            if (event.persisted) { suspended = true; stop(); }
            else dispose();
        }, options);
        window.addEventListener('pageshow', () => { suspended = false; refresh(); }, options);

        if (typeof window.ResizeObserver === 'function') {
            resizeObserver = new ResizeObserver(resize);
            resizeObserver.observe(viewport);
        } else {
            window.addEventListener('resize', resize, options);
        }
        if ('IntersectionObserver' in window) {
            visibilityObserver = new IntersectionObserver(([entry]) => {
                visible = entry.isIntersecting;
                refresh();
            });
            visibilityObserver.observe(viewport);
        }
        themeObserver = new MutationObserver(theme);
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        void document.fonts?.ready.then(theme).catch(() => {});
        updateMotionControl();
        theme();
        resize();
    } catch {
        dispose();
    }
}
