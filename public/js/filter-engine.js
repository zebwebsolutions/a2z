document.addEventListener('DOMContentLoaded', function () {

    const resultsSelector   = '#productResults';
    const paginationSelector = '#paginationWrapper';
    const skeletonSelector  = '#productSkeleton';
    const debounceMs = 300;

    const baseUrl = window.location.pathname;

    const serverPriceMin = window.LSQ8_priceMin ?? 0;
    const serverPriceMax = window.LSQ8_priceMax ?? 0;

    if (!resultsSelector) {
        return;
    }

    let isAjaxLoading = false;

    function hasActiveFilters() {
        const params = new URLSearchParams(window.location.search);

        console.log('Current URL params:', params.toString());

        // Ignore pagination
        params.delete('page');

        return params.toString().length > 0;
    }


    /* ----------------------------------
    * Top Loading Bar
    * ---------------------------------- */
    const topLoader = document.getElementById('topLoader');

    function startTopLoader() {
        if (!topLoader) return;

        topLoader.style.opacity = '1';
        topLoader.style.width = '20%';

        // Simulate progressive loading
        setTimeout(() => {
            topLoader.style.width = '60%';
        }, 200);
    }

    function finishTopLoader() {
        if (!topLoader) return;

        topLoader.style.width = '100%';

        setTimeout(() => {
            topLoader.style.opacity = '0';
            topLoader.style.width = '0%';
        }, 300);
    }

    /* ----------------------------------
     * Debounce
     * ---------------------------------- */
    function debounce(fn, wait) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    }

    /* ----------------------------------
     * Build query URL from sidebar inputs
     * ---------------------------------- */
    function buildUrl() {
        const params = new URLSearchParams();

        // Brand (single)
        const brand = document.querySelector("input[name='brand']:checked");
        if (brand) params.set('brand', brand.value);

        // RAM (single)
        const ram = document.querySelector("input[name='ram']:checked");
        if (ram) params.set('ram', ram.value);

        // Storage (single)
        const storage = document.querySelector("input[name='storage']:checked");
        if (storage) params.set('storage', storage.value);

        // Battery health (multiple)
        document.querySelectorAll("input[name='battery[]']:checked")
            .forEach(el => params.append('battery[]', el.value));

        // Price (from Alpine hidden inputs)
        const minInput = document.querySelector("input[name='min']");
        const maxInput = document.querySelector("input[name='max']");

        const min = minInput ? Number(minInput.value) : serverPriceMin;
    const max = maxInput ? Number(maxInput.value) : serverPriceMax;

        if (!(min === serverPriceMin && max === serverPriceMax)) {
            params.set('min', min);
            params.set('max', max);
        }

        return baseUrl + (params.toString() ? '?' + params.toString() : '');
    }

    /* ----------------------------------
     * Skeleton Loader
     * ---------------------------------- */
    function showSkeletons(count = 6) {
        const skeleton = document.querySelector(skeletonSelector);
        if (!skeleton) return;

        skeleton.innerHTML = '';
        skeleton.classList.remove('hidden');

        for (let i = 0; i < count; i++) {
            skeleton.innerHTML += `
                <div class="bg-white rounded-xl border shadow-sm p-4 animate-pulse flex flex-col">
                    <div class="w-full h-44 bg-gray-200 rounded mb-4"></div>
                    <div class="space-y-2 mb-4">
                        <div class="h-4 bg-gray-200 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    </div>
                    <div class="h-6 bg-gray-200 rounded w-1/2 mt-auto"></div>
                </div>
            `;
        }
    }

    function hideSkeletons() {
        const skeleton = document.querySelector(skeletonSelector);
        if (skeleton) skeleton.classList.add('hidden');
    }

    /* ----------------------------------
     * AJAX Load
     * ---------------------------------- */
    async function ajaxLoad(url, pushState = true) {
        const results    = document.querySelector(resultsSelector);
        const pagination = document.querySelector(paginationSelector);

        if (!results) return;
        
        startTopLoader();
        showSkeletons();
        results.classList.add('hidden');

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error('Bad response');

            const data = await res.json();

            results.innerHTML = data.products ?? '';
            if (pagination) pagination.innerHTML = data.pagination ?? '';

            if (pushState) {
                window.history.pushState({}, '', url);
            }

            bindPagination();

        } catch (e) {
            console.error(e);
            window.location.href = url; // fallback
            return;
        }

        hideSkeletons();
        results.classList.remove('hidden');
        finishTopLoader();
    }

    const debouncedLoad = debounce((url) => ajaxLoad(url), debounceMs);

    /* ----------------------------------
     * Sidebar filter binding
     * ---------------------------------- */
    function bindSidebarInputs() {
        document.querySelectorAll('.autoFilter').forEach(el => {
            el.addEventListener('change', () => {
                debouncedLoad(buildUrl());
            });
        });
    }

    /* ----------------------------------
     * Pagination binding
     * ---------------------------------- */
    function bindPagination() {
        document.querySelectorAll(`${paginationSelector} a`).forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                ajaxLoad(link.href);
            });
        });
    }

    /* ----------------------------------
     * Alpine price slider event
     * ---------------------------------- */
    window.addEventListener('ajaxFilter', function (e) {

        // 🚫 Ignore Alpine init / re-render events
        if (isAjaxLoading) return;

        const params = new URLSearchParams(e.detail);

        const minInput = document.querySelector("input[name='min']");
        const maxInput = document.querySelector("input[name='max']");

        const newMin = Number(params.get('min'));
        const newMax = Number(params.get('max'));

        const currentMin = Number(minInput?.value ?? serverPriceMin);
        const currentMax = Number(maxInput?.value ?? serverPriceMax);

        // 🚫 Ignore no-op changes
        if (newMin === currentMin && newMax === currentMax) {
            return;
        }

        if (minInput) minInput.value = newMin;
        if (maxInput) maxInput.value = newMax;

        debouncedLoad(buildUrl());
    });

    /* ----------------------------------
     * Browser back / forward
     * ---------------------------------- */
    window.addEventListener('popstate', function () {
        ajaxLoad(window.location.href, false);
    });

    /* ----------------------------------
    * Filter Chips (Clear / Remove)
    * ---------------------------------- */
    document.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-remove]');
        if (!chip) return;

        e.preventDefault();
        const type = chip.dataset.remove;

        // -------- Clear ALL filters --------
        if (type === 'all') {

            // Uncheck all radios & checkboxes
            document.querySelectorAll(
                "input[type='checkbox'], input[type='radio']"
            ).forEach(el => el.checked = false);

            // Reset price inputs
            const minEl = document.querySelector("input[name='min']");
            const maxEl = document.querySelector("input[name='max']");
            if (minEl) minEl.value = serverPriceMin;
            if (maxEl) maxEl.value = serverPriceMax;

            // Load base results via AJAX
            debouncedLoad(baseUrl);
            return;
        }

        // -------- Optional: individual chip removal (future-proof) --------
        if (type === 'brand') {
            document.querySelectorAll("input[name='brand']").forEach(el => el.checked = false);
        }
        if (type === 'ram') {
            document.querySelectorAll("input[name='ram']").forEach(el => el.checked = false);
        }
        if (type === 'storage') {
            document.querySelectorAll("input[name='storage']").forEach(el => el.checked = false);
        }
        if (type === 'battery') {
            document.querySelectorAll("input[name='battery[]']").forEach(el => el.checked = false);
        }

        debouncedLoad(buildUrl());
    });

    /* ----------------------------------
     * Init
     * ---------------------------------- */
    bindSidebarInputs();
    bindPagination();
});