(function() {
    'use strict';

    const CONFIG = {
        useLocalStorage: true,
        defaultLinks: { register: '#', play: '#' },
        bonusEndTime: new Date().getTime() + (5 * 3600 + 42 * 60 + 18) * 1000,
    };

    // ==================== ГЕНЕРАЦИЯ УНИКАЛЬНОГО ID ПОЛЬЗОВАТЕЛЯ ====================
    function getUserId() {
        let userId = localStorage.getItem('luckybear_user_id');
        if (!userId) {
            userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('luckybear_user_id', userId);
        }
        return userId;
    }

    // ==================== ОПРЕДЕЛЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ====================
    function getDeviceInfo() {
        const ua = navigator.userAgent;
        let device = 'Desktop';
        let os = 'Unknown';
        let browser = 'Unknown';

        if (/Mobi|Android|iPhone|iPod/i.test(ua)) device = 'Mobile';
        else if (/iPad|Tablet/i.test(ua)) device = 'Tablet';

        if (/Windows/i.test(ua)) os = 'Windows';
        else if (/Mac OS/i.test(ua)) os = 'macOS';
        else if (/Android/i.test(ua)) os = 'Android';
        else if (/iOS|iPhone|iPad/i.test(ua)) os = 'iOS';
        else if (/Linux/i.test(ua)) os = 'Linux';

        if (/Chrome/i.test(ua) && !/Edge/i.test(ua)) browser = 'Chrome';
        else if (/Safari/i.test(ua) && !/Chrome/i.test(ua)) browser = 'Safari';
        else if (/Firefox/i.test(ua)) browser = 'Firefox';
        else if (/Edge/i.test(ua)) browser = 'Edge';
        else if (/Samsung/i.test(ua)) browser = 'Samsung Internet';
        else if (/Opera|OPR/i.test(ua)) browser = 'Opera';

        const screen = `${window.screen.width}×${window.screen.height}`;
        return { device, os, browser, screen, userAgent: ua };
    }

    // ==================== ПОЛУЧЕНИЕ ГЕО ЧЕРЕЗ API ====================
    async function getGeoData() {
        // Пробуем несколько бесплатных API
        const apis = [
            'https://ipapi.co/json/',
            'https://ipwhois.app/json/',
            'https://api.ipify.org?format=json'
        ];

        for (const api of apis) {
            try {
                const resp = await fetch(api, { signal: AbortSignal.timeout(3000) });
                const data = await resp.json();

                if (data.country_code) {
                    return {
                        code: data.country_code,
                        name: data.country_name || data.country || '',
                        city: data.city || '',
                        flag: getFlagEmoji(data.country_code),
                        ip: data.ip || ''
                    };
                }
            } catch(e) {
                continue;
            }
        }

        // Если все API не ответили — возвращаем неизвестно
        return {
            code: 'XX',
            name: 'Неизвестно',
            city: '',
            flag: '🌐',
            ip: ''
        };
    }

    function getFlagEmoji(countryCode) {
        if (!countryCode || countryCode === 'XX') return '🌐';
        const codePoints = countryCode.toUpperCase().split('').map(char => 127397 + char.charCodeAt());
        return String.fromCodePoint(...codePoints);
    }

    // ==================== СОХРАНЕНИЕ РЕАЛЬНЫХ ДАННЫХ В АДМИНКУ ====================
    function saveRealUserData(geoData) {
        if (!CONFIG.useLocalStorage) return;

        const userId = getUserId();
        const deviceInfo = getDeviceInfo();
        const raw = localStorage.getItem('luckybear_admin');

        let adminData;
        if (raw) {
            try { adminData = JSON.parse(raw); } catch(e) { adminData = null; }
        }

        if (!adminData || !adminData.users) {
            // Создаём структуру если нет
            adminData = adminData || {};
            adminData.users = adminData.users || [];
            adminData.events = adminData.events || [];
            adminData.links = adminData.links || { register_link: '#', play_link: '#' };
            adminData.games = adminData.games || [];
            adminData.reviews = adminData.reviews || [];
            adminData.password = adminData.password || 'admin123';
        }

        // Ищем существующего пользователя
        const existingUserIndex = adminData.users.findIndex(u => u.id === userId);

        const userData = {
            id: userId,
            nick: 'User_' + userId.split('_')[1],
            country: {
                code: geoData.code,
                name: geoData.name,
                flag: geoData.flag
            },
            city: geoData.city,
            device: deviceInfo.device,
            os: deviceInfo.os,
            browser: deviceInfo.browser,
            screen: deviceInfo.screen,
            ip: geoData.ip,
            firstVisit: existingUserIndex >= 0 ? adminData.users[existingUserIndex].firstVisit : new Date().toISOString(),
            lastActive: new Date().toISOString(),
            timeOnSite: existingUserIndex >= 0 ? (adminData.users[existingUserIndex].timeOnSite || 0) + 1 : 1,
            clicks: existingUserIndex >= 0 ? adminData.users[existingUserIndex].clicks || [] : []
        };

        if (existingUserIndex >= 0) {
            adminData.users[existingUserIndex] = userData;
        } else {
            adminData.users.push(userData);
        }

        // Ограничиваем количество пользователей (последние 200)
        if (adminData.users.length > 200) {
            adminData.users = adminData.users.slice(-200);
        }

        localStorage.setItem('luckybear_admin', JSON.stringify(adminData));
        return { adminData, userIndex: existingUserIndex >= 0 ? existingUserIndex : adminData.users.length - 1 };
    }

    // ==================== ТРЕКИНГ СОБЫТИЙ ====================
    function trackEvent(event, data = {}) {
        if (!CONFIG.useLocalStorage) return;

        const raw = localStorage.getItem('luckybear_admin');
        if (!raw) return;

        try {
            const adminData = JSON.parse(raw);
            const userId = getUserId();

            // Добавляем событие в общую ленту
            adminData.events.unshift({
                event: event,
                meta: JSON.stringify(data),
                time: new Date().toISOString(),
                userId: userId
            });
            if (adminData.events.length > 500) adminData.events = adminData.events.slice(0, 500);

            // Добавляем клик пользователю
            const user = adminData.users.find(u => u.id === userId);
            if (user) {
                user.clicks.push({
                    action: event,
                    meta: JSON.stringify(data),
                    time: new Date().toISOString()
                });
                user.lastActive = new Date().toISOString();
                // Ограничиваем историю кликов
                if (user.clicks.length > 100) user.clicks = user.clicks.slice(-100);
            }

            localStorage.setItem('luckybear_admin', JSON.stringify(adminData));
        } catch(e) {}
    }

    // ==================== DOM ====================
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

    // ==================== ИНИЦИАЛИЗАЦИЯ ====================
    async function init() {
        createParticles();
        initNavbar();
        initMobileMenu();
        initBonusTimer();

        // Получаем гео и сохраняем пользователя
        const geoData = await getGeoData();
        saveRealUserData(geoData);

        loadDynamicContent();
        initExitPopup();
        initModals();
        initSmoothScroll();
        initTabs();
        trackPageView();

        // Отслеживаем время на сайте
        startTimeOnSiteTracking();
    }

    function startTimeOnSiteTracking() {
        setInterval(() => {
            const raw = localStorage.getItem('luckybear_admin');
            if (!raw) return;
            try {
                const adminData = JSON.parse(raw);
                const userId = getUserId();
                const user = adminData.users.find(u => u.id === userId);
                if (user) {
                    user.timeOnSite = (user.timeOnSite || 0) + 5;
                    user.lastActive = new Date().toISOString();
                    localStorage.setItem('luckybear_admin', JSON.stringify(adminData));
                }
            } catch(e) {}
        }, 5000); // Каждые 5 секунд
    }

    function createParticles() {
        if (!DOM.particles) return;
        const count = window.innerWidth < 768 ? 30 : 60;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            p.style.cssText = `width:${Math.random()*4+2}px;height:${Math.random()*4+2}px;left:${Math.random()*100}%;top:${Math.random()*100}%;animation-delay:${Math.random()*6}s;animation-duration:${Math.random()*6+4}s;opacity:${Math.random()*0.3+0.1};`;
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
        if (CONFIG.useLocalStorage) {
            const raw = localStorage.getItem('luckybear_admin');
            if (raw) {
                try {
                    const adminData = JSON.parse(raw);
                    updateLinks(adminData.links);
                    if (adminData.games && adminData.games.length > 0) {
                        renderGames(adminData.games.filter(g => g.is_active));
                    } else {
                        renderDefaultGames();
                    }
                    if (adminData.reviews && adminData.reviews.length > 0) {
                        renderReviews(adminData.reviews.filter(r => r.is_active));
                    } else {
                        renderDefaultReviews();
                    }
                    return;
                } catch(e) {}
            }
        }
        renderDefaultGames();
        renderDefaultReviews();
    }

    function updateLinks(links) {
        if (!links) return;
        document.querySelectorAll('[data-reg-link]').forEach(l => l.href = links.register_link || CONFIG.defaultLinks.register);
        document.querySelectorAll('[data-play-link]').forEach(l => l.href = links.play_link || CONFIG.defaultLinks.play);
    }

    function renderGames(games) {
        if (!DOM.gamesGrid) return;
        if (!games || !games.length) { renderDefaultGames(); return; }
        DOM.gamesGrid.innerHTML = games.map(g => {
            let iconHTML;
            if (g.iconType === 'image' && g.icon && g.icon.startsWith('data:image')) {
                iconHTML = `<img src="${g.icon}" alt="${g.name}" style="width:100%;height:100%;object-fit:cover;border-radius:12px">`;
            } else {
                iconHTML = `<span class="game-img-placeholder">${g.icon || '🎰'}</span>`;
            }
            return `
                <div class="game-card" data-game-id="${g.id}" data-tab="${g.category}">
                    <div class="game-image">${iconHTML}<div class="game-play-btn"><i class="fas fa-play"></i></div></div>
                    <div class="game-info">
                        <h3 class="game-name">${g.name}</h3>
                        <div class="game-meta">
                            <span class="game-rtp">RTP ${g.rtp}%</span>
                            <span>${g.provider}</span>
                        </div>
                    </div>
                </div>`;
        }).join('');
        DOM.gamesGrid.querySelectorAll('.game-card').forEach(c => c.addEventListener('click', function() {
            const link = document.querySelector('[data-play-link]');
            if (link?.href) { trackEvent('game_click', {game: this.dataset.gameId}); window.open(link.href, '_blank'); }
        }));
    }

    function renderDefaultGames() {
        renderGames([
            {id:1,name:"Gates of Olympus",icon:"🏛️",iconType:"emoji",rtp:"96.50",provider:"Pragmatic Play",category:"slots"},
            {id:2,name:"Sweet Bonanza",icon:"🍬",iconType:"emoji",rtp:"96.51",provider:"Pragmatic Play",category:"slots"},
            {id:3,name:"Book of Dead",icon:"📖",iconType:"emoji",rtp:"96.21",provider:"Play'n GO",category:"slots"},
            {id:4,name:"Aviator",icon:"✈️",iconType:"emoji",rtp:"97.00",provider:"Spribe",category:"crash"},
            {id:5,name:"Crazy Time",icon:"🎡",iconType:"emoji",rtp:"96.08",provider:"Evolution",category:"live"},
            {id:6,name:"Big Bamboo",icon:"🐼",iconType:"emoji",rtp:"96.13",provider:"Push Gaming",category:"slots"},
        ]);
    }

    function renderReviews(reviews) {
        if (!DOM.reviewsGrid) return;
        if (!reviews || !reviews.length) { renderDefaultReviews(); return; }
        DOM.reviewsGrid.innerHTML = reviews.map(r => {
            let avatarHTML;
            if (r.avatarType === 'image' && r.avatar && r.avatar.startsWith('data:image')) {
                avatarHTML = `<img src="${r.avatar}" alt="${r.name}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
            } else {
                avatarHTML = r.avatar || '👤';
            }
            return `
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-avatar" style="overflow:hidden">${avatarHTML}</div>
                        <div>
                            <div class="review-name">${r.name}</div>
                            <div class="review-stars">${'⭐'.repeat(r.stars || 5)}</div>
                        </div>
                    </div>
                    <p class="review-text">${r.text}</p>
                </div>`;
        }).join('');
    }

    function renderDefaultReviews() {
        renderReviews([
            {name:"Мария К.",avatar:"👩‍🦰",avatarType:"emoji",stars:5,text:"Регистрировалась ради фриспинов, а в итоге подняла x1200 в Gates of Olympus. Вывела за 5 минут!"},
            {name:"Алексей В.",avatar:"👨",avatarType:"emoji",stars:5,text:"Медведь реально щедрый. Закинул 1000 рублей, играл в Aviator. Вывод пришел на карту мгновенно."},
            {name:"Игорь L.",avatar:"🧔",avatarType:"emoji",stars:5,text:"Лучший саппорт! Помогли разобраться с вейджером за пару минут. Всё честно и прозрачно."},
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
        document.addEventListener('mouseleave', e => {
            if (e.clientY <= 0 && !shown) {
                DOM.exitPopup.classList.add('active');
                shown = true;
                trackEvent('exit_intent');
            }
        });
        DOM.exitPopupClose?.addEventListener('click', () => DOM.exitPopup.classList.remove('active'));
        DOM.exitPopup.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
    }

    function initModals() {
        if (!DOM.pageModal) return;
        document.querySelectorAll('.page-link').forEach(l => l.addEventListener('click', function(e) {
            e.preventDefault();
            if (DOM.modalBody) DOM.modalBody.innerHTML = '<h2>'+this.textContent+'</h2><p>Страница в разработке.</p>';
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

    function trackPageView() {
        trackEvent('page_view', {page:'home'});
        document.querySelectorAll('[data-reg-link],[data-play-link]').forEach(b => b.addEventListener('click', function() {
            trackEvent(this.dataset.regLink ? 'register_click' : 'play_click', {
                button: this.textContent.trim(),
                position: this.closest('section')?.id || 'unknown'
            });
        }));

        // Отслеживание скролла
        let scrollTracked = {50: false, 100: false};
        window.addEventListener('scroll', () => {
            const scrollPercent = Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100);
            if (scrollPercent >= 50 && !scrollTracked[50]) { scrollTracked[50] = true; trackEvent('scroll_50%'); }
            if (scrollPercent >= 90 && !scrollTracked[100]) { scrollTracked[100] = true; trackEvent('scroll_100%'); }
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
