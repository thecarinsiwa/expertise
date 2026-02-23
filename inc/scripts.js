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
})();
