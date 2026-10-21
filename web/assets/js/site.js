/* ==========================================================================
   Arrow Systems — Homepage behavior (vanilla JS, no dependencies)
   - Desktop mega menus / dropdowns (click, hover intent, Escape, click-outside)
   - Products category level (desktop: one panel at a time; mobile: accordions)
   - Mobile drawer with focus containment and Escape to close
   - Search panel, compact sticky header
   - Hero slider (autoplay with pause, dots, prev/next, reduced-motion aware)
   - Industries carousel (infinite scroll-snap row with prev/next arrows and industry chips)
   - Click-to-play YouTube facades
   - Worldwide locations map (pins, clusters, tooltips)
   - One Stop Solution background video (muted YouTube, autoplay + replay, no visible controls, poster fallback)
   - Newsletter validation. NOT connected to any service yet (see TODO below)
   ========================================================================== */
(function () {
  'use strict';

  var root = document.documentElement;
  root.classList.remove('no-js');
  root.classList.add('js');

  var desktopMQ = window.matchMedia('(min-width: 75em)');
  var hoverMQ = window.matchMedia('(hover: hover) and (pointer: fine)');
  var isDesktop = function () { return desktopMQ.matches; };

  var header = document.querySelector('[data-header]');
  var nav = document.querySelector('[data-site-nav]');
  var navToggle = document.querySelector('[data-nav-toggle]');
  var navToggleLabel = document.querySelector('[data-nav-toggle-label]');
  var searchToggle = document.querySelector('[data-search-toggle]');
  var searchPanel = document.getElementById('site-search');
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-nav-item]'));

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

  function isVisible(el) {
    return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
  }

  /* ---------- Top-level menu items ---------- */
  var openItem = null;
  var hoverOpenedAt = 0;
  var closeTimer = null;

  function triggerOf(item) { return item.querySelector('[data-nav-trigger]'); }

  function setItem(item, open) {
    triggerOf(item).setAttribute('aria-expanded', String(open));
    item.classList.toggle('is-open', open);
    if (open && isDesktop()) ensureCategory(item);
  }

  function openNavItem(item) {
    if (openItem && openItem !== item) setItem(openItem, false);
    setItem(item, true);
    openItem = item;
    closeSearch();
  }

  function closeNavItem(item, returnFocus) {
    item = item || openItem;
    if (!item) return;
    setItem(item, false);
    if (openItem === item) openItem = null;
    if (returnFocus) triggerOf(item).focus();
  }

  items.forEach(function (item) {
    var trigger = triggerOf(item);

    trigger.addEventListener('click', function () {
      var expanded = trigger.getAttribute('aria-expanded') === 'true';
      // A hover just opened this panel; a click right after it should not close it again.
      if (expanded && isDesktop() && Date.now() - hoverOpenedAt < 500) return;
      if (expanded) closeNavItem(item); else openNavItem(item);
    });

    // Hover intent (desktop with a fine pointer only)
    item.addEventListener('mouseenter', function () {
      if (!isDesktop() || !hoverMQ.matches) return;
      clearTimeout(closeTimer);
      if (openItem !== item) { openNavItem(item); hoverOpenedAt = Date.now(); }
    });
    item.addEventListener('mouseleave', function () {
      if (!isDesktop() || !hoverMQ.matches) return;
      clearTimeout(closeTimer);
      closeTimer = setTimeout(function () { if (openItem === item) closeNavItem(item); }, 220);
    });

    // Close a desktop panel once keyboard focus leaves it
    item.addEventListener('focusout', function (e) {
      if (!isDesktop()) return;
      if (e.relatedTarget && !item.contains(e.relatedTarget) && openItem === item) closeNavItem(item);
    });
  });

  /* ---------- Products category level ---------- */
  function catPanel(btn) { return document.getElementById(btn.getAttribute('aria-controls')); }
  function setCat(btn, open) {
    btn.setAttribute('aria-expanded', String(open));
    btn.closest('[data-cat]').classList.toggle('is-active', open);
  }
  function catSiblings(btn) {
    return Array.prototype.slice.call(btn.closest('[data-cat-group]').querySelectorAll('[data-cat-trigger]'));
  }
  function activateCat(btn) {
    catSiblings(btn).forEach(function (b) { setCat(b, b === btn); });
  }
  function ensureCategory(item) {
    var group = item.querySelector('[data-cat-group]');
    if (!group) return;
    var btns = Array.prototype.slice.call(group.querySelectorAll('[data-cat-trigger]'));
    var anyOpen = btns.some(function (b) { return b.getAttribute('aria-expanded') === 'true'; });
    if (!anyOpen && btns.length) setCat(btns[0], true);
  }

  Array.prototype.slice.call(document.querySelectorAll('[data-cat-trigger]')).forEach(function (btn) {
    if (!catPanel(btn)) return;
    btn.addEventListener('click', function () {
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      if (isDesktop()) { activateCat(btn); return; } // desktop always shows one category
      catSiblings(btn).forEach(function (b) { setCat(b, b === btn ? !expanded : false); });
    });
    btn.addEventListener('mouseenter', function () {
      if (isDesktop() && hoverMQ.matches) activateCat(btn);
    });
  });

  /* ---------- Mobile drawer ---------- */
  function drawerOpen() { return nav.classList.contains('is-open'); }

  function updateDrawerTop() {
    var bottom = header.getBoundingClientRect().bottom;
    nav.style.setProperty('--drawer-top', Math.max(0, Math.round(bottom)) + 'px');
  }

  function setDrawer(open) {
    navToggle.setAttribute('aria-expanded', String(open));
    navToggleLabel.textContent = open ? 'Close menu' : 'Open menu';
    nav.classList.toggle('is-open', open);
    document.body.classList.toggle('nav-open', open);
    if (open) {
      closeSearch();
      updateDrawerTop();
      var first = nav.querySelector(FOCUSABLE);
      if (first) first.focus();
    } else if (openItem) {
      closeNavItem();
    }
  }

  navToggle.addEventListener('click', function () { setDrawer(!drawerOpen()); });

  // Keep Tab focus inside the header (logo, actions and drawer) while the drawer is open.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || !drawerOpen() || isDesktop()) return;
    var focusables = Array.prototype.slice.call(header.querySelectorAll(FOCUSABLE)).filter(isVisible);
    if (!focusables.length) return;
    var first = focusables[0];
    var last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  /* ---------- Search ---------- */
  function closeSearch(returnFocus) {
    if (searchPanel.hidden) return;
    searchPanel.hidden = true;
    searchToggle.setAttribute('aria-expanded', 'false');
    if (returnFocus) searchToggle.focus();
  }
  function openSearch() {
    if (drawerOpen()) setDrawer(false);
    if (openItem) closeNavItem();
    searchPanel.hidden = false;
    searchToggle.setAttribute('aria-expanded', 'true');
    var input = searchPanel.querySelector('input[type="search"]');
    if (input) input.focus();
  }
  searchToggle.addEventListener('click', function () {
    if (searchPanel.hidden) openSearch(); else closeSearch(true);
  });
  searchPanel.querySelector('[data-search-close]').addEventListener('click', function () { closeSearch(true); });

  /* ---------- Global Escape / click outside ---------- */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' && e.key !== 'Esc') return;
    if (!searchPanel.hidden) { closeSearch(true); return; }
    if (openItem) {
      closeNavItem(openItem, true);
      return;
    }
    if (drawerOpen()) { setDrawer(false); navToggle.focus(); }
  });

  document.addEventListener('click', function (e) {
    if (isDesktop() && openItem && !openItem.contains(e.target)) closeNavItem();
    if (!searchPanel.hidden && !searchPanel.contains(e.target) && !searchToggle.contains(e.target)) closeSearch();
  });

  // Reset menus when crossing the desktop breakpoint
  function onBreakpointChange() {
    if (drawerOpen()) setDrawer(false);
    if (openItem) closeNavItem();
    document.querySelectorAll('[data-cat-trigger]').forEach(function (b) { setCat(b, false); });
  }
  if (desktopMQ.addEventListener) desktopMQ.addEventListener('change', onBreakpointChange);
  else if (desktopMQ.addListener) desktopMQ.addListener(onBreakpointChange);

  /* ---------- Compact sticky header (with hysteresis to avoid flicker) ---------- */
  var compact = false;
  var ticking = false;
  function onScroll() {
    var y = window.scrollY || window.pageYOffset;
    if (!compact && y > 140) { compact = true; header.classList.add('is-compact'); }
    else if (compact && y < 40) { compact = false; header.classList.remove('is-compact'); }
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
  }, { passive: true });
  window.addEventListener('resize', function () { if (drawerOpen()) updateDrawerTop(); });
  onScroll();

  /* ---------- Links without a confirmed destination ---------- */
  // Links marked data-todo have href="#". Stop them jumping to the top of the page.
  document.addEventListener('click', function (e) {
    var link = e.target.closest ? e.target.closest('a[data-todo]') : null;
    if (link && link.getAttribute('href') === '#') {
      e.preventDefault();
      if (window.console) console.info('[Arrow Systems] Destination not confirmed yet:', link.getAttribute('data-todo'));
    }
  });

  /* ---------- Hero slider ---------- */
  var carousel = document.querySelector('[data-carousel]');
  if (carousel) {
    var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-slide]'));
    var dots = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-dot]'));
    var pauseBtn = carousel.querySelector('[data-carousel-pause]');
    var pauseLabel = carousel.querySelector('[data-carousel-pause-label]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var current = 0;
    var timer = null;
    var userPaused = reduceMotion; // no autoplay when the visitor prefers reduced motion
    var hoverPaused = false;
    var INTERVAL = 7000;

    var showSlide = function (index) {
      current = (index + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        var active = i === current;
        // Video slides keep playing (muted) while hidden: resuming a YouTube video makes it show its pause button again.
        if (active && !slide.classList.contains('is-active')) videoCommand(slide, 'playVideo');
        slide.classList.toggle('is-active', active);
        slide.toggleAttribute('inert', !active);
        slide.setAttribute('aria-hidden', String(!active));
      });
      dots.forEach(function (dot, i) {
        if (i === current) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
      });
    };
    // Video slides (CMS: a YouTube link OR an uploaded video file per slide). Muted, autoplaying, shown as soon as it plays.
    //  - data-hero-video-src="file.mp4": native <video>, plays instantly with no third-party player.
    //  - data-hero-video="YouTube URL or ID": privacy-enhanced YouTube embed with no controls. Not loop/playlist mode (that
    //    adds previous/next buttons and an end screen): it is replayed by seeking to 0 just before the end, the same way as
    //    the One Stop Solution background video below. YouTube may still flash its own play icon for a moment as it starts.
    // Videos keep playing while their slide is hidden (no pause/resume, so YouTube does not show its pause button again on
    // return; an uploaded file simply loops). Reduced motion: no video, the poster stays.
    var YT_ORIGIN = 'https://www.youtube-nocookie.com';
    var youtubeId = function (value) { // accepts a bare ID or any common YouTube URL form
      var m = String(value || '').match(/(?:youtu\.be\/|[?&]v=|\/embed\/|\/shorts\/|\/live\/)([\w-]{11})/);
      return m ? m[1] : (/^[\w-]{11}$/.test(value) ? value : '');
    };
    var heroVideos = reduceMotion ? [] : Array.prototype.slice.call(carousel.querySelectorAll('[data-hero-video], [data-hero-video-src]'));
    var ytCommand = function (box, func, args) {
      var frame = box.querySelector('iframe');
      if (frame && frame.contentWindow) frame.contentWindow.postMessage(JSON.stringify({ event: 'command', func: func, args: args || [] }), YT_ORIGIN);
    };
    var videoCommand = function (slide, func) { // func: 'playVideo' | 'pauseVideo'
      var box = slide.querySelector('[data-hero-video], [data-hero-video-src]');
      if (!box) return;
      var file = box.querySelector('video');
      if (file) { if (func === 'playVideo') { var p = file.play(); if (p && p.catch) p.catch(function () {}); } else file.pause(); }
      else ytCommand(box, func);
    };
    var replayVideo = function (box) {
      if (box.restarting) return;
      box.restarting = true;
      ytCommand(box, 'seekTo', [0, true]);
      ytCommand(box, 'playVideo');
      setTimeout(function () { box.restarting = false; }, 1500);
    };
    heroVideos.forEach(function (box) {
      var title = box.getAttribute('data-hero-video-title') || 'Video';
      var src = box.getAttribute('data-hero-video-src');
      if (src) {
        var video = document.createElement('video');
        video.src = src; video.muted = true; video.loop = true; video.autoplay = true; video.playsInline = true;
        video.setAttribute('muted', ''); video.setAttribute('playsinline', ''); video.setAttribute('aria-hidden', 'true');
        video.preload = 'auto'; video.title = title;
        video.addEventListener('playing', function () { box.classList.add('is-ready'); });
        box.appendChild(video);
        return;
      }
      var id = youtubeId(box.getAttribute('data-hero-video'));
      if (!id) return;
      var params = 'autoplay=1&mute=1&controls=0&playsinline=1&rel=0&disablekb=1&fs=0&iv_load_policy=3&enablejsapi=1';
      if (/^https?:$/.test(window.location.protocol)) params += '&origin=' + encodeURIComponent(window.location.origin);
      var frame = document.createElement('iframe');
      frame.src = YT_ORIGIN + '/embed/' + id + '?' + params;
      frame.title = title;
      frame.tabIndex = -1;
      frame.allow = 'autoplay; encrypted-media';
      frame.addEventListener('load', function () { // ask the player to report its state
        frame.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: id }), YT_ORIGIN);
      });
      box.appendChild(frame);
    });
    if (heroVideos.length) {
      window.addEventListener('message', function (e) {
        if (e.origin !== YT_ORIGIN) return;
        var data;
        try { data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch (err) { return; }
        if (!data || !data.event) return;
        heroVideos.forEach(function (box) {
          var frame = box.querySelector('iframe');
          if (!frame || frame.contentWindow !== e.source) return;
          var info = data.event === 'infoDelivery' && data.info ? data.info : null;
          if (info && typeof info.duration === 'number' && info.duration > 0) box.duration = info.duration;
          // Replay just before the end so YouTube's end screen never appears.
          if (info && typeof info.currentTime === 'number' && box.duration && info.currentTime >= box.duration - 0.6) replayVideo(box);
          var state = data.event === 'onStateChange' ? data.info : (info && info.playerState);
          if (state === 1) box.classList.add('is-ready');      // playing: show it straight away (it then stays shown)
          else if (state === 0) replayVideo(box);              // ended (fallback): start again
        });
      });
    }
    var stop = function () { clearTimeout(timer); timer = null; };
    var start = function () { // a slide can ask for more time with data-interval (ms), e.g. a video slide
      stop();
      if (!userPaused && !hoverPaused) {
        timer = setTimeout(function () { showSlide(current + 1); start(); }, Number(slides[current].getAttribute('data-interval')) || INTERVAL);
      }
    };
    var setUserPaused = function (paused) {
      userPaused = paused;
      pauseBtn.setAttribute('aria-pressed', String(paused));
      pauseLabel.textContent = paused ? 'Play slideshow' : 'Pause slideshow';
      start();
    };

    carousel.querySelector('[data-carousel-prev]').addEventListener('click', function () { showSlide(current - 1); start(); });
    carousel.querySelector('[data-carousel-next]').addEventListener('click', function () { showSlide(current + 1); start(); });
    dots.forEach(function (dot, i) { dot.addEventListener('click', function () { showSlide(i); start(); }); });
    pauseBtn.addEventListener('click', function () { setUserPaused(!userPaused); });

    // Pause while the pointer or keyboard focus is inside the hero.
    carousel.addEventListener('mouseenter', function () { hoverPaused = true; stop(); });
    carousel.addEventListener('mouseleave', function () { hoverPaused = false; start(); });
    carousel.addEventListener('focusin', function () { hoverPaused = true; stop(); });
    carousel.addEventListener('focusout', function (e) {
      if (!carousel.contains(e.relatedTarget)) { hoverPaused = false; start(); }
    });
    carousel.addEventListener('keydown', function (e) {
      if (!e.target.closest('[data-carousel-controls]')) return;
      if (e.key === 'ArrowLeft') { showSlide(current - 1); e.preventDefault(); }
      if (e.key === 'ArrowRight') { showSlide(current + 1); e.preventDefault(); }
    });
    document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });

    carousel.querySelector('[data-carousel-controls]').hidden = false;
    // Reserve the controls' real width beside the caption (the number of slides, and so of dots, comes from the CMS).
    var controls = carousel.querySelector('[data-carousel-controls]');
    var sizeControls = function () {
      var w = 0;
      Array.prototype.forEach.call(controls.children, function (el) { w += el.getBoundingClientRect().width; });
      if (w) carousel.style.setProperty('--hero-controls-w', Math.ceil(w + 6 * (controls.children.length - 1) + 30) + 'px');
    };
    sizeControls();
    window.addEventListener('resize', sizeControls);
    showSlide(0);
    setUserPaused(userPaused);
  }

  /* ---------- Industries carousel (infinite scroll-snap row + arrows and chips) ---------- */
  // The real cards are cloned once before and once after, so the row can always scroll either way.
  // When scrolling settles on a clone, the row jumps (without animation) to the same card in the real set.
  var indTrack = document.querySelector('[data-ind-track]');
  if (indTrack) {
    var indReal = Array.prototype.slice.call(indTrack.querySelectorAll('[data-ind-slide]'));
    var indCount = indReal.length;
    var indChips = Array.prototype.slice.call(document.querySelectorAll('[data-ind-chip]'));
    var indPrev = document.querySelector('[data-ind-prev]');
    var indNext = document.querySelector('[data-ind-next]');

    var makeClone = function (slide) {
      var clone = slide.cloneNode(true);
      clone.removeAttribute('id');
      clone.removeAttribute('data-ind-slide');
      clone.setAttribute('aria-hidden', 'true');
      Array.prototype.forEach.call(clone.querySelectorAll('a'), function (a) { a.tabIndex = -1; });
      return clone;
    };
    indReal.slice().reverse().forEach(function (slide) { indTrack.insertBefore(makeClone(slide), indTrack.firstChild); });
    indReal.forEach(function (slide) { indTrack.appendChild(makeClone(slide)); });
    var indAll = Array.prototype.slice.call(indTrack.children); // [clones][real][clones]

    var slideOffset = function (i) { return indAll[i].offsetLeft - indAll[0].offsetLeft; };
    var setWidth = function () { return slideOffset(indCount); };
    var nearestSlide = function () {
      var x = indTrack.scrollLeft;
      var nearest = 0;
      for (var i = 1; i < indAll.length; i++) {
        if (Math.abs(slideOffset(i) - x) < Math.abs(slideOffset(nearest) - x)) nearest = i;
      }
      return nearest;
    };
    var jumpTo = function (x) {
      indTrack.style.scrollBehavior = 'auto';
      indTrack.scrollLeft = x;
      void indTrack.offsetWidth;
      indTrack.style.scrollBehavior = '';
    };
    var goTo = function (index) {
      index = Math.max(0, Math.min(indAll.length - 1, index));
      indTrack.scrollTo({ left: slideOffset(index) });
    };
    var syncInd = function () {
      // Settled on a clone: move to the matching real card.
      var i = nearestSlide();
      if (i < indCount) jumpTo(indTrack.scrollLeft + setWidth());
      else if (i >= indCount * 2) jumpTo(indTrack.scrollLeft - setWidth());
      var active = nearestSlide() % indCount;
      indChips.forEach(function (chip, j) {
        if (j === active) chip.setAttribute('aria-current', 'true'); else chip.removeAttribute('aria-current');
      });
    };

    indPrev.addEventListener('click', function () { goTo(nearestSlide() - 1); });
    indNext.addEventListener('click', function () { goTo(nearestSlide() + 1); });
    indChips.forEach(function (chip, j) {
      chip.addEventListener('click', function () {
        // Use whichever copy of the card is closest, so the row takes the short way round.
        var from = nearestSlide();
        var best = j + indCount;
        [j, j + indCount, j + indCount * 2].forEach(function (k) { if (Math.abs(k - from) < Math.abs(best - from)) best = k; });
        if (best === from) syncInd(); else goTo(best);
      });
    });

    var indTick = null;
    indTrack.addEventListener('scroll', function () {
      clearTimeout(indTick);
      indTick = setTimeout(syncInd, 100);
    }, { passive: true });
    window.addEventListener('resize', function () {
      // Card widths change with the viewport: re-snap to the same card in the real set.
      jumpTo(slideOffset(indCount + nearestSlide() % indCount));
      syncInd();
    });

    document.querySelector('[data-ind-controls]').hidden = false;
    document.querySelector('[data-ind-chips]').hidden = false;
    jumpTo(slideOffset(indCount));
    syncInd();
  }

  /* ---------- Video facades ---------- */
  // Thumbnails load locally; YouTube (privacy-enhanced domain) loads only after a click.
  Array.prototype.slice.call(document.querySelectorAll('[data-video]')).forEach(function (box) {
    var btn = box.querySelector('[data-video-play]');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(box.getAttribute('data-video')) +
        '?autoplay=1&rel=0&playsinline=1';
      iframe.title = btn.textContent.replace(/\s+/g, ' ').trim().replace(/^Play video:?\s*/, '') || 'Video';
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
      iframe.allowFullscreen = true;
      box.innerHTML = '';
      box.appendChild(iframe);
      iframe.focus();
    });
  });

  /* ---------- Worldwide locations map ---------- */
  // Pins are built from the location list in the markup (data-x / data-y: percentages on the clean 1000:450 map).
  // Pins closer than one 44px touch target are merged into a cluster that lists its cities, so none is unreachable.
  var worldMap = document.querySelector('[data-world-map]');
  if (worldMap) {
    var HIT = 44;
    var locs = Array.prototype.slice.call(document.querySelectorAll('[data-loc]')).map(function (li) {
      return {
        li: li,
        x: parseFloat(li.getAttribute('data-x')),
        y: parseFloat(li.getAttribute('data-y')),
        label: li.querySelector('[data-loc-label]').textContent,
        hq: li.hasAttribute('data-hq')
      };
    });
    var legend = document.querySelector('[data-world-map-legend]');
    var tip = document.createElement('div');
    tip.className = 'map-tip';
    tip.hidden = true;
    tip.setAttribute('aria-hidden', 'true'); // visual only: each pin's accessible name carries the same text
    worldMap.appendChild(tip);

    var pins = [];
    var shownPin = null;   // pin whose tooltip is visible
    var pinnedPin = null;  // pin opened by click/tap (stays open until toggled, outside click or Escape)
    var signature = '';

    var clusterLocs = function () {
      var w = worldMap.clientWidth, h = worldMap.clientHeight;
      var parent = locs.map(function (_, i) { return i; });
      var find = function (i) { while (parent[i] !== i) { parent[i] = parent[parent[i]]; i = parent[i]; } return i; };
      for (var i = 0; i < locs.length; i++) {
        for (var j = i + 1; j < locs.length; j++) {
          var dx = (locs[i].x - locs[j].x) / 100 * w;
          var dy = (locs[i].y - locs[j].y) / 100 * h;
          if (Math.sqrt(dx * dx + dy * dy) < HIT) parent[find(i)] = find(j);
        }
      }
      var groups = {};
      locs.forEach(function (loc, i) { (groups[find(i)] = groups[find(i)] || []).push(loc); });
      return Object.keys(groups).map(function (k) { return groups[k]; });
    };

    var nameOf = function (loc) { return loc.label + (loc.hq ? ' (Headquarters)' : ''); };

    var fillTip = function (pin) {
      tip.textContent = '';
      if (pin.members.length === 1) {
        var loc = pin.members[0];
        tip.appendChild(document.createTextNode(loc.label));
        if (loc.hq) {
          var sub = document.createElement('span');
          sub.className = 'map-tip__sub';
          sub.textContent = 'Headquarters';
          tip.appendChild(sub);
        }
        return;
      }
      tip.appendChild(document.createTextNode(pin.members.length + ' nearby locations'));
      var ul = document.createElement('ul');
      pin.members.forEach(function (m) {
        var li = document.createElement('li');
        li.textContent = nameOf(m);
        ul.appendChild(li);
      });
      tip.appendChild(ul);
    };

    var placeTip = function (pin) {
      var w = worldMap.clientWidth, h = worldMap.clientHeight;
      var cx = pin.x / 100 * w, cy = pin.y / 100 * h;
      var gap = pin.members.length > 1 ? 18 : 12;
      var tw = tip.offsetWidth, th = tip.offsetHeight;
      var left = Math.max(4, Math.min(cx - tw / 2, w - tw - 4));
      var above = cy - gap - th >= 0; // otherwise below, over the legend/list rather than the previous section
      tip.style.left = Math.round(left) + 'px';
      tip.style.top = Math.round(above ? cy - gap - th : cy + gap) + 'px';
      tip.style.setProperty('--arrow-x', Math.round(Math.max(10, Math.min(cx - left, tw - 10))) + 'px');
      tip.setAttribute('data-place', above ? 'above' : 'below');
    };

    var highlight = function (pin, on) {
      pin.el.classList.toggle('is-active', on);
      pin.members.forEach(function (m) { m.li.classList.toggle('is-highlighted', on); });
    };

    var showTip = function (pin) {
      if (shownPin && shownPin !== pin) highlight(shownPin, false);
      shownPin = pin;
      fillTip(pin);
      tip.hidden = false;
      placeTip(pin);
      highlight(pin, true);
    };
    var hideTip = function () {
      if (shownPin) highlight(shownPin, false);
      shownPin = null;
      tip.hidden = true;
    };
    // After a hover/focus ends, fall back to the click-opened pin if there is one.
    var restoreTip = function () { if (pinnedPin) showTip(pinnedPin); else hideTip(); };
    var unpin = function () {
      if (pinnedPin) pinnedPin.el.setAttribute('aria-expanded', 'false');
      pinnedPin = null;
    };

    var buildPins = function () {
      var groups = clusterLocs();
      var sig = groups.map(function (g) { return g.map(function (m) { return m.label; }).join('|'); }).sort().join('/');
      if (sig === signature) { if (shownPin) placeTip(shownPin); return; }
      signature = sig;
      unpin();
      hideTip();
      pins.forEach(function (p) { p.el.remove(); });
      pins = groups.map(function (members, n) {
        var pin = {
          members: members,
          x: members.reduce(function (s, m) { return s + m.x; }, 0) / members.length,
          y: members.reduce(function (s, m) { return s + m.y; }, 0) / members.length
        };
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'map-pin' + (members.length > 1 ? ' map-pin--cluster' : '') +
          (members.length === 1 && members[0].hq ? ' map-pin--hq' : '');
        btn.style.left = pin.x + '%';
        btn.style.top = pin.y + '%';
        btn.style.setProperty('--pulse-delay', ((n * 0.7) % 2.8).toFixed(1) + 's');
        btn.setAttribute('aria-expanded', 'false');
        var dot = document.createElement('span');
        dot.className = 'map-pin__dot';
        dot.setAttribute('aria-hidden', 'true');
        if (members.length > 1) dot.textContent = members.length;
        var name = document.createElement('span');
        name.className = 'visually-hidden';
        name.textContent = members.length > 1
          ? members.length + ' nearby locations: ' + members.map(nameOf).join('; ')
          : nameOf(members[0]);
        btn.appendChild(dot);
        btn.appendChild(name);
        pin.el = btn;

        btn.addEventListener('mouseenter', function () { if (hoverMQ.matches) showTip(pin); });
        btn.addEventListener('mouseleave', function () { if (hoverMQ.matches && document.activeElement !== btn) restoreTip(); });
        btn.addEventListener('focus', function () { showTip(pin); });
        btn.addEventListener('blur', function () { restoreTip(); });
        btn.addEventListener('click', function () {
          if (pinnedPin === pin) { unpin(); hideTip(); return; }
          unpin();
          pinnedPin = pin;
          btn.setAttribute('aria-expanded', 'true');
          showTip(pin);
        });
        worldMap.appendChild(btn);
        return pin;
      });
      if (legend) {
        legend.hidden = false;
        var clusterKey = legend.querySelector('[data-legend-cluster]');
        if (clusterKey) clusterKey.hidden = !pins.some(function (p) { return p.members.length > 1; });
      }
    };

    var pinOf = function (loc) {
      for (var i = 0; i < pins.length; i++) if (pins[i].members.indexOf(loc) !== -1) return pins[i];
      return null;
    };
    // Pointing at a city in the list shows where it is on the map.
    locs.forEach(function (loc) {
      loc.li.addEventListener('mouseenter', function () { var p = pinOf(loc); if (p) showTip(p); });
      loc.li.addEventListener('mouseleave', restoreTip);
    });

    document.addEventListener('click', function (e) {
      if (pinnedPin && !e.target.closest('.map-pin')) { unpin(); hideTip(); }
    });
    document.addEventListener('keydown', function (e) {
      if ((e.key !== 'Escape' && e.key !== 'Esc') || tip.hidden) return;
      var focused = shownPin && document.activeElement === shownPin.el ? shownPin.el : null;
      unpin();
      hideTip();
      if (focused) focused.focus(); // keep focus where it was; the tooltip stays closed until the next focus/hover
    });

    // Phones: start with the full city list collapsed (the pins and clusters already list every city).
    var locList = document.querySelector('[data-locations-list]');
    if (locList && window.matchMedia('(max-width: 47.99em)').matches) locList.open = false;

    buildPins();
    if ('ResizeObserver' in window) new ResizeObserver(buildPins).observe(worldMap);
    else window.addEventListener('resize', buildPins);
  }

  /* ---------- One Stop Solution background video ---------- */
  // Muted YouTube embed behind the copy that autoplays and replays with no visible controls (client request).
  // Loaded only near the viewport, never for reduced-motion or Save-Data visitors (they keep the poster).
  // Controlled through the YouTube iframe API's postMessage protocol, so no extra script is loaded.
  // YouTube's own overlay buttons appear while the player starts, resumes or reaches the end, so:
  //  - the poster covers the frame until playback has run steadily for a moment;
  //  - the video is replayed by seeking to 0 just before it ends (YouTube's loop/playlist mode adds prev/next buttons
  //    and an end screen, so it is not used).
  var bgSection = document.querySelector('[data-bg-video]');
  if (bgSection) {
    var bgBox = bgSection.querySelector('[data-bg-video-frame]');
    var bgId = bgSection.getAttribute('data-bg-video');
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    var saveData = !!(conn && conn.saveData);
    var lessMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!lessMotion && !saveData && 'IntersectionObserver' in window) {
      var YT = 'https://www.youtube-nocookie.com';
      var REVEAL_DELAY = 1200; // ms of steady playback before the poster fades away
      var frame = null, ready = false, inView = false, listenTimer = null, revealTimer = null;
      var duration = 0, restarting = false;

      var command = function (func, args) {
        if (frame && frame.contentWindow) {
          frame.contentWindow.postMessage(JSON.stringify({ event: 'command', func: func, args: args || [], id: 'onestop-bg', channel: 'widget' }), YT);
        }
      };
      var showVideo = function (show) {
        clearTimeout(revealTimer);
        revealTimer = null;
        if (show) revealTimer = setTimeout(function () { revealTimer = null; bgSection.classList.add('is-playing'); }, REVEAL_DELAY);
        else bgSection.classList.remove('is-playing');
      };
      var replay = function () {
        if (restarting) return;
        restarting = true;
        command('seekTo', [0, true]);
        command('playVideo');
        setTimeout(function () { restarting = false; }, 1500);
      };

      var loadFrame = function () {
        var params = 'autoplay=1&mute=1&controls=0&playsinline=1&rel=0&enablejsapi=1&disablekb=1&fs=0&iv_load_policy=3';
        if (/^https?:$/.test(window.location.protocol)) params += '&origin=' + encodeURIComponent(window.location.origin);
        frame = document.createElement('iframe');
        frame.src = YT + '/embed/' + encodeURIComponent(bgId) + '?' + params;
        frame.title = 'Decorative background video';
        frame.tabIndex = -1;
        frame.setAttribute('aria-hidden', 'true');
        frame.allow = 'autoplay; encrypted-media; picture-in-picture';
        // Ask the player for events until it answers (same handshake the official API uses).
        frame.addEventListener('load', function () {
          var tries = 0;
          clearInterval(listenTimer);
          listenTimer = setInterval(function () {
            if (ready || ++tries > 30) { clearInterval(listenTimer); return; }
            frame.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 'onestop-bg', channel: 'widget' }), YT);
          }, 300);
        });
        bgBox.appendChild(frame);
      };

      window.addEventListener('message', function (e) {
        if (e.origin !== YT || !frame || e.source !== frame.contentWindow) return;
        var data;
        try { data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch (err) { return; }
        if (!data || !data.event) return;
        if (!ready && (data.event === 'onReady' || data.event === 'initialDelivery' || data.event === 'infoDelivery')) {
          ready = true;
          command(inView ? 'playVideo' : 'pauseVideo');
        }
        var info = data.event === 'infoDelivery' && data.info ? data.info : null;
        if (info && typeof info.duration === 'number' && info.duration > 0) duration = info.duration;
        // Replay just before the end so YouTube's end screen never appears.
        if (info && typeof info.currentTime === 'number' && duration && info.currentTime >= duration - 0.6) replay();

        var state = data.event === 'onStateChange' ? data.info : (info && info.playerState);
        if (state === 1) { if (!bgSection.classList.contains('is-playing') && !revealTimer) showVideo(true); }
        else if (state === 0) { showVideo(false); replay(); }                       // ended (fallback): restart behind the poster
        else if (state === 2 || ((state === 3 || state === -1) && !restarting)) {   // paused/stalled: show the poster, not YouTube's overlay
          showVideo(false);
        }
      });

      new IntersectionObserver(function (entries) {
        inView = entries[0].isIntersecting;
        if (inView && !frame) loadFrame();
        if (ready) command(inView ? 'playVideo' : 'pauseVideo');
      }, { rootMargin: '200px 0px' }).observe(bgSection);
    }
  }

  /* ---------- Newsletter ---------- */
  var form = document.querySelector('[data-newsletter]');
  if (form) {
    var email = form.elements.email;
    var consent = form.elements.consent; // optional: the live form is email-only
    var status = form.querySelector('.form-status');

    var setStatus = function (message, type) {
      status.textContent = message;
      status.className = 'form-status' + (type ? ' is-' + type : '');
    };
    var clearInvalid = function () {
      email.removeAttribute('aria-invalid');
      if (consent) consent.removeAttribute('aria-invalid');
    };

    email.addEventListener('input', function () {
      if (email.getAttribute('aria-invalid')) { clearInvalid(); setStatus(''); }
    });
    if (consent) {
      consent.addEventListener('change', function () {
        if (consent.getAttribute('aria-invalid')) { clearInvalid(); setStatus(''); }
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearInvalid();
      var value = email.value.trim();

      if (!value) {
        email.setAttribute('aria-invalid', 'true');
        setStatus('Enter your email address.', 'error');
        email.focus();
        return;
      }
      if (!email.checkValidity() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        email.setAttribute('aria-invalid', 'true');
        setStatus('Enter a valid email address, like name@company.com.', 'error');
        email.focus();
        return;
      }
      // TODO(client): confirm whether a consent checkbox is mandatory (brief §6, §10.6). Checked only if present.
      if (consent && !consent.checked) {
        consent.setAttribute('aria-invalid', 'true');
        setStatus('Please tick the box to confirm you agree to receive marketing emails.', 'error');
        consent.focus();
        return;
      }

      // TODO(integration): submit to HubSpot here (Forms API or embedded form; portal ID and
      // form GUID pending). Show a success message only after HubSpot confirms the submission.
      setStatus('Newsletter signup is not connected yet, so your email address has not been sent. ' +
        'In the meantime, email sales@arrsys.com to ask for updates.', 'pending');
    });
  }
})();
