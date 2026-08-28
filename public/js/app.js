// ASENTRA SPK — Global UI behavior
(function () {
    'use strict';

    // Toast system
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
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
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(function () {
                toast.remove();
            }, 180);
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

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
