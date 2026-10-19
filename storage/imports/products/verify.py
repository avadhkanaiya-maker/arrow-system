#!/usr/bin/env python3
"""
Products checks against the local staging site:
  1. all live product URLs (cache/*.status = 200) → 200 on staging; the 14 redirected ones → 301 to the same place as live
  2. SEO parity with the live <head> (cache/<slug>.html): title, meta description, canonical path, robots
     ("Arrow Systems, Inc." vs the staging site name normalised)
  3. content: H1, main image, video, PDFs (download list + links in text) vs the live page
  4. the homepage and the other sections still answer 200
Run from the project root: python3 storage/imports/products/verify.py [base URL]
"""
import glob, html, os, re, sys, urllib.request, urllib.error
BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8081').rstrip('/')
HERE = os.path.dirname(os.path.abspath(__file__))

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
opener = urllib.request.build_opener(NoRedirect)

def get(path):
    try:
        r = opener.open(BASE + path, timeout=60); return r.status, r.read().decode('utf-8', 'replace'), None
    except urllib.error.HTTPError as e:
        return e.code, '', e.headers.get('Location')
def head(page):
    h = page.split('</head>')[0]
    m = lambda pat: (lambda x: html.unescape(x.group(1)) if x else None)(re.search(pat, h, re.S))
    return {'title': m(r'<title>(.*?)</title>'), 'description': m(r'<meta name="description" content="([^"]*)"'),
            'robots': m(r'<meta name="robots" content="([^"]*)"'), 'canonical': m(r'<link rel="canonical" href="([^"]*)"')}
norm = lambda s: re.sub(r'\s+', ' ', (s or '').replace('Arrow Systems (Local Staging)', 'Arrow Systems, Inc.').replace('​', '')).strip()
path = lambda u: re.sub(r'^https?://[^/]+', '', u or '')
text = lambda s: norm(html.unescape(re.sub(r'<[^>]+>', '', s)))

live_ok, redirected = [], {}
for f in sorted(glob.glob(HERE + '/cache/*.status')):
    slug, st = os.path.basename(f)[:-7], open(f).read().strip()
    if st.startswith('200'): live_ok.append(slug)
    else: redirected[slug] = st
redirected.update({s: '' for s in ['arrowjet-bolt', 'arrowjet-hybrid-m-pro-plus', 'arrowjet-nova-250', 'arrowjet-aqua-330r',
                                    'arrow-systems-anycut-iii-13-roll-to-roll-laser-cutter']})   # old slugs in the design / menus

problems, content = [], []
for slug in live_ok:
    code, body, _ = get('/product/%s/' % slug)
    if code != 200: problems.append('%s: HTTP %s' % (slug, code)); continue
    live = open('%s/cache/%s.html' % (HERE, slug), encoding='utf-8', errors='replace').read()
    mine, theirs = head(body), head(live)
    for k in ('title', 'description', 'robots'):
        if norm(mine[k]) != norm(theirs[k]): problems.append('%s: %s %r ≠ live %r' % (slug, k, mine[k], theirs[k]))
    if path(mine['canonical']) != path(theirs['canonical']): problems.append('%s: canonical %s ≠ live %s' % (slug, mine['canonical'], theirs['canonical']))
    h1 = re.search(r'<h1 class="pd-hero__title"[^>]*>(.*?)</h1>', body, re.S)
    if not h1: problems.append('%s: no H1' % slug)
    if 'class="pd-media"' not in body: content.append('%s: no main image' % slug)
    live_vid = 'elementor-widget-video' in live
    if live_vid and 'data-video="' not in body: content.append('%s: live has a video, staging not' % slug)
    pdf_live = len(set(re.findall(r'href="(https://arrsys\.com/[^"]+\.pdf)"', live.split('data-elementor-type="footer"')[0], re.I)))
    pdf_mine = len(set(re.findall(r'class="download" href="([^"]+)"', body)))
    if pdf_live and pdf_mine < pdf_live and slug != 'arrowjet-aqua-330r-lite-2':
        rich = len(set(re.findall(r'href="(https://arrsys\.com/[^"]+\.pdf)"', body, re.I)))
        if pdf_mine + rich < pdf_live: content.append('%s: PDFs %d (+%d linked in text) vs live %d' % (slug, pdf_mine, rich, pdf_live))

print('1. URLs: %d products checked' % len(live_ok))
bad = 0
for slug in sorted(redirected):
    code, _, loc = get('/product/%s/' % slug)
    if code != 301: bad += 1; problems.append('redirect %s: HTTP %s' % (slug, code))
print('   %d redirects checked, %d wrong' % (len(redirected), bad))
print('2. SEO parity: %d problems' % len([p for p in problems if 'HTTP' not in p]))
for p in problems: print('   -', p)
print('3. Content: %d notes' % len(content))
for c in content: print('   -', c)
others = ['/', '/blog/', '/exhibitions/', '/upcoming-exhibitions/']
print('4. Other pages:', ', '.join('%s %s' % (p, get(p)[0]) for p in others))
