import '../css/home-scene.css';
import '../css/home-scroll.css';
import { mountHomeScroll } from './home-scroll';

const homepage = document.querySelector<HTMLElement>('[data-home-experience]');
if (homepage) mountHomeScroll(homepage);

const stage = document.querySelector<HTMLElement>('[data-learning-scene]');
const connection = (navigator as Navigator & {
    connection?: { saveData?: boolean };
}).connection;

// Keep the server-rendered illustration on data-saving connections.
if (stage && !connection?.saveData) {
    let started = false;
    const start = () => {
        if (started || !stage.isConnected) return;
        started = true;
        const load = () => {
            void import('./scenes/learning-studio')
                .then(({ mountLearningStudio }) => {
                    if (stage.isConnected) mountLearningStudio(stage);
                })
                .catch(() => {
                    // A blocked download must never interrupt the course page.
                    stage.dataset.sceneState = 'fallback';
                });
        };
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(load, { timeout: 1500 });
        } else {
            window.setTimeout(load, 100);
        }
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                start();
            }
        }, { rootMargin: '160px' });
        observer.observe(stage);
        window.addEventListener('pagehide', (event) => {
            if (!event.persisted) observer.disconnect();
        });
    } else {
        start();
    }
}
