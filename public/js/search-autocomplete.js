/**
 * search-autocomplete.js
 * Implements FR-030 & FR-031: Unified Search & Autocomplete
 * Debounce: 300ms, Max items: 8, Direct navigation on selection.
 */

(function() {
    function initAutocomplete(inputEl) {
        if (!inputEl) return;

        // Container wrapper
        const wrapper = inputEl.parentElement;
        if (!wrapper) return;
        wrapper.style.position = 'relative';

        // Create dropdown element
        const dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.style.cssText = `
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius, 8px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            max-height: 420px;
            overflow-y: auto;
            text-align: left;
        `;
        wrapper.appendChild(dropdown);

        let debounceTimer = null;
        let selectedIndex = -1;
        let currentItems = [];

        // Input event with 300ms debounce (FR-031)
        inputEl.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = inputEl.value.trim();

            if (query.length < 2) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        // Keydown navigation (Arrow Up, Arrow Down, Enter, Escape)
        inputEl.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    goToFullSearch(inputEl.value.trim());
                }
                return;
            }

            const items = dropdown.querySelectorAll('.autocomplete-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateHighlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateHighlight(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < currentItems.length) {
                    window.location.href = currentItems[selectedIndex].url;
                } else {
                    goToFullSearch(inputEl.value.trim());
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                hideDropdown();
            }
        });

        function fetchSuggestions(query) {
            const apiBase = (typeof baseUrl !== 'undefined' ? baseUrl : '/webpinjamft/public');
            const url = apiBase + '/api/search?q=' + encodeURIComponent(query);

            fetch(url)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        currentItems = res.data;
                        renderSuggestions(query, res.data);
                    } else {
                        renderEmpty(query);
                    }
                })
                .catch(err => {
                    console.error("Autocomplete error:", err);
                    hideDropdown();
                });
        }

        function renderSuggestions(query, items) {
            selectedIndex = -1;
            dropdown.innerHTML = '';

            const header = document.createElement('div');
            header.style.cssText = 'padding: 0.5rem 0.75rem; font-size: 0.725rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; background: #f8fafc;';
            header.innerText = `Saran Cepat (${items.length} hasil)`;
            dropdown.appendChild(header);

            items.forEach((item, idx) => {
                const el = document.createElement('a');
                el.href = item.url;
                el.className = 'autocomplete-item';
                el.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.65rem 0.75rem;
                    text-decoration: none;
                    color: inherit;
                    border-bottom: 1px solid #f8fafc;
                    transition: background 0.15s ease;
                    cursor: pointer;
                `;

                // Badge color based on type
                let badgeBg = '#2563eb';
                if (item.type === 'Gedung') badgeBg = '#0284c7';
                if (item.type === 'Jadwal') badgeBg = '#7c3aed';

                el.innerHTML = `
                    <span style="font-size: 0.675rem; font-weight: 700; color: #fff; background: ${badgeBg}; padding: 0.15rem 0.4rem; border-radius: 4px; white-space: nowrap;">
                        ${item.type}
                    </span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.85rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(item.title)}
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(item.subtitle)}
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                `;

                el.addEventListener('mouseenter', () => {
                    selectedIndex = idx;
                    updateHighlight(dropdown.querySelectorAll('.autocomplete-item'));
                });

                dropdown.appendChild(el);
            });

            // Footer link to full search
            const footer = document.createElement('div');
            footer.style.cssText = 'padding: 0.6rem 0.75rem; font-size: 0.8rem; text-align: center; background: #f8fafc; border-top: 1px solid #e2e8f0; cursor: pointer; color: #2563eb; font-weight: 600;';
            footer.innerText = `Lihat semua hasil untuk "${query}" →`;
            footer.addEventListener('click', () => goToFullSearch(query));
            dropdown.appendChild(footer);

            dropdown.style.display = 'block';
        }

        function renderEmpty(query) {
            selectedIndex = -1;
            dropdown.innerHTML = `
                <div style="padding: 1.25rem 1rem; text-align: center; color: #64748b; font-size: 0.825rem;">
                    <div>Tidak ada saran cepat untuk "<strong>${escapeHtml(query)}</strong>"</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Tekan Enter untuk mencari lebih lengkap (FR-032)</div>
                </div>
            `;
            dropdown.style.display = 'block';
        }

        function hideDropdown() {
            dropdown.style.display = 'none';
            selectedIndex = -1;
        }

        function updateHighlight(items) {
            items.forEach((item, idx) => {
                if (idx === selectedIndex) {
                    item.style.background = '#f1f5f9';
                } else {
                    item.style.background = 'transparent';
                }
            });
        }

        function goToFullSearch(query) {
            if (!query) return;
            const apiBase = (typeof baseUrl !== 'undefined' ? baseUrl : '/webpinjamft/public');
            window.location.href = apiBase + '/search?q=' + encodeURIComponent(query);
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/[&<>"']/g, function(m) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[m];
            });
        }
    }

    // Auto-initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        const navSearch = document.getElementById('navSearchInput');
        if (navSearch) initAutocomplete(navSearch);

        const homeSearch = document.getElementById('searchInput');
        if (homeSearch) initAutocomplete(homeSearch);
    });
})();
