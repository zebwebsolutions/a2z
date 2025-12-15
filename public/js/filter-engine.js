// filter-engine.js
document.addEventListener('DOMContentLoaded', function () {

    const resultsSelector = '#productResults';
    const chipsSelector = '#filterChips';
    const debounceMs = 300;

    // IMPORTANT: set baseUrl exactly like route('brand.category', ['category'=>..., 'brand'=>...])
    // The blade will output the correct url string when injected inline.
    const baseUrl = document.currentScript?.getAttribute('data-base-url') || window.location.pathname;
    // But we will set the baseUrl by inlining a global variable in the blade below.

    // Server defaults (you will inline these in the blade)
    const serverPriceMin = window.LSQ8_priceMin ?? 0;
    const serverPriceMax = window.LSQ8_priceMax ?? 0;

    // debounce util
    function debounce(fn, wait) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    }

    // Build URL from sidebar inputs
    function buildUrl() {
        const params = new URLSearchParams();

        // Brand (radio or single)
        const brand = document.querySelector("input[name='brand']:checked");
        if (brand) params.set('brand', brand.value);

        // RAM
        const ram = document.querySelector("input[name='ram']:checked");
        if (ram) params.set('ram', ram.value);

        // Storage
        const storage = document.querySelector("input[name='storage']:checked");
        if (storage) params.set('storage', storage.value);

        // Prices (hidden inputs used by Alpine)
        const minInput = document.querySelector("input[name='min']");
        const maxInput = document.querySelector("input[name='max']");

        const currentMin = Number(minInput?.value ?? serverPriceMin);
        const currentMax = Number(maxInput?.value ?? serverPriceMax);

        if (currentMin !== serverPriceMin) params.set('min', currentMin);
        if (currentMax !== serverPriceMax) params.set('max', currentMax);

        const query = params.toString();
        return baseUrl + (query ? '?' + query : '');
    }

    function generateSkeletons(count) {
        const skeletonEl = document.querySelector('#productSkeleton');
        if (!skeletonEl) return;

        skeletonEl.innerHTML = ''; // clear old skeletons

        for (let i = 0; i < count; i++) {
            skeletonEl.innerHTML += `
                <div class="bg-gray-300 h-64 rounded animate-pulse"></div>
            `;
        }
    }


    // AJAX loader expects JSON { products, chips }
    async function ajaxLoad(url) {
        const resultsEl   = document.querySelector(resultsSelector);
        const chipsEl     = document.querySelector(chipsSelector);
        const skeletonEl  = document.querySelector('#productSkeleton');

        if (!resultsEl & !skeletonEl) return;


        const productCards = 9;
        generateSkeletons(productCards);

        resultsEl.style.opacity = 0;
        skeletonEl.classList.remove('hidden');

        const minSkeletonTime = 1000; // 1 second minimum
        const startTime = Date.now();

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error("Bad response");

            const data = await res.json();

            // Update results
            if (data.products !== undefined) {
                resultsEl.innerHTML = data.products;
            }

            // Update chips
            if (data.chips !== undefined && chipsEl) {
                chipsEl.innerHTML = data.chips;
            }

            window.history.pushState({}, '', url);

            bindPagination();

        } catch (err) {
            console.error(err);
            window.location.href = url;
            return;
        }

        // -----------------------------
        // Enforce 1-second minimum skeleton visibility
        // -----------------------------
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, minSkeletonTime - elapsed);

        setTimeout(() => {
            skeletonEl.classList.add('hidden');
            resultsEl.style.opacity = '1';

        }, remaining);
    }


    const debouncedLoad = debounce((url) => ajaxLoad(url), debounceMs);

    // delegated handler for chip clicks (single attachment, won't duplicate)
    document.addEventListener('click', function (e) {
        const chip = e.target.closest('.chip[data-remove]');
        if (!chip) return;

        e.preventDefault();
        const type = chip.dataset.remove;

        if (type === 'brand') {
            document.querySelectorAll("input[name='brand']").forEach(el => el.checked = false);
        } else if (type === 'ram') {
            document.querySelectorAll("input[name='ram']").forEach(el => el.checked = false);
        } else if (type === 'storage') {
            document.querySelectorAll("input[name='storage']").forEach(el => el.checked = false);
        } else if (type === 'price') {
            const minEl = document.querySelector("input[name='min']");
            const maxEl = document.querySelector("input[name='max']");
            if (minEl) minEl.value = serverPriceMin;
            if (maxEl) maxEl.value = serverPriceMax;
            // notify Alpine slider if present
            window.dispatchEvent(new CustomEvent('ajaxFilter', {
                detail: `min=${serverPriceMin}&max=${serverPriceMax}`
            }));
        } else if (type === 'all') {
            // redirect to base (clear all)
            window.location.href = baseUrl;
            return;
        }

        // fetch with updated query
        const url = buildUrl();
        debouncedLoad(url);
    });

    // Bind sidebar inputs (these are static => safe to bind)
    function bindSidebarInputs() {
        document.querySelectorAll('.autoFilter').forEach(el => {
            el.onchange = () => {
                const url = buildUrl();
                debouncedLoad(url);
            };
        });
    }

    // Bind pagination links inside #productResults
    function bindPagination() {
        document.querySelectorAll(resultsSelector + ' .pagination a').forEach(link => {
            link.onclick = (e) => {
                e.preventDefault();
                ajaxLoad(link.href);
            };
        });
    }

    // Listen to Alpine slider custom event
    window.addEventListener('ajaxFilter', function (e) {
        // e.detail is query string like "min=100&max=500"
        const params = new URLSearchParams(e.detail);
        const minInput = document.querySelector("input[name='min']");
        const maxInput = document.querySelector("input[name='max']");
        if (minInput && params.has('min')) minInput.value = params.get('min');
        if (maxInput && params.has('max')) maxInput.value = params.get('max');

        debouncedLoad(buildUrl());
    });

    // handle browser back/forward
    window.addEventListener('popstate', function () {
        ajaxLoad(window.location.href);
    });

    // Initial bindings
    bindSidebarInputs();
    bindPagination();

});