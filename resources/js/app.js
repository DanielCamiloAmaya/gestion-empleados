document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const toggles = document.querySelectorAll('[data-menu-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const mobileViewport = window.matchMedia('(max-width: 820px)');

    const syncNavigationState = () => {
        if (! sidebar) return;

        const isOpen = body.classList.contains('menu-open');
        const isHidden = mobileViewport.matches && ! isOpen;
        sidebar.toggleAttribute('inert', isHidden);
        sidebar.setAttribute('aria-hidden', String(isHidden));
        toggles.forEach((item) => item.setAttribute('aria-expanded', String(isOpen)));
    };

    toggles.forEach((toggle) => toggle.addEventListener('click', () => {
        body.classList.toggle('menu-open');
        syncNavigationState();
    }));

    mobileViewport.addEventListener('change', () => {
        body.classList.remove('menu-open');
        syncNavigationState();
    });

    syncNavigationState();

    document.querySelector('.primary-nav a.active')?.scrollIntoView({ block: 'nearest' });

    requestAnimationFrame(() => body.classList.add('is-ready'));

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(button.dataset.copy);
            const label = button.querySelector('span');
            if (label) label.textContent = 'Copiado';
        });
    });
});
