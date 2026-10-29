/**
 * topic-tabs.js
 * Topic page: Overview / Study Notes / Missions / Assessment tabs, the notes contents list,
 * and "notes read" tracking. Without this script every panel is simply shown in order.
 *
 * Tab choice: ?tab=missions or #missions wins, then the last tab used on this topic, then
 * Overview. "Read" notes are display-only (stored per topic in this browser) and never gate
 * missions or the quiz.
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-topic-tabs]');
    if (!root) return;

    const topicId = root.dataset.topicId;
    const tablist = root.querySelector('[role="tablist"]');
    const tabs = Array.from(root.querySelectorAll('[role="tab"]'));
    const panels = tabs.map((t) => document.getElementById(t.getAttribute('aria-controls')));
    const names = tabs.map((t) => t.dataset.tab);
    const storeKey = `netlearn-topic-tab-${topicId}`;
    const notesKey = `netlearn-notes-read-${topicId}`;

    const store = {
        get(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* private mode */ } },
    };

    root.classList.add('is-tabbed');
    tablist.hidden = false;

    function select(name, { focus = false, remember = true } = {}) {
        const i = names.indexOf(name);
        if (i < 0) return;
        tabs.forEach((tab, j) => {
            const on = j === i;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            if (panels[j]) panels[j].hidden = !on;
        });
        if (focus) tabs[i].focus();
        if (remember) store.set(storeKey, name);
        // On narrow screens the tab bar scrolls sideways: keep the selected tab visible
        const tab = tabs[i];
        if (tab.offsetLeft < tablist.scrollLeft || tab.offsetLeft + tab.offsetWidth > tablist.scrollLeft + tablist.clientWidth) {
            tablist.scrollLeft = tab.offsetLeft - (tablist.clientWidth - tab.offsetWidth) / 2;
        }
    }

    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => select(names[i]));
        tab.addEventListener('keydown', (e) => {
            let next = null;
            if (e.key === 'ArrowRight') next = (i + 1) % tabs.length;
            else if (e.key === 'ArrowLeft') next = (i - 1 + tabs.length) % tabs.length;
            else if (e.key === 'Home') next = 0;
            else if (e.key === 'End') next = tabs.length - 1;
            if (next === null) return;
            e.preventDefault();
            select(names[next], { focus: true });
        });
    });

    // Links elsewhere on the page that switch tab ("Go to missions", "Read notes", #mission-12)
    root.addEventListener('click', (e) => {
        const link = e.target.closest('[data-tab-link]');
        if (!link || !link.dataset.tabLink) return;
        const target = link.getAttribute('href') || '';
        select(link.dataset.tabLink);
        if (target.startsWith('#') && target.length > 1 && !target.startsWith('#tab-')) {
            e.preventDefault();
            document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (target.startsWith('#tab-')) {
            e.preventDefault();
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Initial tab
    const params = new URLSearchParams(window.location.search);
    const hash = window.location.hash.replace('#', '');
    const fromHash = names.includes(hash) ? hash : (hash.startsWith('mission-') ? 'missions' : null);
    const initial = params.get('tab') || fromHash || store.get(storeKey) || 'overview';
    select(names.includes(initial) ? initial : 'overview', { remember: !!(params.get('tab') || fromHash) });
    if (hash.startsWith('mission-')) document.getElementById(hash)?.scrollIntoView({ block: 'start' });

    // ── Study notes ──
    const sections = Array.from(root.querySelectorAll('.note-section'));
    const toggleAll = root.querySelector('[data-notes-toggle]');
    const jump = document.getElementById('notesJump');
    const milestone = root.querySelector('[data-notes-milestone]');
    const progressLabel = root.querySelector('[data-notes-progress]');

    let opened = new Set();
    try { opened = new Set(JSON.parse(store.get(notesKey + '-sections') || '[]')); } catch (e) { opened = new Set(); }

    function renderNotesProgress() {
        const total = sections.length;
        const read = total > 0 && (store.get(notesKey) === '1' || sections.every((s) => opened.has(s.id)));
        if (read) store.set(notesKey, '1');
        if (milestone) {
            milestone.classList.toggle('is-done', read);
            const icon = milestone.querySelector('i');
            if (icon) icon.className = read ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
        }
        if (progressLabel) {
            const seen = sections.filter((s) => opened.has(s.id)).length;
            progressLabel.textContent = total ? (read ? '(read)' : `(${seen}/${total} sections)`) : '';
        }
        const cta = root.querySelector('[data-notes-cta]');
        if (cta) cta.hidden = read;
    }

    function markOpened(section) {
        if (!section.open || opened.has(section.id)) return;
        opened.add(section.id);
        store.set(notesKey + '-sections', JSON.stringify(Array.from(opened)));
        renderNotesProgress();
    }

    function syncToggle() {
        if (!toggleAll) return;
        const allOpen = sections.length > 0 && sections.every((s) => s.open);
        toggleAll.setAttribute('aria-pressed', allOpen ? 'true' : 'false');
        toggleAll.querySelector('span').textContent = allOpen ? 'Collapse all' : 'Expand all';
        const icon = toggleAll.querySelector('i');
        if (icon) icon.className = allOpen ? 'fa-solid fa-down-left-and-up-right-to-center' : 'fa-solid fa-up-right-and-down-left-from-center';
    }

    function openSection(id) {
        const section = document.getElementById(id);
        if (!section) return;
        section.open = true;
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        section.querySelector('summary')?.focus({ preventScroll: true });
    }

    sections.forEach((s) => {
        s.addEventListener('toggle', () => { markOpened(s); syncToggle(); });
        markOpened(s); // the first section starts open
    });

    root.querySelectorAll('[data-note-link]').forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            openSection(a.dataset.noteLink);
            if (jump) jump.value = a.dataset.noteLink;
        });
    });
    if (jump) jump.addEventListener('change', () => openSection(jump.value));

    if (toggleAll) {
        toggleAll.addEventListener('click', () => {
            const open = !sections.every((s) => s.open);
            sections.forEach((s) => { s.open = open; });
            syncToggle();
        });
    }

    renderNotesProgress();
    syncToggle();
})();
