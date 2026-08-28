// ASENTRA SPK — Global UI behavior
(function () {
    'use strict';

    // Toast system
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    toastContainer.setAttribute('aria-live', 'polite');
    toastContainer.setAttribute('role', 'status');
    document.body.appendChild(toastContainer);

    window.showToast = function (message, type = 'success', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;

        const iconMap = {
            success: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#43D17A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
            error: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F06464" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F0B84B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        };

        toast.innerHTML = (iconMap[type] || iconMap.warning) + '<span>' + escapeHtml(message) + '</span>';
        toastContainer.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('is-leaving');
            setTimeout(function () {
                toast.remove();
            }, 200);
        }, duration);
    };

    // Modal system
    window.showModal = function (options) {
        options = options || {};
        const title = options.title || 'Konfirmasi';
        const text = options.text || '';
        const confirmText = options.confirmText || 'Ya';
        const cancelText = options.cancelText || 'Batal';
        const danger = options.danger || false;

        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.innerHTML =
            '<div class="modal">' +
            '<h3 class="modal-title">' + escapeHtml(title) + '</h3>' +
            '<p class="modal-text">' + escapeHtml(text) + '</p>' +
            '<div class="modal-actions">' +
            '<button type="button" class="btn btn-secondary modal-cancel">' + escapeHtml(cancelText) + '</button>' +
            '<button type="button" class="btn ' + (danger ? 'btn-danger' : 'btn-primary') + ' modal-confirm">' + escapeHtml(confirmText) + '</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(backdrop);

        // Trigger reflow for transition
        void backdrop.offsetWidth;
        backdrop.classList.add('show');

        return new Promise(function (resolve) {
            backdrop.querySelector('.modal-confirm').addEventListener('click', function () {
                closeBackdrop();
                resolve(true);
            });
            backdrop.querySelector('.modal-cancel').addEventListener('click', function () {
                closeBackdrop();
                resolve(false);
            });
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) {
                    closeBackdrop();
                    resolve(false);
                }
            });

            function closeBackdrop() {
                backdrop.classList.remove('show');
                setTimeout(function () {
                    backdrop.remove();
                }, 180);
            }
        });
    };

    // Delete confirmation on elements with data-confirm
    document.addEventListener('click', function (e) {
        const el = e.target.closest('[data-confirm]');
        if (!el) return;

        e.preventDefault();
        const message = el.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
        const form = el.closest('form');

        showModal({
            title: 'Konfirmasi',
            text: message,
            confirmText: 'Lanjutkan',
            cancelText: 'Batal',
            danger: true,
        }).then(function (confirmed) {
            if (confirmed && form) {
                form.submit();
            }
        });
    });

    // Mobile sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarBackdrop = document.querySelector('[data-sidebar-close]');

    function setSidebar(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.toggle('show', open);
        }
    }

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-sidebar-toggle]');
        if (toggle) {
            const willOpen = sidebar ? !sidebar.classList.contains('open') : false;
            setSidebar(willOpen);
            return;
        }
        if (e.target.closest('[data-sidebar-close]')) {
            setSidebar(false);
        }
    });

    // Close drawer when a nav link is chosen (mobile)
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a')) {
                setSidebar(false);
            }
        });
    }

    // Auto-show toast from session data embedded by PHP
    document.addEventListener('DOMContentLoaded', function () {
        const toastData = window._toast;
        if (toastData && toastData.message) {
            showToast(toastData.message, toastData.type || 'success');
            window._toast = null;
        }
    });

    // Motion enhancement flag (progressive enhancement: nothing below runs
    // without JS, and all of it is skipped under reduced-motion).
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.documentElement.classList.add('js');

    function initMotion() {
        if (reducedMotion) return;

        // KPI count-up (MOTION.md §7) — server value is the source of truth,
        // JS only animates the display. Uses data-count (final integer).
        function animateCount(el) {
            const target = parseFloat(el.getAttribute('data-count'));
            if (isNaN(target)) return;
            const duration = 700;
            const start = performance.now();
            const decimals = (el.getAttribute('data-count') || '').split('.')[1];
            const fixed = decimals ? decimals.length : 0;
            function step(now) {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 3);
                const val = target * eased;
                el.textContent = fixed ? val.toFixed(fixed) : Math.round(val).toString();
                if (p < 1) requestAnimationFrame(step);
                else el.textContent = fixed ? target.toFixed(fixed) : String(target);
            }
            requestAnimationFrame(step);
        }

        // Data-adaptive stagger (MOTION.md §8, §21): fewer rows = more stagger.
        function revealTargets() {
            const groups = document.querySelectorAll('[data-reveal-group]');
            groups.forEach(function (group) {
                const items = group.querySelectorAll('[data-reveal]');
                if (!items.length) return;
                const n = items.length;
                const stagger = n <= 10 ? 60 : (n <= 50 ? 30 : (n <= 100 ? 15 : 0));
                items.forEach(function (item, i) {
                    if (stagger === 0) {
                        item.classList.add('is-visible');
                    } else {
                        item.style.transitionDelay = (i * stagger) + 'ms';
                    }
                });
            });
        }

        const revealEls = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });
            revealEls.forEach(function (el) { io.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        }

        revealTargets();

        const counters = document.querySelectorAll('[data-count]');
        if ('IntersectionObserver' in window) {
            const io2 = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        io2.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { io2.observe(el); });
        } else {
            counters.forEach(animateCount);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMotion);
    } else {
        initMotion();
    }

    // Button loading state helper (MOTION.md §12) — no fake progress, only
    // indeterminate spinner while the form actually submits.
    window.setButtonLoading = function (form, loading) {
        if (!form) return;
        const btn = form.querySelector('[type="submit"]');
        if (!btn) return;
        if (loading) {
            btn.setAttribute('data-orig-html', btn.innerHTML);
            btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span> Memproses...';
            btn.classList.add('is-loading');
        } else {
            const orig = btn.getAttribute('data-orig-html');
            if (orig) btn.innerHTML = orig;
            btn.classList.remove('is-loading');
        }
    };

    // Wire data-loading forms: submit → indeterminate spinner (real submit,
    // no artificial delay, no fake percentage).
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('[data-loading]');
        if (form) {
            setButtonLoading(form, true);
        }
    }, true);

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
