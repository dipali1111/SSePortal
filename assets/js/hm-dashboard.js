if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('.sidebar');
    const progressBars = document.querySelectorAll('[data-progress]');
    const previewInputs = document.querySelectorAll('[data-photo-preview]');
    const sidebarNav = document.querySelector('.sidebar-nav');

    // Scroll top on initial load if no hash
    if (!window.location.hash) {
        window.scrollTo(0, 0);
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Handle hash links in sidebar smoothly without abrupt jump
    if (sidebarNav) {
        sidebarNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href') || '';
                if (href.includes('#')) {
                    const hash = href.split('#')[1];
                    const targetEl = document.getElementById(hash);
                    if (targetEl && window.location.pathname.endsWith('dashboard.php')) {
                        e.preventDefault();
                        sidebarNav.querySelectorAll('a').forEach((a) => a.classList.remove('active'));
                        link.classList.add('active');
                        targetEl.scrollIntoView({ behavior: 'smooth' });
                        history.pushState(null, '', '#' + hash);
                    }
                }
            });
        });
    }

    progressBars.forEach((bar) => {
        const value = Number(bar.dataset.progress || 0);
        requestAnimationFrame(() => {
            bar.style.width = value + '%';
        });
    });

    previewInputs.forEach((input) => {
        input.addEventListener('change', (event) => {
            const previewBox = input.closest('.form-group')?.querySelector('.preview-box');
            if (!previewBox) return;

            previewBox.innerHTML = '';
            const files = Array.from(event.target.files || []);

            files.forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    previewBox.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    });

    const successMessage = document.querySelector('.notice.success');
    const errorMessage = document.querySelector('.notice.error');
    [successMessage, errorMessage].forEach((el) => {
        if (el) {
            setTimeout(() => {
                el.style.display = 'none';
            }, 4000);
        }
    });
});
