(function () {
    var bar = document.getElementById('mabox-reading-progress');
    if (!bar) {
        return;
    }

    function updateProgress() {
        var scrollTop   = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight   = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var progress    = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        bar.style.width = Math.min(progress, 100) + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
})();
