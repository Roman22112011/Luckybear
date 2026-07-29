(function() {
    'use strict';

    const CONFIG = {
    apiUrl: null,  // не используем API
    trackUrl: null, // не используем API
    useLocalStorage: true, // ВКЛЮЧАЕМ РЕЖИМ localStorage
        defaultLinks: { register: '#', play: '#' },
        bonusEndTime: new Date().getTime() + (5 * 3600 + 42 * 60 + 18) * 1000,
    };

    const DOM = {
        navbar: document.getElementById('navbar'),
        mobileMenuBtn: document.getElementById('mobileMenuBtn'),
        mobileMenu: document.getElementById('mobileMenu'),
        timerHours: document.getElementById('hours'),
        timerMinutes: document.getElementById('minutes'),
        timerSeconds: document.getElementById('seconds'),
        gamesGrid: document.getElementById('gamesGrid'),
        reviewsGrid: document.getElementById('reviewsGrid'),
        exitPopup: document.getElementById('exitPopup'),
        exitPopupClose: document.getElementById('exitPopupClose'),
        pageModal: document.getElementById('pageModal'),
        modalClose: document.getElementById('modalClose'),
        modalBody: document.getElementById('modalBody'),
        stickyBtn: document.getElementById('stickyBtn'),
        particles: document.getElementById('particles'),
    };

    function init() {
        createParticles();
        initNavbar();
        initMobileMenu();
        initBonusTimer();
        loadDynamicContent();
        initExitPopup();
        initModals();
        initSmoothScroll();
        initTabs();
        trackPageView();
    }

    function createParticles() {
        if (!DOM.particles) return;
        const count = window.innerWidth < 768 ? 30 : 60;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            p.style.cssText = `
                width:${Math.random()*4+2}px;height:${Math.random()*4+2}px;
                left:${Math.random()*100}%;top:${Math.random()*100}%;
                animation-delay:${Math.random()*6}s;animation-duration:${Math.random()*6+4}s;
                opacity:${Math.random()*0.3+0.1};
            `;
            DOM.particles.appendChild(p);
        }
    }

    function initNavbar() {
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const s = window.pageYOffset;
            DOM.navbar.classList.toggle('scrolled', s > 50);
            if (window.innerWidth <= 768 && DOM.stickyBtn) {
                DOM.stickyBtn.style.transform = (s > 300 && s < lastScroll) ? 'translateY(0)' : 'translateY(100%)';
            }
            lastScroll = s;
        });
    }

    function initMobileMenu() {
        if (!DOM.mobileMenuBtn) return;
        DOM.mobileMenuBtn.addEventListener('click', () => {
            DOM.mobileMenuBtn.classList.toggle('active');
            DOM.mobileMenu.classList.toggle('active');
            document.body.style.overflow = DOM.mobileMenu.classList.contains('active') ? 'hidden' : '';
        });
        DOM.mobileMenu.querySelectorAll('a').forEach(l => l.addEventListener('click', () => {
            DOM.mobileMenuBtn.classList.remove('active');
            DOM.mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }));
    }

    function initBonusTimer() {
        function update() {
            let d = CONFIG.bonusEndTime - new Date().getTime();
            if (d < 0) { CONFIG.bonusEndTime = new Date().getTime() + 24*3600*1000; d = CONFIG.bonusEndTime - new Date().getTime(); }
            const h = Math.floor(d/3600000), m = Math.floor((d%3600000)/60000), s = Math.floor((d%60000)/1000);
            if (DOM.timerHours) DOM.timerHours.textContent = String(h).padStart(2,'0');
            if (DOM.timerMinutes) DOM.timerMinutes.textContent = String(m).padStart(2,'0');
            if (DOM.timerSeconds) DOM.timerSeconds.textContent = String(s).padStart(2,'0');
        }
        update(); setInterval(update, 1000);
    }

    async function loadDynamicContent() {
        try {
            const r = await fetch(CONFIG.apiUrl);
            const d = await r.json();
            if (d.success) {
                updateLinks(d.links);
                renderGames(d.games);
                renderReviews(d.reviews);
            }
        } catch(e) { renderDefaultGames(); renderDefaultReviews(); }
    }

    function updateLinks(links) {
        if (!links) return;
        document.querySelectorAll('[data-reg-link]').forEach(l => l.href = links.register_link || CONFIG.defaultLinks.register);
        document.querySelectorAll('[data-play-link]').forEach(l => l.href = links.play_link || CONFIG.defaultLinks.play);
    }

    function renderGames(games) {
        if (!DOM.gamesGrid) return;
        if (!games || !games.length) { renderDefaultGames(); return; }
        DOM.gamesGrid.innerHTML = games.map(g => `
            <div class="game-card" data-game-id="${g.id}" data-tab="${g.category}">
                <div class="game-image"><span class="game-img-placeholder">${g.icon||'🎰'}</span><div class="game-play-btn"><i class="fas fa-play"></i></div></div>
                <div class="game-info"><h3 class="game-name">${g.name}</h3><div class="game-meta"><span class="game-rtp">RTP ${g.rtp}%</span><span>${g.provider}</span></div></div>
            </div>`).join('');
        DOM.gamesGrid.querySelectorAll('.game-card').forEach(c => c.addEventListener('click', function() {
            const link = document.querySelector('[data-play-link]');
            if (link?.href) { trackEvent('game_click', {game: this.dataset.gameId}); window.open(link.href, '_blank'); }
        }));
    }

    function renderDefaultGames() {
        renderGames([
            {id:1,name:"Gates of Olympus",rtp:"96.50",provider:"Pragmatic Play",icon:"🏛️",category:"slots"},
            {id:2,name:"Sweet Bonanza",rtp:"96.51",provider:"Pragmatic Play",icon:"🍬",category:"slots"},
            {id:3,name:"Book of Dead",rtp:"96.21",provider:"Play'n GO",icon:"📖",category:"slots"},
            {id:4,name:"Aviator",rtp:"97.00",provider:"Spribe",icon:"✈️",category:"crash"},
            {id:5,name:"Crazy Time",rtp:"96.08",provider:"Evolution",icon:"🎡",category:"live"},
            {id:6,name:"Big Bamboo",rtp:"96.13",provider:"Push Gaming",icon:"🐼",category:"slots"},
        ]);
    }

    function renderReviews(reviews) {
        if (!DOM.reviewsGrid) return;
        if (!reviews || !reviews.length) { renderDefaultReviews(); return; }
        DOM.reviewsGrid.innerHTML = reviews.map(r => `
            <div class="review-card">
                <div class="review-header"><div class="review-avatar">${r.avatar||'👤'}</div><div><div class="review-name">${r.name}</div><div class="review-stars">${'⭐'.repeat(r.stars||5)}</div></div></div>
                <p class="review-text">${r.text}</p>
            </div>`).join('');
    }

    function renderDefaultReviews() {
        renderReviews([
            {name:"Мария К.",avatar:"👩‍🦰",stars:5,text:"Регистрировалась ради фриспинов, а в итоге подняла x1200 в Gates of Olympus. Вывела за 5 минут!"},
            {name:"Алексей В.",avatar:"👨",stars:5,text:"Медведь реально щедрый. Закинул 1000 рублей, играл в Aviator. Вывод пришел на карту мгновенно."},
            {name:"Игорь L.",avatar:"🧔",stars:5,text:"Лучший саппорт! Помогли разобраться с вейджером за пару минут. Все честно и прозрачно."},
        ]);
    }

    function initTabs() {
        document.querySelectorAll('.tab-btn').forEach(b => b.addEventListener('click', function() {
            const tab = this.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            DOM.gamesGrid?.querySelectorAll('.game-card').forEach(c => {
                c.style.display = (tab === 'all' || c.dataset.tab === tab) ? 'block' : 'none';
            });
            trackEvent('tab_switch', {tab});
        }));
    }

    function initExitPopup() {
        if (!DOM.exitPopup) return;
        let shown = false;
        document.addEventListener('mouseleave', e => { if (e.clientY <= 0 && !shown) { DOM.exitPopup.classList.add('active'); shown = true; trackEvent('exit_intent'); } });
        DOM.exitPopupClose?.addEventListener('click', () => DOM.exitPopup.classList.remove('active'));
        DOM.exitPopup.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
    }

    function initModals() {
        if (!DOM.pageModal) return;
        document.querySelectorAll('.page-link').forEach(l => l.addEventListener('click', async function(e) {
            e.preventDefault();
            try {
                const r = await fetch(`${CONFIG.apiUrl}?page=${this.dataset.page}`);
                const d = await r.json();
                if (DOM.modalBody) DOM.modalBody.innerHTML = d.success && d.content ? d.content : '<p>Страница недоступна</p>';
            } catch(e) { if (DOM.modalBody) DOM.modalBody.innerHTML = '<p>Ошибка загрузки</p>'; }
            DOM.pageModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }));
        DOM.modalClose?.addEventListener('click', closeModal);
        DOM.pageModal.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    }

    function closeModal() { DOM.pageModal?.classList.remove('active'); document.body.style.overflow = ''; }

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', function(e) {
            const t = document.querySelector(this.getAttribute('href'));
            if (t) { e.preventDefault(); window.scrollTo({top: t.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth'}); }
        }));
    }

    function trackEvent(event, data = {}) {
        try {
            fetch(CONFIG.trackUrl, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({event,data,url:window.location.href,timestamp:new Date().toISOString()})});
        } catch(e) {}
    }

    function trackPageView() {
        trackEvent('page_view', {page:'home'});
        document.querySelectorAll('[data-reg-link],[data-play-link]').forEach(b => b.addEventListener('click', function() {
            trackEvent(this.dataset.regLink ? 'register_click' : 'play_click', {button: this.textContent.trim(), position: this.closest('section')?.id || 'unknown'});
        }));
    }

    document.addEventListener('DOMContentLoaded', init);
})();
