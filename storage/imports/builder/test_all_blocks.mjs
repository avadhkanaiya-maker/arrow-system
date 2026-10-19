// Checks every page-builder block on the 3 test pages (test_all_blocks.php) in headless Chrome: markup, computed styles,
// behaviour, JS errors, failed requests, broken images, phone overflow; full-page screenshots into <out dir>.
//   node --experimental-websocket storage/imports/builder/test_all_blocks.mjs [out dir] [base URL]
import { spawn } from 'node:child_process'; import { writeFileSync, mkdtempSync } from 'node:fs'; import { tmpdir } from 'node:os';
const out = process.argv[2] || mkdtempSync(tmpdir() + '/blocks-'); const base = process.argv[3] || 'http://localhost:8081';
const chrome = spawn('google-chrome', ['--headless=new', '--no-sandbox', '--disable-gpu', '--remote-debugging-port=9499', `--user-data-dir=${out}/profile`, '--window-size=1440,900', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let t; for (let i = 0; i < 40; i++) { try { t = await (await fetch('http://127.0.0.1:9499/json')).json(); break; } catch { await sleep(250); } }
const ws = new WebSocket(t.find(x => x.type === 'page').webSocketDebuggerUrl); await new Promise(r => ws.onopen = r);
let id = 0; const p = {}; let errors = [], failed = [];
ws.onmessage = m => { const d = JSON.parse(m.data); if (d.id && p[d.id]) { p[d.id](d); delete p[d.id]; }
  if (d.method === 'Runtime.exceptionThrown') errors.push(d.params.exceptionDetails.exception?.description || d.params.exceptionDetails.text);
  if (d.method === 'Network.responseReceived' && d.params.response.status >= 400 && d.params.response.url.startsWith(base)) failed.push(d.params.response.status + ' ' + d.params.response.url); };
const send = (method, params = {}) => new Promise(r => { const i = ++id; p[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
const ev = async e => { const r = await send('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true }); return r.result?.exceptionDetails ? 'EVAL ERROR ' + JSON.stringify(r.result.exceptionDetails.exception?.description) : r.result?.result?.value; };
await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');

// in-page helpers: q(sel) count, cs(el, prop, pseudo), ok(name, cond, detail)
const helpers = `
  const R = []; const ok = (name, cond, detail = '') => R.push((cond ? 'PASS ' : 'FAIL ') + name + (cond ? '' : '  → ' + detail));
  const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
  const cs = (el, prop, pseudo) => el ? getComputedStyle(el, pseudo || null).getPropertyValue(prop) : '(missing)';
  const cols = el => el ? cs(el, 'grid-template-columns').split(' ').filter(Boolean).length : 0;
  const wait = ms => new Promise(r => setTimeout(r, ms));
  const text = s => ($(s)?.textContent || '').trim();`;
const commonChecks = `
  const pr = $$('.prose');
  ok('common: blocks sit in a .prose column', pr.length >= 1, pr.length);
  const h2 = $$('.prose h2').find(h => h.textContent.trim() === 'Test H2 heading');
  ok('heading H2 (+ Anchor ID "test-h2")', h2 && h2.id === 'test-h2', h2 ? 'id=' + h2.id : 'no h2');
  ok('heading answer lead p.answer', $$('.prose p.answer').length === 1);
  ok('heading H3 / H4', $$('.prose h3').some(h => h.textContent.trim() === 'Test H3 heading') && $$('.prose h4').some(h => h.textContent.trim() === 'Test H4 heading'));
  ok('intro p.post-intro styled (larger text)', parseFloat(cs($('.post-intro'), 'font-size')) > parseFloat(cs($('.prose > p:not([class])') || $('.prose p'), 'font-size')) - 0.1, cs($('.post-intro'), 'font-size'));
  const rtUl = $$('.prose ul:not([class])').find(u => u.textContent.includes('Bullet one'));
  ok('rich text: bullets with markers', rtUl && cs(rtUl.querySelector('li'), 'content', '::before') !== 'none', 'no bullets');
  ok('rich text: numbered list, bold, link, blockquote', $$('.prose ol li').some(l => l.textContent.includes('Number one')) && $('.prose strong') && $('.prose a[href="/industries/"]') && $('.prose blockquote'));
  ok('callout p.callout styled', $('.prose p.callout') && (cs($('.prose p.callout'), 'border-left-width') !== '0px' || cs($('.prose p.callout'), 'background-color') !== 'rgba(0, 0, 0, 0)'), cs($('.prose p.callout'), 'border-left-width'));
  ok('callout note style p.note', !!$('.prose p.note'));
  ok('quote figure.prose-quote + source', $('figure.prose-quote blockquote, figure.prose-quote p') && /Test Person/.test(text('figure.prose-quote')));
  const tk = $('section.takeaways');
  ok('takeaways: box, heading, 2 items, Anchor ID', tk && tk.querySelectorAll('li').length === 2 && /Key takeaways/.test(tk.textContent) && (tk.id === 'test-takeaways' || !!tk.querySelector('#test-takeaways')), tk ? 'id=' + tk.id : 'missing');
  const ck = $$('ul.checklist').find(u => u.textContent.includes('Checklist item one'));
  ok('checklist: 2 items with tick markers, markdown bold', ck && ck.querySelectorAll('li').length === 2 && cs(ck.querySelector('li'), 'background-image', '::before') !== 'none' && ck.querySelector('strong'), ck ? cs(ck.querySelector('li'), 'background-image', '::before').slice(0, 30) : 'missing');
  ok('steps: ol.steps 3 items, numbered', $$('ol.steps > li').length === 3 && cs($('ol.steps > li'), 'content', '::before') !== 'none', $$('ol.steps > li').length);
  const sl = $$('dl.spec-list').find(d => d.closest('.prose'));
  ok('spec list: 2 rows, markdown bold', sl && sl.querySelectorAll('dt').length === 2 && sl.querySelector('dd strong'));
  ok('compare: pro ✓ / con ✕ markers, 2 columns', cs($('.compare__col--pro li'), 'background-image', '::before').includes('svg') && cs($('.compare__col--con li'), 'background-image', '::before').includes('svg') && (innerWidth < 768 || cols($('.compare')) === 2), cs($('.compare__col--pro li'), 'background-color', '::before'));
  const figs = $$('figure.prose-figure'); const fw = s => $('figure.prose-figure' + s)?.getBoundingClientRect().width || 0;
  ok('image: full / large / medium / small widths', figs.length === 4 && fw(':not([class*="--"])') > fw('.prose-figure--large') && fw('.prose-figure--large') > fw('.prose-figure--medium') && fw('.prose-figure--medium') > fw('.prose-figure--small'), [fw(':not([class*="--"])'), fw('.prose-figure--large'), fw('.prose-figure--medium'), fw('.prose-figure--small')].map(Math.round).join('/'));
  ok('image: captions', $$('figure.prose-figure figcaption').length === 4);
  ok('gallery: 2 / 3 / 4 columns', cols($('.prose-gallery--2')) === 2 && cols($('.prose-gallery--3')) === 3 && cols($('.prose-gallery--4')) === 4, [2, 3, 4].map(n => cols($('.prose-gallery--' + n))).join('/'));
  ok('video: embed iframe 16:9', $('figure.prose-video iframe') && Math.abs($('.prose-video__frame').getBoundingClientRect().width / $('.prose-video__frame').getBoundingClientRect().height - 16 / 9) < .05);
  ok('data table: table.data-table + caption, 2 rows', $('table.data-table') && $$('table.data-table tbody tr').length === 2 && /Data table caption/.test(text('.table-wrap') + text('table.data-table caption')));
  ok('product list: 2 items, entry link → product URL', $$('ul.product-list > li').length === 2 && $$('ul.product-list a').some(a => a.href.includes('/product/')));
  ok('buttons: primary / outline / link', $('.prose-button a.btn--primary') && $('.prose-button a.btn--outline') && $$('.prose-button a.text-link').length >= 1);
  ok('button text visible (not maroon on maroon)', cs($('.prose-button a.btn--primary'), 'color') !== cs($('.prose-button a.btn--primary'), 'background-color'), cs($('.prose-button a.btn--primary'), 'color'));
  ok('call to action box', $$('.post-cta').some(c => /Call to action heading/.test(c.textContent)));
  const fq = $$('details.faq__item'); if (fq[0]) fq[0].querySelector('summary').click();
  ok('faq: 2 questions, opens on click', fq.length === 2 && fq[0].open);
  ok('divider hr.prose-divider', !!$('hr.prose-divider'));
  ok('html block output as-is', !!$('.test-html-block strong'));
  ok('every common block stays inside its column (no unclosed markup)', !!$('.prose .test-html-block') && $$('.prose').length === 1, $$('.prose').length + ' columns');`;
const pages = {
  '/builder-test-post/': `
    ok('blog: contents list has the H2 label', /H2 in contents/.test(text('[data-toc]') || text('.toc')), text('[data-toc]').slice(0, 60));`,
  '/product/builder-test-product/': `
    ok('product: header video in the header (Show In: Header)', !!$('.pd-hero__body aside.pd-video[aria-label]'));
    ok('product: page video in the details (Show In: Page)', !!$('.pd-details__grid > aside.pd-video'));
    const tabs = $$('[role=tab]'); tabs[1]?.click();
    ok('product tabs: 3 tabs, click switches panel', tabs.length === 3 && $$('[data-panel]').filter(x => !x.hidden).map(x => x.id).join() === 'features', $$('[data-panel]').filter(x => !x.hidden).map(x => x.id).join());
    ok('product tabs: text / specs / 2 downloads (Spanish hreflang)', $('#overview .rich-text h4') && $$('#features dt').length === 2 && $$('#brochure a.download').length === 2 && $('#brochure a.download[hreflang=es]'));
    $('[data-open-tab]')?.click(); await wait(300);
    ok('product: "Download …" quick link opens the downloads tab', $('#tab-brochure')?.getAttribute('aria-selected') === 'true');
    ok('product: Specifications block (panel + title)', $$('.pd-block .pd-block__title').some(h => /Specifications block/.test(h.textContent)) && $$('.pd-block dl.spec-list').length >= 1);
    ok('product: Downloads block', $$('.pd-block a.download').length === 1);
    ok('product: common blocks in a white panel', !!$('.pd-block > .prose'));
    const look = d => d ? [cs(d, 'border-top-width'), cs(d.querySelector('div'), 'border-radius')].join() : 'missing';
    ok('spec lists: product rows (tab + Specifications block), blog cards (common column)', look($('#features .spec-list')) === '2px,0px' && look($('.pd-block > .spec-list')) === '2px,0px' && look($('.prose .spec-list')) === '0px,10px', [look($('#features .spec-list')), look($('.pd-block > .spec-list')), look($('.prose .spec-list'))].join(' | '));
    $('.pd-hero__body [data-video-play]')?.click(); await wait(500);
    ok('product: header video plays on click', !!$('.pd-hero__body .video iframe'));`,
  '/builder-test-industry/': `
    ok('industry header: H1 with red part, 2 intro paragraphs, 2 tags, image, 2 buttons', $('h1 .hl') && $$('.ind-detail-hero__lead').length === 2 && $$('.app-tags li').length === 2 && $('.ind-detail-hero__media img') && $$('.ind-detail-hero .btn-row a').length === 2);
    const eq = $$('.ind-equipment');
    ok('recommended products: picked (3) / category (4) / single wide card', eq.length === 3 && eq[0].querySelectorAll('.pcard').length === 3 && eq[1].querySelectorAll('.pcard').length === 4 && eq[2].querySelector('.pgrid--one') && eq[2].querySelectorAll('.pcard').length === 1, eq.map(e => e.querySelectorAll('.pcard').length).join('/'));
    ok('recommended products: Anchor ID "equipment" + View All link', eq[0]?.id === 'equipment' && eq[0].querySelector('.text-link'));
    const cb = $$('.ind-blocks .content-block');
    ok('content blocks: 4 in one section (image / video / text / plain)', cb.length === 4 && cb[0].querySelector('.content-block__media img') && cb[1].querySelector('.content-block__media--video') && cb[2].classList.contains('content-block--text') && cb[3].classList.contains('content-block--plain'), cb.map(c => c.className).join(' | '));
    ok('content block: 2nd media on the other side (alternates)', innerWidth < 1024 || cb[1].querySelector('.content-block__media').getBoundingClientRect().left > cb[1].querySelector('.content-block__body').getBoundingClientRect().left);
    ok('content block: text-only → heading left, text right', innerWidth < 1024 || cb[2].querySelector('.content-block__head').getBoundingClientRect().right <= cb[2].querySelector('.content-block__body').getBoundingClientRect().left + 1);
    const pt = cb[3]?.querySelector('.content-block__text p'), rects = pt ? [...pt.getClientRects()] : [];
    ok('content block: plain text really flows into 2 columns', innerWidth < 1024 || new Set(rects.map(r => Math.round(r.left))).size === 2, rects.length + ' boxes');
    cb[1].querySelector('[data-video-play]')?.click(); await wait(500);
    ok('content block video plays on click', !!cb[1].querySelector('iframe'));
    const gs = $$('.gallery-section');
    ok('photo gallery: 9 photos (big tile) + 2 photos (side by side)', gs.length === 2 && gs[0].querySelectorAll('.gallery__item').length === 9 && gs[0].querySelector('.gallery__item--big') && gs[1].querySelector('.gallery--few'));
    gs[0]?.querySelector('[data-gallery-index]')?.click(); await wait(400);
    ok('photo gallery: lightbox opens, counts all 11 photos', $('[data-lightbox]')?.open && /of 11|\\/ 11/.test(text('[data-lightbox-count]')), text('[data-lightbox-count]')); $('[data-lightbox]')?.close();
    ok('CTA band (maroon, buttons), [red] words readable', $('.sustain.ind-cta h2 .hl') && $$('.ind-cta .btn').length >= 1 && cs($('.ind-cta h2 .hl'), 'color') === cs($('.ind-cta h2'), 'color'), cs($('.ind-cta h2 .hl'), 'color'));
    const f = $('[data-contact-form]'); f?.requestSubmit(); const empty = text('[data-contact-form] [data-form-status]');
    if (f) { f.elements.name.value = 'Test'; f.elements.email.value = 'a@b.co'; f.elements.phone.value = '+1 716 285 2974'; f.requestSubmit(); }
    ok('contact form: validates, then says not connected', /name/i.test(empty) && /not connected/i.test(text('[data-contact-form] [data-form-status]')), empty);
    ok('contact panel: phones / email / address from Site Settings', $$('.contact-panel__list li').length === 4);
    ok('other industries: the 3 picked', $$('.ind-more .ind-card').length === 3);`,
};

const report = []; let fails = 0;
for (const [path, own] of Object.entries(pages)) {
  for (const width of [1440, 390]) {
    errors = []; failed = [];
    await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: width < 600 });
    await send('Page.navigate', { url: base + path }); await sleep(3500);
    await ev(`(async () => { [...document.images].forEach(i => i.loading = 'eager'); await Promise.all([...document.images].map(im => im.complete ? 0 : new Promise(r => { im.onload = im.onerror = r; setTimeout(r, 6000); }))); })()`);
    const results = width === 1440 ? await ev(`(async () => { ${helpers} ${commonChecks} ${own} return R; })()`) : await ev(`(async () => { ${helpers} ${commonChecks.replace(/ok\('(image: full|gallery: 2|content block: (2nd|text-only|plain))[^;]*;/g, '')} return R; })()`);
    const broken = await ev(`[...document.images].filter(i => i.complete && i.naturalWidth === 0 && i.getAttribute('src')).map(i => i.src)`);
    const overflow = await ev('document.documentElement.scrollWidth > innerWidth');
    const lines = Array.isArray(results) ? results : ['FAIL checks did not run: ' + results];
    lines.push((errors.length ? 'FAIL' : 'PASS') + ' no JavaScript errors' + (errors.length ? '  → ' + errors.join(' | ') : ''));
    lines.push((failed.length ? 'FAIL' : 'PASS') + ' no failed requests' + (failed.length ? '  → ' + failed.join(' | ') : ''));
    lines.push((broken.length ? 'FAIL' : 'PASS') + ' no broken images' + (broken.length ? '  → ' + broken.join(' ') : ''));
    lines.push((overflow ? 'FAIL' : 'PASS') + ' no sideways scroll');
    fails += lines.filter(l => l.startsWith('FAIL')).length;
    report.push(`### ${path} @ ${width}px`, ...lines);
    const h = await ev('document.documentElement.scrollHeight');
    await send('Emulation.setDeviceMetricsOverride', { width, height: Math.min(h, 16000), deviceScaleFactor: 1, mobile: width < 600 }); await sleep(800);
    const s = await send('Page.captureScreenshot'); writeFileSync(`${out}/${path.split('/').filter(Boolean).pop()}-${width}.png`, Buffer.from(s.result.data, 'base64'));
  }
}
console.log(report.join('\n')); console.log(`\n${fails} failed check(s). Screenshots: ${out}`);
ws.close(); chrome.kill(); process.exit(fails ? 1 : 0);
