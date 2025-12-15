const input = document.getElementById('liveSearchInput');
const resultsBox = document.getElementById('liveSearchResults');
let searchTimeout = null;

input.addEventListener('input', function () {
    clearTimeout(searchTimeout);

    const query = this.value.trim();

    if (query.length < 2) {
        resultsBox.classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`/search/suggest?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => renderResults(data));
    }, 300); // debounce
});

function renderResults(products) {
    if (products.length === 0) {
        resultsBox.innerHTML = `
            <div class="p-3 text-gray-500 text-sm">No results found</div>
        `;
        resultsBox.classList.remove('hidden');
        return;
    }

    resultsBox.innerHTML = products.map(p => `
        <a href="/product/${p.slug}"
            class="flex items-center gap-3 p-3 hover:bg-gray-100 transition border-b last:border-0">
            <div class="flex-1">
                <p class="font-medium text-sm">${p.name}</p>
            </div>
        </a>
    `).join('');

    resultsBox.classList.remove('hidden');
}

// Close dropdown on click outside
document.addEventListener('click', function (event) {
    if (!input.contains(event.target) && !resultsBox.contains(event.target)) {
        resultsBox.classList.add('hidden');
    }
});
