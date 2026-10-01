export function mountHomeScroll(root: HTMLElement): void {
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const pointerQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
    const toggle = root.querySelector<HTMLButtonElement>('[data-page-motion]');
    const toggleLabel = root.querySelector<HTMLElement>('[data-motion-label]');
    const progressBar = root.querySelector<HTMLElement>('[data-page-progress]');
    const hero = root.querySelector<HTMLElement>('[data-home-hero]');
    const heroArt = root.querySelector<HTMLElement>('[data-hero-art]');
    const scene = root.querySelector<HTMLElement>('[data-learning-scene]');
    const manifesto = root.querySelector<HTMLElement>('[data-manifesto]');
    const journey = root.querySelector<HTMLElement>('[data-journey]');
    const journeyArt = root.querySelector<HTMLElement>('[data-journey-art]');
    const chapters = [...root.querySelectorAll<HTMLElement>('[data-chapter-step]')];
    const chapterNumber = root.querySelector<HTMLElement>('[data-chapter-number]');
    const chapterLabel = root.querySelector<HTMLElement>('[data-chapter-label]');
    const chapterFraction = journeyArt?.querySelector<HTMLElement>('.home-journey-caption > span:last-child');
    const finale = root.querySelector<HTMLElement>('[data-finale]');
    const ribbon = root.querySelector<HTMLElement>('[data-word-ribbon]');
    const journeyPath = root.querySelector<SVGPathElement>('[data-journey-path]');
    const journeyMarker = root.querySelector<SVGCircleElement>('[data-journey-marker]');
    const chapterLinks = [...root.querySelectorAll<HTMLAnchorElement>('[data-chapter-link]')];
    const pathLength = journeyPath?.getTotalLength() ?? 0;
    const events = new AbortController();
    const animations = new Map<Element, Animation>();
    const revealed = new WeakSet<Element>();
    let observer: IntersectionObserver | undefined;
    let resizeObserver: ResizeObserver | undefined;
    let frame = 0;
    let suspended = false;
    let disposed = false;
    let enabled = false;
    let userPaused = false;
    let activeChapter = -1;
    let previousHeroProgress = -1;
    let pointerTarget: HTMLElement | null = null;
    let pointerX = 0;
    let pointerY = 0;
    try { userPaused = sessionStorage.getItem('studio-home-motion') === 'off'; } catch { /* Storage is optional. */ }

    const clamp = (value: number) => Math.max(0, Math.min(1, value));

    function selectChapter(index: number) {
        chapterLinks.forEach((link, linkIndex) => {
            if (linkIndex === index) link.setAttribute('aria-current', 'step');
            else link.removeAttribute('aria-current');
        });
        if (index === activeChapter || !journeyArt) return;
        activeChapter = index;
        const number = String(index + 1).padStart(2, '0');
        journeyArt.dataset.chapter = String(index);
        if (chapterNumber) chapterNumber.textContent = number;
        if (chapterLabel) chapterLabel.textContent = chapters[index]?.dataset.chapterTitle ?? '';
        if (chapterFraction) chapterFraction.textContent = `${number} / 03`;
    }

    function clearPointer() {
        if (!pointerTarget) return;
        delete pointerTarget.dataset.pointerActive;
        ['--pointer-x', '--pointer-y', '--card-tilt-x', '--card-tilt-y', '--card-shine'].forEach((name) => pointerTarget!.style.removeProperty(name));
        pointerTarget = null;
    }

    function update() {
        frame = 0;
        if (disposed || suspended || document.hidden) return;
        const viewportHeight = window.innerHeight;
        const scrollable = document.documentElement.scrollHeight - viewportHeight;
        const pageProgress = scrollable > 0 ? clamp(window.scrollY / scrollable) : 0;

        // Read layout together, before writing transforms. Never intercept wheel/touch scrolling.
        const heroBounds = enabled ? hero?.getBoundingClientRect() : undefined;
        const manifestoBounds = enabled ? manifesto?.getBoundingClientRect() : undefined;
        const journeyBounds = enabled ? journey?.getBoundingClientRect() : undefined;
        const finaleBounds = enabled ? finale?.getBoundingClientRect() : undefined;
        const chapterBounds = enabled ? chapters.map((chapter) => chapter.getBoundingClientRect()) : [];
        const ribbonBounds = enabled ? ribbon?.getBoundingClientRect() : undefined;
        const pointerBounds = enabled ? pointerTarget?.getBoundingClientRect() : undefined;
        progressBar?.style.setProperty('transform', `scaleX(${pageProgress})`);
        if (!enabled) return;

        if (pointerBounds && pointerTarget) {
            const x = clamp((pointerX - pointerBounds.left) / Math.max(1, pointerBounds.width));
            const y = clamp((pointerY - pointerBounds.top) / Math.max(1, pointerBounds.height));
            pointerTarget.dataset.pointerActive = 'true';
            pointerTarget.style.setProperty('--pointer-x', `${x * 100}%`);
            pointerTarget.style.setProperty('--pointer-y', `${y * 100}%`);
            pointerTarget.style.setProperty('--card-tilt-x', `${(0.5 - y) * 5}deg`);
            pointerTarget.style.setProperty('--card-tilt-y', `${(x - 0.5) * 5}deg`);
            pointerTarget.style.setProperty('--card-shine', '1');
        }
        if (ribbonBounds) {
            const progress = clamp((viewportHeight - ribbonBounds.top) / (viewportHeight + ribbonBounds.height));
            ribbon?.style.setProperty('--ribbon-shift', `${-20 - progress * 180}px`);
        }

        if (heroBounds) {
            const progress = clamp(-heroBounds.top / Math.max(1, heroBounds.height));
            heroArt?.style.setProperty('--hero-offset', `${progress * 32}px`);
            heroArt?.style.setProperty('--hero-turn', `${progress * 3}deg`);
            if (scene && progress !== previousHeroProgress) {
                previousHeroProgress = progress;
                scene.dataset.scrollProgress = String(progress);
                scene.dispatchEvent(new CustomEvent('home:scroll', { detail: progress }));
            }
        }
        if (manifestoBounds) {
            const progress = clamp((viewportHeight * 0.85 - manifestoBounds.top) / (manifestoBounds.height + viewportHeight * 0.25));
            manifesto?.style.setProperty('--manifesto-shift', `${(1 - progress) * 48}px`);
            manifesto?.style.setProperty('--manifesto-spin', `${progress * 100}deg`);
            manifesto?.style.setProperty('--manifesto-progress', String(progress));
        }
        if (journeyBounds) {
            const progress = clamp((viewportHeight * 0.55 - journeyBounds.top) / Math.max(1, journeyBounds.height - viewportHeight * 0.35));
            journey?.style.setProperty('--journey-progress', String(progress));
            if (journeyPath && journeyMarker && pathLength > 0) {
                const point = journeyPath.getPointAtLength(progress * pathLength);
                journeyMarker.setAttribute('cx', String(point.x));
                journeyMarker.setAttribute('cy', String(point.y));
            }
            let chapter = 0;
            chapterBounds.forEach((bounds, index) => {
                if (bounds.top < viewportHeight * 0.6) chapter = index;
            });
            selectChapter(chapter);
        }
        if (finaleBounds) {
            const progress = clamp((viewportHeight - finaleBounds.top) / (viewportHeight + finaleBounds.height));
            finale?.style.setProperty('--finale-turn', `${-20 + progress * 70}deg`);
        }
    }

    function schedule() {
        if (!frame && !disposed && !suspended && !document.hidden) frame = window.requestAnimationFrame(update);
    }

    function cancelAnimations() {
        animations.forEach((animation) => animation.cancel());
        animations.clear();
    }

    function setMotion() {
        enabled = !motionQuery.matches && !userPaused;
        root.dataset.motion = enabled ? 'on' : 'off';
        if (toggle) {
            toggle.hidden = motionQuery.matches;
            toggle.setAttribute('aria-pressed', String(enabled));
            toggle.setAttribute('aria-label', enabled ? 'Turn page animations off' : 'Turn page animations on');
        }
        if (toggleLabel) toggleLabel.textContent = enabled ? 'Motion on' : 'Motion off';
        if (!enabled) {
            cancelAnimations();
            clearPointer();
            ribbon?.style.setProperty('--ribbon-shift', '0px');
            heroArt?.style.removeProperty('--hero-offset');
            heroArt?.style.removeProperty('--hero-turn');
            manifesto?.style.removeProperty('--manifesto-shift');
            manifesto?.style.removeProperty('--manifesto-spin');
            manifesto?.style.removeProperty('--manifesto-progress');
            journey?.style.setProperty('--journey-progress', '1');
            finale?.style.removeProperty('--finale-turn');
            selectChapter(0);
            chapterLinks.forEach((link) => link.removeAttribute('aria-current'));
        }
        previousHeroProgress = -1;
        scene?.dispatchEvent(new CustomEvent('home:motion', { detail: enabled }));
        schedule();
    }

    if (typeof window.IntersectionObserver === 'function') {
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const element = entry.target as HTMLElement;
                observer?.unobserve(element);
                if (revealed.has(element)) return;
                revealed.add(element);
                if (!enabled || document.hidden || typeof element.animate !== 'function' || element.contains(document.activeElement)) return;
                const delay = Math.min(4, Number(element.dataset.revealOrder) || 0) * 55;
                // Visibility belongs to the HTML. If JS fails, nothing remains hidden.
                const animation = element.animate([
                    { opacity: 0, transform: 'translate3d(0, 24px, 0)' },
                    { opacity: 1, transform: 'translate3d(0, 0, 0)' },
                ], { duration: 420, delay, easing: 'cubic-bezier(.2,.7,.2,1)', fill: 'backwards' });
                animations.set(element, animation);
                animation.onfinish = animation.oncancel = () => animations.delete(element);
            });
        }, { threshold: 0.08 });
        root.querySelectorAll('[data-reveal]').forEach((element) => observer!.observe(element));
    }

    const options = { signal: events.signal };
    root.addEventListener('pointermove', (event) => {
        if (!enabled || !pointerQuery.matches || event.pointerType !== 'mouse') return;
        const target = event.target instanceof Element
            ? event.target.closest<HTMLElement>('.home-course-item .course-art, [data-spotlight]')
            : null;
        if (target !== pointerTarget) { clearPointer(); pointerTarget = target; }
        pointerX = event.clientX;
        pointerY = event.clientY;
        if (pointerTarget) schedule();
    }, { ...options, passive: true });
    root.addEventListener('pointerleave', clearPointer, options);
    pointerQuery.addEventListener('change', clearPointer, options);
    window.addEventListener('blur', clearPointer, options);
    toggle?.addEventListener('click', () => {
        userPaused = !userPaused;
        try { sessionStorage.setItem('studio-home-motion', userPaused ? 'off' : 'on'); } catch { /* Storage is optional. */ }
        setMotion();
    }, options);
    root.addEventListener('focusin', (event) => {
        clearPointer();
        if (!(event.target instanceof Element)) return;
        let element = event.target.closest('[data-reveal]');
        while (element) {
            animations.get(element)?.cancel();
            revealed.add(element);
            element = element.parentElement?.closest('[data-reveal]') ?? null;
        }
    }, options);
    motionQuery.addEventListener('change', setMotion, options);
    window.addEventListener('scroll', schedule, { ...options, passive: true });
    window.addEventListener('resize', schedule, options);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearPointer();
            window.cancelAnimationFrame(frame);
            frame = 0;
            cancelAnimations();
        } else schedule();
    }, options);
    window.addEventListener('pagehide', (event) => {
        clearPointer();
        suspended = true;
        window.cancelAnimationFrame(frame);
        frame = 0;
        cancelAnimations();
        if (!event.persisted) {
            disposed = true;
            events.abort();
            observer?.disconnect();
            resizeObserver?.disconnect();
        }
    }, options);
    window.addEventListener('pageshow', () => { suspended = false; schedule(); }, options);
    if (typeof window.ResizeObserver === 'function') {
        resizeObserver = new ResizeObserver(schedule);
        resizeObserver.observe(root);
    }
    setMotion();
}
