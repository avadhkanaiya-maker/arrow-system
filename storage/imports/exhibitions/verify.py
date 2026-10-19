#!/usr/bin/env python3
"""
Exhibitions checks (EXHIBITIONS_MIGRATION_PLAN.md §10) against the local staging site:
  1. /exhibitions/ and all 28 show URLs → 200; /upcoming-exhibitions/ → 200 (D1)
  2. SEO parity with the live <head> (data/seo.json): title, meta description, canonical path, robots
     ("Arrow Systems, Inc." vs the staging site name normalised)
  3. photos per show: Craft (gallery count on the page) vs live (links to full-size images on the live page)
Run from the project root: python3 storage/imports/exhibitions/verify.py [base URL]
"""
import html, json, os, re, sys, urllib.request, urllib.error
BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8081').rstrip('/')
D = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')
seo = json.load(open(D + '/seo.json'))
pages = {p['slug']: p for p in json.load(open(D + '/pages.json'))}
listing = json.load(open(D + '/listing.json'))

def get(path):
    try:
        r = urllib.request.urlopen(BASE + path, timeout=60); return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, ''
def head(page):
    h = page.split('</head>')[0]
    m = lambda pat: (lambda x: html.unescape(x.group(1)) if x else None)(re.search(pat, h))
    return {'title': m(r'<title>(.*?)</title>'), 'description': m(r'<meta name="description" content="([^"]*)"'),
            'robots': m(r'<meta name="robots" content="([^"]*)"'), 'canonical': m(r'<link rel="canonical" href="([^"]*)"')}
norm = lambda s: (s or '').replace('Arrow Systems (Local Staging)', 'Arrow Systems, Inc.').strip()
path = lambda u: re.sub(r'^https?://[^/]+', '', u or '')

problems, rows = [], []
for slug in ['exhibitions'] + [r['slug'] for r in listing]:
    code, body = get('/%s/' % slug)
    if code != 200: problems.append('%s: HTTP %s' % (slug, code)); continue
    mine, live = head(body), seo.get(slug, {})
    for k in ('title', 'description', 'robots'):
        if norm(mine[k]) != norm(live.get(k)): problems.append('%s: %s %r ≠ live %r' % (slug, k, mine[k], live.get(k)))
    if path(mine['canonical']) != path(live.get('canonical')): problems.append('%s: canonical %s' % (slug, mine['canonical']))
    if slug != 'exhibitions':
        craft = len(re.findall(r'data-gallery-index="', body))
        c = pages[slug]['content']['rendered']
        live_n = len(set(re.findall(r'href="(https://arrsys\.com/media/(?!cache/)[^"?]+\.(?:jpe?g|png|webp|gif))"', c, re.I)))
        rows.append((slug, craft, live_n))
code, _ = get('/upcoming-exhibitions/')
print('1. URLs: /exhibitions/ + %d shows checked, /upcoming-exhibitions/ → %s' % (len(listing), code))
print('2. SEO parity: %d problems' % len([p for p in problems if 'HTTP' not in p]))
for p in problems: print('   -', p)
diff = [r for r in rows if r[1] != r[2]]
print('3. Photos: %d shows, %d with a different count (Craft / live): %s' % (len(rows), len(diff), diff))
print('   total photos: Craft %d, live %d' % (sum(r[1] for r in rows), sum(r[2] for r in rows)))
