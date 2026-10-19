/* ==========================================================================
   Arrow Systems — Exhibitions page behavior (vanilla JS, no dependencies)
   - Upcoming show countdown on the ticket stub ("11 days to go" / "Tomorrow" / "On now"; hidden after the show)
   - Year filter for the past exhibitions timeline (counts, ?year= in the URL, live status)
   Header, menus, search and newsletter are handled by the shared ../js/home.js.
   Without JS the countdown and filter stay hidden and the full timeline is listed.
   ========================================================================== */
(function () {
  'use strict';

  var DAY = 864e5;

  /* ---------- Upcoming show countdown ---------- */
  var show = document.querySelector('[data-show]');
  var countdown = show && show.querySelector('[data-countdown]');
  if (countdown) {
    var parseDay = function (iso) { // local midnight, so the count flips at the visitor's midnight
      var p = iso.split('-');
      return new Date(+p[0], +p[1] - 1, +p[2]).getTime();
    };
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    var start = parseDay(show.getAttribute('data-start'));
    var end = parseDay(show.getAttribute('data-end'));
    var days = Math.round((start - today) / DAY);
    var num = countdown.querySelector('[data-countdown-num]');
    var label = countdown.querySelector('[data-countdown-label]');

    if (days > 1) { num.textContent = days; label.textContent = 'Days to go'; countdown.hidden = false; }
    else if (days === 1) { num.textContent = 'Tomorrow'; label.textContent = 'Doors open'; countdown.hidden = false; }
    else if (today <= end) { num.textContent = 'On now'; label.textContent = 'Visit us at the show'; countdown.hidden = false; }
    // After the end date the countdown stays hidden (TODO(client): replace the ticket with the next show).
  }

  /* ---------- Year filter ---------- */
  var filter = document.querySelector('[data-year-filter]');
  var timeline = document.querySelector('[data-timeline]');
  if (!filter || !timeline) return;

  var groups = Array.prototype.slice.call(timeline.querySelectorAll('[data-group]'));
  var buttons = Array.prototype.slice.call(filter.querySelectorAll('[data-filter]'));
  var status = filter.querySelector('[data-filter-status]');

  function countOf(group) { return group.querySelectorAll('.event-card').length; }
  function groupOf(key) {
    for (var i = 0; i < groups.length; i++) if (groups[i].getAttribute('data-group') === key) return groups[i];
    return null;
  }
  function labelOf(btn) { return btn.firstChild.textContent.trim(); }

  var total = groups.reduce(function (sum, g) { return sum + countOf(g); }, 0);

  // Show how many exhibitions each button holds; drop any button without a matching group.
  buttons = buttons.filter(function (btn) {
    var key = btn.getAttribute('data-filter');
    var count = key === 'all' ? total : (groupOf(key) ? countOf(groupOf(key)) : 0);
    if (!count) { btn.remove(); return false; }
    var span = document.createElement('span');
    span.className = 'year-filter__count';
    span.textContent = count;
    btn.appendChild(span);
    btn.setAttribute('aria-label', labelOf(btn) + ', ' + count + (count === 1 ? ' exhibition' : ' exhibitions'));
    return true;
  });

  function apply(key, animate) {
    var shown = 0;
    groups.forEach(function (group) {
      var show = key === 'all' || group.getAttribute('data-group') === key;
      var wasHidden = group.hidden;
      group.hidden = !show;
      group.classList.remove('is-entering');
      if (show) {
        shown += countOf(group);
        if (animate && wasHidden) {
          void group.offsetWidth; // restart the fade for groups that were hidden
          group.classList.add('is-entering');
        }
      }
    });

    var active = null;
    buttons.forEach(function (btn) {
      var on = btn.getAttribute('data-filter') === key;
      btn.setAttribute('aria-pressed', String(on));
      if (on) active = btn;
    });

    var noun = shown === 1 ? 'exhibition' : 'exhibitions';
    status.textContent = key === 'all'
      ? 'Showing all ' + shown + ' ' + noun + '.'
      : 'Showing ' + shown + ' ' + noun + ' from ' + labelOf(active) + '.';
  }

  function keyFromUrl() {
    var match = /[?&]year=([^&#]+)/.exec(window.location.search);
    var key = match ? decodeURIComponent(match[1]) : 'all';
    return buttons.some(function (b) { return b.getAttribute('data-filter') === key; }) ? key : 'all';
  }

  function saveToUrl(key) {
    if (!window.history || !window.history.replaceState) return;
    var url = window.location.pathname + (key === 'all' ? '' : '?year=' + encodeURIComponent(key)) + window.location.hash;
    try { window.history.replaceState(null, '', url); } catch (e) { /* file:// in some browsers */ }
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.getAttribute('aria-pressed') === 'true') return;
      var key = btn.getAttribute('data-filter');
      apply(key, true);
      saveToUrl(key);
      // If the filter row has scrolled away, bring the start of the results back into view.
      var top = filter.getBoundingClientRect().top;
      if (top < 0) filter.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });
  });

  filter.hidden = false;
  apply(keyFromUrl(), false);
})();
