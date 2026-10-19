/* ==========================================================================
   Arrow Systems — Upcoming Exhibitions page behavior (vanilla JS, no dependencies)
   - Marks each show as past / on now / next / later from the visitor's date
     (badge, countdown on the next show, "Schedule a Meeting" only for shows still to come)
   - "At a glance" month grid: dot states, current month ("Today" + progress through the month),
     and month links that jump to that month's first show in the schedule
   - Hero "Next show" stat follows the next show (hidden when none is left)
   Header, menus, search and newsletter are handled by the shared ../js/home.js.
   Without JS every show is listed as on the live page.
   ========================================================================== */
(function () {
  'use strict';

  var DAY = 864e5;
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function parseDay(iso) { // local midnight, so states flip at the visitor's midnight
    var p = iso.split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]);
  }
  var now = new Date();
  var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  /* ---------- Show states ---------- */
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-show]'));
  var next = null;

  rows.forEach(function (row) {
    var start = parseDay(row.getAttribute('data-start'));
    var end = parseDay(row.getAttribute('data-end'));
    var state = end < today ? 'past' : (start <= today ? 'now' : 'later');
    if (state !== 'past' && !next) { next = row; if (state === 'later') state = 'next'; }
    row._state = state;
    row._start = start;
  });

  rows.forEach(function (row) {
    var state = row._state;
    var badge = row.querySelector('[data-status]');
    var countdown = row.querySelector('[data-countdown]');
    var upcomingCta = row.querySelector('[data-cta-upcoming]');
    var pastCta = row.querySelector('[data-cta-past]');

    row.classList.add('is-' + state);
    if (state === 'now') row.classList.add('is-next');

    badge.textContent = { past: 'Took place', now: 'On now', next: 'Next show', later: 'Upcoming' }[state];
    badge.classList.toggle('badge--accent', state === 'now' || state === 'next');
    badge.hidden = false;

    if (state === 'past') {
      upcomingCta.hidden = true;
      pastCta.hidden = false;
    }

    if (countdown && (state === 'next' || state === 'now')) {
      var days = Math.round((row._start - today) / DAY);
      var num = countdown.querySelector('[data-countdown-num]');
      var label = countdown.querySelector('[data-countdown-label]');
      if (state === 'now') { num.textContent = 'On now'; label.textContent = ''; }
      else if (days === 1) { num.textContent = 'Tomorrow'; label.textContent = ''; }
      else { num.textContent = days; label.textContent = 'days to go'; }
      countdown.hidden = false;
    }

    var dot = document.querySelector('[data-dot="' + row.id.replace('show-', '') + '"]');
    if (dot) dot.classList.add(state === 'past' ? 'is-past' : (state === 'later' ? 'is-later' : 'is-next'));
  });

  /* ---------- Upcoming first, then past (most recent first) ---------- */
  var upcomingList = document.querySelector('[data-upcoming-list]');
  var pastGroup = document.querySelector('[data-past-group]');
  var pastList = document.querySelector('[data-past-list]');
  if (upcomingList && pastGroup && pastList) {
    var past = rows.filter(function (r) { return r._state === 'past'; });
    var upcoming = rows.filter(function (r) { return r._state !== 'past'; });
    past.sort(function (a, b) { return b._start - a._start; }).forEach(function (r) { pastList.appendChild(r); });
    pastGroup.hidden = !past.length;
    document.querySelector('[data-upcoming-title]').hidden = false;
    document.querySelector('[data-upcoming-empty]').hidden = upcoming.length > 0;
  }

  /* ---------- Hero "Next show" stat ---------- */
  var stat = document.querySelector('[data-next-stat]');
  if (stat) {
    if (next) {
      stat.querySelector('[data-next-date]').textContent = next._state === 'now'
        ? 'On now'
        : MONTHS[next._start.getMonth()] + ' ' + next._start.getDate();
      stat.querySelector('[data-next-name]').textContent = next.querySelector('.show-row__title').textContent;
    } else {
      stat.hidden = true; // TODO(client): add the next year's shows
    }
  }

  /* ---------- At a glance: current month ---------- */
  var cal = document.querySelector('[data-cal]');
  if (cal && rows.length) {
    var year = rows[0]._start.getFullYear();
    Array.prototype.slice.call(cal.querySelectorAll('[data-month]')).forEach(function (cell) {
      var m = +cell.getAttribute('data-month') - 1;
      var monthStart = new Date(year, m, 1);
      var monthEnd = new Date(year, m + 1, 1);
      if (monthEnd <= today) cell.classList.add('is-before');
      if (monthStart <= today && today < monthEnd) {
        cell.classList.add('is-current');
        var tag = document.createElement('span');
        tag.className = 'cal-month__today';
        tag.setAttribute('aria-hidden', 'true');
        tag.textContent = 'Today';
        var bar = document.createElement('span');
        bar.className = 'cal-month__progress';
        bar.setAttribute('aria-hidden', 'true');
        var daysInMonth = (monthEnd - monthStart) / DAY;
        bar.style.setProperty('--progress', Math.round(today.getDate() / daysInMonth * 100) + '%');
        cell.appendChild(tag);
        cell.appendChild(bar);
      }
    });
  }

  /* ---------- Month links: jump to that month's first show ---------- */
  // Shows were reordered above (upcoming first, then past), so point each month link at the first row
  // of that month in the page as it now reads. A normal anchor jump keeps the :target highlight.
  Array.prototype.slice.call(document.querySelectorAll('[data-month-link]')).forEach(function (link) {
    var month = +link.getAttribute('data-month-link');
    var ordered = Array.prototype.slice.call(document.querySelectorAll('[data-show]'));
    for (var i = 0; i < ordered.length; i++) {
      if (ordered[i]._start && ordered[i]._start.getMonth() + 1 === month) {
        link.setAttribute('href', '#' + ordered[i].id);
        break;
      }
    }
  });
})();
