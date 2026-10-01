(() => {
    const modes = ['light', 'dark', 'system'];
    const system = matchMedia('(prefers-color-scheme: dark)');
    let appearance = 'system';
    try {
        const stored = localStorage.getItem('appearance') || localStorage.getItem('theme');
        if (modes.includes(stored)) appearance = stored;
    } catch {
        const cookie = document.cookie.split('; ').find(item => item.startsWith('appearance='))?.split('=')[1];
        if (modes.includes(cookie)) appearance = cookie;
    }
    const apply = () => {
        const dark = appearance === 'dark' || (appearance === 'system' && system.matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-theme-picker] [data-theme-option]').forEach(button => {
            button.setAttribute('aria-checked', String(button.dataset.themeOption === appearance));
        });
        document.querySelectorAll('[data-theme-trigger]').forEach(trigger => {
            trigger.setAttribute('aria-label', `Colour theme: ${appearance}. Choose theme`);
        });
    };
    const remember = () => {
        try {
            localStorage.setItem('appearance', appearance);
            localStorage.setItem('theme', appearance);
        } catch { /* The theme remains usable when browser storage is unavailable. */ }
        document.cookie = `appearance=${appearance};path=/;max-age=31536000;SameSite=Lax`;
    };
    remember();
    apply();
    system.addEventListener('change', apply);
    window.addEventListener('theme-changed', event => {
        if (!modes.includes(event.detail)) return;
        appearance = event.detail;
        remember();
        apply();
    });
    window.addEventListener('storage', event => {
        if (!['appearance', 'theme'].includes(event.key) || !modes.includes(event.newValue)) return;
        appearance = event.newValue;
        apply();
    });
    const initializePickers = () => {
        document.querySelectorAll('[data-theme-picker]').forEach(picker => {
            const trigger = picker.querySelector('[data-theme-trigger]');
            const menu = picker.querySelector('[data-theme-menu]');
            const options = Array.from(picker.querySelectorAll('[data-theme-option]'));
            if (!trigger || !menu) return;
            const close = (restoreFocus = false) => {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                if (restoreFocus) trigger.focus();
            };
            const open = (last = false) => {
                menu.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
                const selected = options.find(option => option.dataset.themeOption === appearance);
                (last ? options.at(-1) : selected || options[0])?.focus();
            };
            picker.hidden = false;
            trigger.addEventListener('click', () => menu.hidden ? open() : close(true));
            trigger.addEventListener('keydown', event => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    open(event.key === 'ArrowUp');
                }
            });
            picker.addEventListener('click', event => {
                const button = event.target.closest('[data-theme-option]');
                if (!button || !picker.contains(button)) return;
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: button.dataset.themeOption }));
                close(true);
            });
            picker.addEventListener('keydown', event => {
                if (menu.hidden) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close(true);
                } else if (event.key === 'Tab') {
                    close(true);
                } else if (menu.contains(event.target)) {
                    const index = options.indexOf(document.activeElement);
                    const targets = {
                        ArrowDown: (index + 1) % options.length,
                        ArrowUp: (index - 1 + options.length) % options.length,
                        Home: 0,
                        End: options.length - 1,
                    };
                    if (Object.hasOwn(targets, event.key)) {
                        event.preventDefault();
                        options[targets[event.key]]?.focus();
                    }
                }
            });
            document.addEventListener('pointerdown', event => {
                if (!picker.contains(event.target)) close();
            });
            picker.addEventListener('focusout', event => {
                if (!picker.contains(event.relatedTarget)) close();
            });
        });
        apply();
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePickers, { once: true });
    } else {
        initializePickers();
    }
})();
