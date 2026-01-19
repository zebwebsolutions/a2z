// Save scroll position BEFORE page unloads
window.addEventListener('beforeunload', function () {
    sessionStorage.setItem('scrollY', window.scrollY);
});

// Restore scroll position AFTER page load & layout
window.addEventListener('pageshow', function () {
    const scrollY = sessionStorage.getItem('scrollY');

    if (scrollY !== null) {
        // Delay ensures images, grids, pagination are rendered
        setTimeout(() => {
            window.scrollTo({
                top: parseInt(scrollY, 10),
                left: 0,
                behavior: 'instant' // no animation
            });
            sessionStorage.removeItem('scrollY');
        }, 50);
    }
});
