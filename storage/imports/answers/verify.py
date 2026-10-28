#!/usr/bin/env python3
"""
Answers checks against the local staging site (live data: cache/, fetched 2026-10-27):
  1. /answers/ → 200 with one card per live answer, in the live hub's order; every /answers/{slug}/ → 200
  2. SEO parity with the live <head> (cache/{slug}.html): title, meta description, robots, canonical path, og:image
     present ("Arrow Systems, Inc." vs the staging site name normalised)
  3. content: every text of the live page (each paragraph, heading, list item, table cell, question …) is on the
     staging page; FAQPage schema when the page has questions
  4. links on the staging pages that still go to arrsys.com
Run from the project root: python3 storage/imports/answers/verify.py [base URL, default http://localhost:8081]
"""
import html, json, os, re, sys, urllib.request
from html.parser import HTMLParser

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8081').rstrip('/')
C = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'cache')
children = {p['slug']: p for p in json.load(open(C + '/children.json'))}
hub = json.load(open(C + '/hub.json'))['content']['rendered']
order = []
for s in re.findall(r'href="https://arrsys\.com/answers/([^/"]+)/"', hub):
    if s in children and s not in order: order.append(s)
order += [s for s in children if s not in order]

def get(path):
    try:
        with urllib.request.urlopen(BASE + path, timeout=60) as r: return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e: return e.code, ''

norm = lambda s: re.sub(r'\s+', ' ', html.unescape(s or '').replace('​', '').replace('\xa0', ' ')
                        .replace('Arrow Systems (Local Staging)', 'Arrow Systems, Inc.').replace('’', "'").replace('“', '"').replace('”', '"')).strip()
def head(page):
    h = page.split('</head>')[0]
    m = lambda pat: (lambda x: html.unescape(x.group(1)) if x else None)(re.search(pat, h, re.S))
    return {'title': m(r'<title>(.*?)</title>'), 'description': m(r'<meta name="description" content="([^"]*)"'),
            'robots': m(r'<meta name="robots" content="([^"]*)"'), 'canonical': m(r'<link rel="canonical" href="([^"]*)"'),
            'ogImage': m(r'<meta property="og:image" content="([^"]*)"')}

class Texts(HTMLParser):   # the text of every block-level element, without styles / scripts / icons
    BLOCK = {'p', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'td', 'th', 'summary', 'button', 'a', 'span', 'div', 'caption'}
    def __init__(self):
        super().__init__(); self.skip = 0; self.out = []; self.buf = []
    def handle_starttag(self, tag, a):
        if tag in ('style', 'script', 'svg', 'title', 'noscript'): self.skip += 1
        elif tag in self.BLOCK or tag == 'br': self.flush()
    def handle_endtag(self, tag):
        if tag in ('style', 'script', 'svg', 'title', 'noscript'): self.skip = max(0, self.skip - 1)
        elif tag in self.BLOCK: self.flush()
    def handle_data(self, d):
        if not self.skip: self.buf.append(d)
    def flush(self):
        t = norm(''.join(self.buf)); self.buf = []
        if len(t) >= 3: self.out.append(t)
def texts(h):
    p = Texts(); p.feed(h); p.flush(); return p.out
flat = lambda s: re.sub(r'[^a-z0-9]+', ' ', norm(s).lower()).strip()   # case, punctuation and arrows don't count

code, listing = get('/answers/')
cards = re.findall(r'<li class="ans-card".*?href="[^"]*/answers/([^/"]+)/"', listing, re.S)
print('1. /answers/ → %s, %d cards, live order: %s' % (code, len(cards), 'yes' if cards == order else 'NO (%s)' % cards))

seo, content, schema, live_links, bad = [], [], [], set(), []
for slug in order:
    code, page = get('/answers/%s/' % slug)
    if code != 200: bad.append('%s: HTTP %s' % (slug, code)); continue
    main = page.split('<main', 1)[-1].split('</main>')[0]
    live_page = open('%s/%s.html' % (C, slug), encoding='utf-8', errors='replace').read()
    if 'One moment' not in live_page.split('</head>')[0]:
        mine, theirs = head(page), head(live_page)
        for k in ('title', 'description', 'robots'):
            if norm(mine[k]) != norm(theirs[k]): seo.append('%s: %s %r ≠ live %r' % (slug, k, mine[k], theirs[k]))
        path = lambda u: re.sub(r'^https?://[^/]+', '', u or '')
        if path(mine['canonical']) != path(theirs['canonical']): seo.append('%s: canonical %s ≠ live %s' % (slug, mine['canonical'], theirs['canonical']))
        if theirs['ogImage'] and not mine['ogImage']: seo.append('%s: no og:image' % slug)
    else:
        seo.append('%s: not compared (live sent its bot check)' % slug)
    have = flat(' '.join(texts(main)))
    missing = [t for t in texts(children[slug]['content']['rendered']) if len(flat(t)) >= 12 and flat(t) not in have]
    if missing: content.append((slug, missing))
    if ('<details' in children[slug]['content']['rendered'] or 'faq' in children[slug]['content']['rendered'].lower()) and '"FAQPage"' not in page:
        schema.append(slug)
    live_links |= set(re.findall(r'href="(https?://(?:www\.)?arrsys\.com/[^"]*)"', main))

print('   answers: %d of %d → 200 %s' % (len(order) - len(bad), len(order), bad or ''))
print('2. SEO: %d differences' % len([s for s in seo if 'not compared' not in s]))
for s in seo: print('   -', s)
print('3. Content: %d pages with live text not found on staging' % len(content))
for slug, miss in content:
    print('   - %s: %d' % (slug, len(miss)))
    for m in miss[:6]: print('       · %s' % m[:150])
print('   FAQPage schema missing on: %s' % (schema or 'none'))
print('4. Links to arrsys.com left on the answer pages: %d' % len(live_links))
for l in sorted(live_links): print('   -', l)
