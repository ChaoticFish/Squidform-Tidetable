/**
 * Tide Table: category buttons, and the Moon calendar's months.
 * Everything works without this script: every event shows, and the
 * calendar shows every month one after another.
 */
(() => {
  'use strict';

  const fill = (tpl, ...vals) => {
    let i = 0;
    return String(tpl).replace(/%(s|d)/g, () => String(vals[i++] ?? ''));
  };

  function calendar(root) {
    const months = [...root.querySelectorAll('[data-tt-month]')];
    const nav = root.querySelector('[data-tt-nav]');
    if (!months.length || !nav) return () => {};
    let at = 0;
    const prev = nav.querySelector('[data-tt-prev]');
    const next = nav.querySelector('[data-tt-next]');
    const show = (i, focus) => {
      at = Math.max(0, Math.min(months.length - 1, i));
      months.forEach((m, k) => { m.hidden = k !== at; });
      prev.disabled = at === 0;
      next.disabled = at === months.length - 1;
      if (focus) months[at].querySelector('.tt-month-title')?.focus();
    };
    if (months.length > 1) {
      nav.hidden = false;
      prev.addEventListener('click', () => show(at - 1, true));
      next.addEventListener('click', () => show(at + 1, true));
    }
    show(0, false);

    root.addEventListener('click', (ev) => {
      const a = ev.target.closest('.tt-daylink');
      if (!a) return;
      const target = root.querySelector(a.getAttribute('href'));
      if (!target) return;
      ev.preventDefault();
      root.querySelectorAll('.tt-dayblock.tt-on').forEach((d) => d.classList.remove('tt-on'));
      target.classList.add('tt-on');
      target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest' });
      target.focus({ preventScroll: true });
    });

    // After filtering: days with nothing left lose their link and moons.
    return (cat) => {
      root.querySelectorAll('.tt-daylink').forEach((a) => {
        const cats = (a.dataset.ttCats || '').split(' ');
        const n = cat === '' ? cats.length : cats.filter((c) => c === cat).length;
        const plain = a.parentElement.querySelector('.tt-plain');
        a.hidden = n === 0;
        if (plain) plain.hidden = n !== 0;
        a.querySelectorAll('.tt-moondot').forEach((d) => { d.hidden = cat !== '' && d.dataset.ttCat !== cat; });
        if (n) a.setAttribute('aria-label', fill(n === 1 ? root.dataset.ttDayOne : root.dataset.ttDayMany, a.dataset.ttDay, n));
      });
    };
  }

  function setup(root) {
    const afterFilter = root.hasAttribute('data-tt-calendar') ? calendar(root) : () => {};
    const bar = root.querySelector('[data-tt-filters]');
    if (!bar) return;
    const chips = [...bar.querySelectorAll('[data-tt-filter]')];
    const items = [...root.querySelectorAll('[data-tt-cat]')].filter((el) => !el.classList.contains('tt-moondot'));
    const groups = [...root.querySelectorAll('[data-tt-group]')];
    const status = root.querySelector('[data-tt-status]');
    let empty = null;
    bar.hidden = false;

    const apply = (cat, announce) => {
      let shown = 0;
      items.forEach((el) => {
        const on = cat === '' || el.dataset.ttCat === cat;
        el.hidden = !on;
        if (on) shown++;
      });
      groups.forEach((g) => { g.hidden = ![...g.querySelectorAll('[data-tt-cat]')].some((el) => !el.hidden && !el.classList.contains('tt-moondot')); });
      chips.forEach((c) => {
        const on = c.dataset.ttFilter === cat;
        c.setAttribute('aria-pressed', on ? 'true' : 'false');
        c.classList.toggle('active', on);
      });
      if (!shown) {
        if (!empty) {
          empty = document.createElement('p');
          empty.className = 'tt-none';
          empty.textContent = root.dataset.ttNone || '';
          bar.after(empty);
        }
        empty.hidden = false;
      } else if (empty) {
        empty.hidden = true;
      }
      afterFilter(cat);
      if (announce && status) status.textContent = shown === 1 ? root.dataset.ttOne : fill(root.dataset.ttShowing, shown);
    };

    chips.forEach((c) => c.addEventListener('click', () => apply(c.dataset.ttFilter, true)));
  }

  const start = () => document.querySelectorAll('[data-tt-root]').forEach(setup);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
