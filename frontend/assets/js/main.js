/**
 * ScanPro AI - Core Frontend Interactions, Header Tool Search & Shared Utilities
 */
'use strict';

(function () {
    const ScanProUI = {
        getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') || '' : '';
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = String(str ?? '');
            return div.innerHTML;
        },

        showAlert(container, type, message) {
            if (!container) return;
            const validTypes = ['success', 'error', 'warning', 'info'];
            const safeType = validTypes.includes(type) ? type : 'info';
            const iconMap = {
                success: 'fa-circle-check',
                error: 'fa-circle-exclamation',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info'
            };

            container.innerHTML = `
                <div class="alert alert--${safeType}" role="alert">
                    <i class="fa-solid ${iconMap[safeType]}" aria-hidden="true" style="margin-top:2px;"></i>
                    <div class="alert-body">${this.escapeHtml(message)}</div>
                    <button type="button" class="alert-close" aria-label="Dismiss alert">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            `;
        },

        clearAlert(container) {
            if (container) {
                container.innerHTML = '';
            }
        },

        setButtonLoading(button, isLoading, loadingLabel) {
            if (!button) return;
            if (isLoading) {
                if (!button.dataset.originalHtml) {
                    button.dataset.originalHtml = button.innerHTML;
                }
                button.disabled = true;
                button.innerHTML = `<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> <span>${this.escapeHtml(loadingLabel || 'Please wait...')}</span>`;
            } else {
                button.disabled = false;
                if (button.dataset.originalHtml) {
                    button.innerHTML = button.dataset.originalHtml;
                }
            }
        }
    };

    window.ScanProUI = ScanProUI;

    const SEARCH_TOOLS_CATALOG = [
        { title: 'Merge PDF', category: 'PDF Tool', icon: 'fa-object-group', href: 'tools/merge-pdf.php', keys: 'merge combine join pdf' },
        { title: 'Split PDF', category: 'PDF Tool', icon: 'fa-scissors', href: 'tools/split-pdf.php', keys: 'split cut range zip pdf' },
        { title: 'Compress PDF', category: 'PDF Tool', icon: 'fa-minimize', href: 'tools/compress-pdf.php', keys: 'compress shrink optimize size pdf' },
        { title: 'PDF to JPG', category: 'PDF Tool', icon: 'fa-file-image', href: 'tools/pdf-to-jpg.php', keys: 'pdf to jpg image export zip' },
        { title: 'JPG to PDF', category: 'PDF Tool', icon: 'fa-images', href: 'tools/jpg-to-pdf.php', keys: 'jpg png images to pdf convert' },
        { title: 'Rotate PDF', category: 'PDF Tool', icon: 'fa-rotate-right', href: 'tools/rotate-pdf.php', keys: 'rotate 90 180 270 orientation pdf' },
        { title: 'Watermark PDF', category: 'PDF Tool', icon: 'fa-stamp', href: 'tools/watermark-pdf.php', keys: 'watermark text stamp opacity pdf' },
        { title: 'Add Page Numbers', category: 'PDF Tool', icon: 'fa-list-ol', href: 'tools/add-page-numbers.php', keys: 'page numbers numbering footer header pdf' },
        { title: 'Extract PDF Pages', category: 'PDF Tool', icon: 'fa-file-export', href: 'tools/extract-pages.php', keys: 'extract select pages pdf' },
        { title: 'Reorder PDF Pages', category: 'PDF Tool', icon: 'fa-arrow-down-up-across-line', href: 'tools/reorder-pages.php', keys: 'reorder rearrange drag sort pages pdf' },
        { title: 'All PDF Tools', category: 'Hub', icon: 'fa-file-pdf', href: 'pdf-tools.php', keys: 'all tools pdf hub studio' },
        { title: 'Web Document Workspace', category: 'Editor', icon: 'fa-pen-ruler', href: 'workspace.php', keys: 'workspace crop filter ocr scan editor' },
        { title: 'AI Scanner', category: 'Overview', icon: 'fa-wand-magic-sparkles', href: 'ai-scanner.php', keys: 'ai scanner camera ocr filters android' },
        { title: 'All Features', category: 'Overview', icon: 'fa-layer-group', href: 'features.php', keys: 'features office word excel vault security' },
        { title: 'Pricing Plans', category: 'Plans', icon: 'fa-tags', href: 'pricing.php', keys: 'pricing free pro ultra plans' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Sticky Header Scroll State
        const header = document.getElementById('siteHeader');
        if (header) {
            const syncHeader = () => {
                header.classList.toggle('scrolled', window.scrollY > 12);
            };
            syncHeader();
            window.addEventListener('scroll', syncHeader, { passive: true });
        }

        // 2. Header Search Bar & Live Tool Finder
        const desktopSearchBtn = document.getElementById('desktopSearchToggle');
        const mobileSearchBtn = document.getElementById('mobileSearchToggle');
        const searchPanel = document.getElementById('headerSearchPanel');
        const searchInput = document.getElementById('headerSearchInput');
        const searchClose = document.getElementById('headerSearchClose');
        const searchResults = document.getElementById('headerSearchResults');

        if (searchPanel && searchInput && searchResults) {
            const routePrefix = searchPanel.dataset.routePrefix || '';

            const renderSearchResults = (query) => {
                const q = String(query || '').trim().toLowerCase();
                const matches = q === ''
                    ? SEARCH_TOOLS_CATALOG.slice(0, 8)
                    : SEARCH_TOOLS_CATALOG.filter((item) =>
                        item.title.toLowerCase().includes(q) ||
                        item.category.toLowerCase().includes(q) ||
                        item.keys.toLowerCase().includes(q)
                    );

                if (matches.length === 0) {
                    searchResults.innerHTML = `<p class="text-muted" style="font-size:0.88rem;padding:8px 0;">No matching tool found for "${ScanProUI.escapeHtml(query)}".</p>`;
                    return;
                }

                searchResults.innerHTML = matches.map((item) => `
                    <a href="${ScanProUI.escapeHtml(routePrefix + item.href)}" class="search-result-item" role="option">
                        <i class="fa-solid ${ScanProUI.escapeHtml(item.icon)}" aria-hidden="true"></i>
                        <div>
                            <span class="search-result-title">${ScanProUI.escapeHtml(item.title)}</span>
                            <span class="search-result-cat">${ScanProUI.escapeHtml(item.category)}</span>
                        </div>
                    </a>
                `).join('');
            };

            const setSearchOpen = (open) => {
                searchPanel.classList.toggle('open', open);
                searchPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
                if (desktopSearchBtn) desktopSearchBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (mobileSearchBtn) mobileSearchBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    renderSearchResults(searchInput.value);
                    setTimeout(() => searchInput.focus(), 40);
                }
            };

            if (desktopSearchBtn) {
                desktopSearchBtn.addEventListener('click', () => {
                    setSearchOpen(!searchPanel.classList.contains('open'));
                });
            }
            if (mobileSearchBtn) {
                mobileSearchBtn.addEventListener('click', () => {
                    setSearchOpen(!searchPanel.classList.contains('open'));
                });
            }
            if (searchClose) {
                searchClose.addEventListener('click', () => setSearchOpen(false));
            }
            searchInput.addEventListener('input', () => renderSearchResults(searchInput.value));
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    setSearchOpen(false);
                } else if (e.key === 'Enter') {
                    const firstLink = searchResults.querySelector('a.search-result-item');
                    if (firstLink) {
                        e.preventDefault();
                        firstLink.click();
                    }
                }
            });

            document.addEventListener('click', (e) => {
                if (
                    searchPanel.classList.contains('open') &&
                    !e.target.closest('#headerSearchPanel, #desktopSearchToggle, #mobileSearchToggle')
                ) {
                    setSearchOpen(false);
                }
            });
        }

        // 3. PDF Tools Hub Page Live Filter (#pdfToolsFilterInput)
        const hubFilterInput = document.getElementById('pdfToolsFilterInput');
        const hubGrid = document.getElementById('pdfToolsHubGrid');
        const hubEmptyMsg = document.getElementById('pdfToolsEmptyFilter');
        if (hubFilterInput && hubGrid) {
            hubFilterInput.addEventListener('input', () => {
                const q = hubFilterInput.value.trim().toLowerCase();
                let visibleCount = 0;
                hubGrid.querySelectorAll('.pdf-hub-card[data-tool-search]').forEach((card) => {
                    const hay = card.getAttribute('data-tool-search') || '';
                    const match = q === '' || hay.includes(q);
                    card.style.display = match ? 'flex' : 'none';
                    if (match) visibleCount++;
                });
                if (hubEmptyMsg) {
                    hubEmptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            });
        }

        // 4. Mobile Sidebar Drawer (Only visible on click)
        const openBtn = document.getElementById('mobileMenuOpen');
        const closeBtn = document.getElementById('mobileMenuClose');
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('mobileOverlay');

        const setDrawerState = (isOpen) => {
            if (!drawer || !overlay || !openBtn) return;
            drawer.classList.toggle('open', isOpen);
            overlay.classList.toggle('open', isOpen);
            openBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        };

        if (openBtn && drawer && overlay) {
            setDrawerState(false);

            openBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (searchPanel && searchPanel.classList.contains('open')) {
                    searchPanel.classList.remove('open');
                }
                const isCurrentlyOpen = drawer.classList.contains('open');
                setDrawerState(!isCurrentlyOpen);
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', () => setDrawerState(false));
            }

            overlay.addEventListener('click', () => setDrawerState(false));

            drawer.querySelectorAll('a.nav-link, .mobile-drawer-actions a').forEach((link) => {
                link.addEventListener('click', () => setDrawerState(false));
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && drawer.classList.contains('open')) {
                    setDrawerState(false);
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024 && drawer.classList.contains('open')) {
                    setDrawerState(false);
                }
            });
        }

        // 5. FAQ Accordion
        document.querySelectorAll('.faq-question').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                if (!item) return;
                const willOpen = !item.classList.contains('open');

                const parentList = item.closest('.faq-list');
                if (parentList) {
                    parentList.querySelectorAll('.faq-item.open').forEach((openItem) => {
                        openItem.classList.remove('open');
                        const qBtn = openItem.querySelector('.faq-question');
                        if (qBtn) qBtn.setAttribute('aria-expanded', 'false');
                    });
                }

                item.classList.toggle('open', willOpen);
                btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        });

        // 6. Password Visibility Toggle
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('.password-toggle');
            if (!toggleBtn) return;

            const wrap = toggleBtn.closest('.input-wrap');
            const input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
        });

        // 7. Alert Dismissal
        document.addEventListener('click', (e) => {
            const closeAlertBtn = e.target.closest('.alert-close');
            if (!closeAlertBtn) return;
            const alertBox = closeAlertBtn.closest('.alert');
            if (alertBox) {
                alertBox.remove();
            }
        });
    });
})();