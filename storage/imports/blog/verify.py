#!/usr/bin/env python3
"""
Blog migration checks (BLOG_MIGRATION_PLAN.md §9), against the local staging site:
  1. every live post URL: imported posts → 200, the 9 redirected ones → 301 to the same target as live
  2. every URL in the live post sitemaps → 200 (or the live redirect)
  3. archives: /blog/, /blog/page/2/ … last page → 200, one page past the end → 404; all categories and tags → 200;
     /author/admin/ → 301 /blog/
  4. SEO parity per post with the live <head> (data/seo.json): title, meta description, canonical path, robots,
     og:image present. The site name differs on staging ("… (Local Staging)"), so "Arrow Systems, Inc." is normalised.
Run from the project root: python3 storage/imports/blog/verify.py [base URL, default http://localhost:8081]
"""
import glob, html, json, os, re, sys, urllib.request, urllib.error
from concurrent.futures import ThreadPoolExecutor

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8081').rstrip('/')
D = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')
posts = [p for f in sorted(glob.glob(D + '/posts-*.json')) for p in json.load(open(f))]
seo = json.load(open(D + '/seo.json'))
redirects = {l.split('\t')[0]: l.split('\t')[2].strip() for l in open(D + '/live-redirects.tsv') if l.strip()}
cats = json.load(open(D + '/categories.json'))
tags = [t for f in glob.glob(D + '/tags-*.json') for t in json.load(open(f))]

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
opener = urllib.request.build_opener(NoRedirect)

def get(path):
    try:
        r = opener.open(BASE + path, timeout=60)
        return r.status, r.read().decode('utf-8', 'replace'), None
    except urllib.error.HTTPError as e:
        return e.code, '', e.headers.get('Location')

def head_tags(page):
    h = page.split('</head>')[0]
    def meta(attr, name):
        m = re.search(r'<meta %s="%s" content="([^"]*)"' % (attr, re.escape(name)), h)
        return html.unescape(m.group(1)) if m else None
    t = re.search(r'<title>(.*?)</title>', h, re.S)
    c = re.search(r'<link rel="canonical" href="([^"]*)"', h)
    return {'title': html.unescape(t.group(1).strip()) if t else None, 'description': meta('name', 'description'),
            'robots': meta('name', 'robots'), 'canonical': c.group(1) if c else None, 'ogImage': meta('property', 'og:image')}

problems = []
def check_post(p):
    slug = p['slug']
    code, body, loc = get('/%s/' % slug)
    if slug in redirects:
        want = redirects[slug].replace('https://arrsys.com', BASE)
        return None if code == 301 and loc == want else '%s: expected 301 → %s, got %s %s' % (slug, want, code, loc)
    if code != 200: return '%s: HTTP %s' % (slug, code)
    live, mine = seo.get(slug, {}), head_tags(body)
    norm = lambda s: (s or '').replace('Arrow Systems (Local Staging)', 'Arrow Systems, Inc.').strip()
    diffs = []
    if norm(mine['title']) != norm(live.get('title')): diffs.append('title %r ≠ live %r' % (mine['title'], live.get('title')))
    if (mine['description'] or '') != (live.get('description') or ''): diffs.append('description differs')
    if mine['robots'] != live.get('robots'): diffs.append('robots %r ≠ %r' % (mine['robots'], live.get('robots')))
    path = lambda u: re.sub(r'^https?://[^/]+', '', u or '')
    if path(mine['canonical']) != path(live.get('canonical')) and path(live.get('canonical')) == '/%s/' % slug: diffs.append('canonical %s' % mine['canonical'])
    if live.get('ogImage') and not mine['ogImage']: diffs.append('no og:image')
    return '%s: %s' % (slug, '; '.join(diffs)) if diffs else None

with ThreadPoolExecutor(6) as ex:
    post_results = list(ex.map(check_post, posts))
post_problems = [r for r in post_results if r]
print('1+4. Posts: %d live posts (%d redirected), %d with problems' % (len(posts), len(redirects), len(post_problems)))
for r in post_problems[:40]: print('   -', r)

sitemap_urls = []
for f in glob.glob(D + '/../../../../storage/imports/blog/data/post-sitemap*.xml') + glob.glob(D + '/post-sitemap*.xml'):
    sitemap_urls += re.findall(r'<loc>https://arrsys\.com(/[^<]*)</loc>', open(f).read())
if sitemap_urls:
    with ThreadPoolExecutor(6) as ex:
        res = list(ex.map(lambda u: (u, get(u)[0]), sorted(set(sitemap_urls))))
    bad = [(u, c) for u, c in res if c not in (200, 301)]
    print('2. Sitemap URLs: %d, not 200/301: %d %s' % (len(res), len(bad), bad[:10]))
else:
    print('2. Sitemap URLs: (no sitemap files in data/)')

n_posts = len(posts) - len(redirects)
last = (n_posts + 9) // 10
arch = ['/blog/'] + ['/blog/page/%d/' % i for i in range(2, last + 1)]
with ThreadPoolExecutor(6) as ex:
    res = list(ex.map(lambda u: (u, get(u)[0]), arch))
bad = [(u, c) for u, c in res if c != 200]
past = get('/blog/page/%d/' % (last + 1))[0]
print('3. Blog listing: %d pages, not 200: %s; page %d → %s (want 404)' % (len(arch), bad or 'none', last + 1, past))

byid = {c['id']: c for c in cats}
def cat_path(c):
    s = [c['slug']]
    while c['parent']: c = byid[c['parent']]; s.insert(0, c['slug'])
    return '/category/%s/' % '/'.join(s)
with ThreadPoolExecutor(6) as ex:
    res = list(ex.map(lambda c: (cat_path(c), get(cat_path(c))[0]), cats))
print('   Categories: %d, not 200: %s' % (len(res), [r for r in res if r[1] != 200] or 'none'))
with ThreadPoolExecutor(6) as ex:
    res = list(ex.map(lambda t: ('/tag/%s/' % t['slug'], get('/tag/%s/' % t['slug'])), tags))
bad = [u for u, (c, b, l) in res if c != 200]
noindex = sum(1 for u, (c, b, l) in res if 'content="follow, noindex"' in b)
print('   Tags: %d, not 200: %s; noindex: %d' % (len(res), bad or 'none', noindex))
code, _, loc = get('/author/admin/')
print('   /author/admin/ → %s %s' % (code, loc))
