// Registers Alpine components used across Admin screens. They must load before
// Alpine starts, which is why they are imported here (this file loads before shared/app.js).
import './document-viewer.js';
import './seller-compliance.js';
import './complaints.js';
import './reports.js';
import './motion.js';

const { body } = document;

const checkStatusUrl = body.dataset.checkStatusUrl;
const loginUrl = body.dataset.loginUrl;

if (checkStatusUrl && loginUrl) {
    window.setInterval(() => {
        fetch(checkStatusUrl, { headers: { Accept: 'application/json' } })
            .then(response => response.json())
            .then(data => {
                if (!data.active) window.location.href = loginUrl;
            })
            .catch(() => {});
    }, 5000);
}

const bellWrap = document.getElementById('adminBellWrap');
const bellButton = document.getElementById('adminBellButton');
const bellMenu = document.getElementById('adminBellMenu');

if (bellWrap && bellButton && bellMenu) {
    const closeBell = () => {
        bellMenu.hidden = true;
        bellButton.setAttribute('aria-expanded', 'false');
    };

    bellButton.addEventListener('click', () => {
        const opening = bellMenu.hidden;
        bellMenu.hidden = !opening;
        bellButton.setAttribute('aria-expanded', String(opening));
    });

    document.addEventListener('click', event => {
        if (!bellWrap.contains(event.target)) closeBell();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !bellMenu.hidden) {
            closeBell();
            bellButton.focus();
        }
    });

    let latestId = Number(bellWrap.dataset.latestId || 0);
    let audio;

    function playNotificationSound() {
        try {
            audio ||= new (window.AudioContext || window.webkitAudioContext)();
            audio.resume();

            const oscillator = audio.createOscillator();
            const gain = audio.createGain();
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.07, audio.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + 0.16);
            oscillator.connect(gain).connect(audio.destination);
            oscillator.start();
            oscillator.stop(audio.currentTime + 0.17);
        } catch (_) {}
    }

    async function refreshAdminNotifications() {
        if (document.hidden || !bellWrap.dataset.recentUrl) return;

        try {
            const response = await fetch(bellWrap.dataset.recentUrl, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            const badge = document.getElementById('adminNotificationCount');
            const list = document.getElementById('adminBellList');

            if (badge) {
                badge.hidden = !data.unread_count;
                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
            }
            if (list && data.html) list.innerHTML = data.html;
            if (data.latest_id > latestId) playNotificationSound();

            latestId = Math.max(latestId, data.latest_id);
        } catch (_) {}
    }

    window.setInterval(refreshAdminNotifications, 3000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshAdminNotifications();
    });
}