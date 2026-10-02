(() => {
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const close = document.querySelector('.menu-close');
    const backdrop = document.querySelector('.nav-backdrop');
    const mobile = window.matchMedia('(max-width: 900px)');
    const account = document.querySelector('.account-dropdown');
    const accountToggle = account.querySelector('summary');
    document.addEventListener('click', (event) => {
        if (!account.contains(event.target)) account.open = false;
    });
    account.addEventListener('focusout', (event) => {
        if (event.relatedTarget && !account.contains(event.relatedTarget)) account.open = false;
    });
    account.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && account.open) {
            account.open = false;
            accountToggle.focus();
        }
    });
    const setOpen = (open, restoreFocus = true) => {
        document.body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        backdrop.hidden = !open;
        if (open) { account.open = false; close.focus(); }
        else if (restoreFocus) toggle.focus();
    };
    document.body.classList.add('nav-ready');
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    close.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    mobile.addEventListener('change', () => setOpen(false, false));
    document.addEventListener('keydown', (event) => {
        if (!document.body.classList.contains('nav-open')) return;
        if (event.key === 'Escape') setOpen(false);
        if (event.key === 'Tab') {
            const items = sidebar.querySelectorAll('a, button');
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
})();
