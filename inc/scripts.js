/**
 * Expertise – Mega-menu et position du hero
 */
(function () {
    var triggers = document.querySelectorAll('.nav-mega-trigger');
    var megas = document.querySelectorAll('.mega-menu');
    var closeBtns = document.querySelectorAll('[data-close-mega]');
    var body = document.body;
    var siteHeader = document.querySelector('.site-header');

    function updateMegaMenuPosition() {
        if (siteHeader) {
            var headerHeight = siteHeader.offsetHeight;
            body.style.paddingTop = headerHeight + 'px';
            var hero = document.querySelector('.hero');
            if (hero) hero.style.marginTop = '-' + headerHeight + 'px';
            for (var i = 0; i < megas.length; i++) {
                megas[i].style.top = headerHeight + 'px';
                megas[i].style.maxHeight = 'calc(100vh - ' + headerHeight + 'px)';
            }
        }
    }

    updateMegaMenuPosition();
    window.addEventListener('resize', updateMegaMenuPosition);

    var navbarCollapse = document.getElementById('navbarMain');
    if (navbarCollapse) {
        navbarCollapse.addEventListener('show.bs.collapse', updateMegaMenuPosition);
        navbarCollapse.addEventListener('hidden.bs.collapse', updateMegaMenuPosition);
    }

    function closeAll() {
        for (var i = 0; i < megas.length; i++) {
            megas[i].classList.remove('show');
            (function (m) {
                setTimeout(function () { m.setAttribute('aria-hidden', 'true'); }, 300);
            })(megas[i]);
        }
        for (var j = 0; j < triggers.length; j++) triggers[j].classList.remove('active');
    }

    function openMega(id) {
        closeAll();
        var el = document.getElementById(id);
        if (el) {
            updateMegaMenuPosition();
            el.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { el.classList.add('show'); });
            });
        }
        var t = document.querySelector('.nav-mega-trigger[data-mega="' + id + '"]');
        if (t) t.classList.add('active');
    }

    for (var k = 0; k < triggers.length; k++) {
        (function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                var id = trigger.getAttribute('data-mega');
                if (document.getElementById(id).classList.contains('show')) closeAll();
                else openMega(id);
            });
        })(triggers[k]);
    }

    for (var n = 0; n < closeBtns.length; n++) {
        closeBtns[n].addEventListener('click', closeAll);
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.nav-mega-trigger') || e.target.closest('.mega-menu')) return;
        closeAll();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    // Copier le lien (partage)
    document.querySelectorAll('.copy-link[data-copy-url]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            var url = el.getAttribute('data-copy-url');
            var label = el.querySelector('.copy-link-text');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    if (label) label.textContent = 'Lien copié !';
                    setTimeout(function () { if (label) label.textContent = 'Copier le lien'; }, 2000);
                });
            } else {
                var ta = document.createElement('textarea');
                ta.value = url;
                ta.setAttribute('readonly', '');
                ta.style.position = 'absolute';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    if (label) label.textContent = 'Lien copié !';
                    setTimeout(function () { if (label) label.textContent = 'Copier le lien'; }, 2000);
                } catch (err) {}
                document.body.removeChild(ta);
            }
        });
    });

    // Imprimer
    document.querySelectorAll('.share-print').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    });
})();

/**
 * Hero carousel : pause quand l’onglet n’est pas visible (économise les ressources)
 */
(function () {
    var carouselEl = document.getElementById('heroAnnouncementsCarousel');
    if (!carouselEl || typeof bootstrap === 'undefined' || !bootstrap.Carousel) return;
    function onVisibilityChange() {
        var instance = bootstrap.Carousel.getInstance(carouselEl);
        if (!instance) return;
        if (document.hidden) instance.pause();
        else instance.cycle();
    }
    if (typeof document.hidden !== 'undefined') {
        document.addEventListener('visibilitychange', onVisibilityChange);
    }
})();
