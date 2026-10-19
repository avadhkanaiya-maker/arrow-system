#!/usr/bin/env python3
"""
Industries checks against the local staging site (INDUSTRIES_MIGRATION_PLAN.md §10):
  1. /industries/ and the 12 industry URLs → 200 at the live path (trailing slash)
  2. SEO parity with the live <head> (cache/<slug>.html): title, meta description, canonical path, robots, og:image
     present when live has one ("Arrow Systems, Inc." vs the staging site name normalised)
  3. content: H1, every live photo and video present (by file name / YouTube id; image-box icons skipped), product links →
     product cards, no image or link loading from arrsys.com for migrated pages. Expected notes: the photos of short
     product teasers (Matan SprinG3 …), which the product cards replace
  4. the homepage (section E), /blog/, /exhibitions/ and a product page still answer 200
Run from the project root: python3 storage/imports/industries/verify.py [base URL]
"""
import glob, html, os, re, sys, urllib.request, urllib.error
BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8081').rstrip('/')
HERE = os.path.dirname(os.path.abspath(__file__))

def get(path):
    try:
        r = urllib.request.urlopen(BASE + path, timeout=60); return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, ''
def head(page):
    h = page.split('</head>')[0]
    m = lambda pat: (lambda x: html.unescape(x.group(1)) if x else None)(re.search(pat, h, re.S))
    return {'title': m(r'<title>(.*?)</title>'), 'description': m(r'<meta name="description" content="([^"]*)"'),
            'robots': m(r'<meta name="robots" content="([^"]*)"'), 'canonical': m(r'<link rel="canonical" href="([^"]*)"'),
            'og:image': m(r'<meta property="og:image" content="([^"]*)"')}
norm = lambda s: re.sub(r'\s+', ' ', (s or '').replace('Arrow Systems (Local Staging)', 'Arrow Systems, Inc.')).strip()
path = lambda u: re.sub(r'^https?://[^/]+', '', u or '')
base = lambda u: re.sub(r'-\d+x\d+(?=\.\w+$)', '', os.path.basename(u.split('?')[0])).lower()   # WordPress size suffix off

slugs = sorted(os.path.basename(f)[:-5] for f in glob.glob(HERE + '/cache/*.html'))
problems, content = [], []
for slug in slugs:
    code, body = get('/%s/' % slug)
    if code != 200: problems.append('%s: HTTP %s' % (slug, code)); continue
    live = open('%s/cache/%s.html' % (HERE, slug), encoding='utf-8', errors='replace').read()
    mine, theirs = head(body), head(live)
    for k in ('title', 'description', 'robots'):
        if norm(mine[k]) != norm(theirs[k]): problems.append('%s: %s %r ≠ live %r' % (slug, k, mine[k], theirs[k]))
    if path(mine['canonical']) != path(theirs['canonical']): problems.append('%s: canonical %s ≠ live %s' % (slug, mine['canonical'], theirs['canonical']))
    if bool(theirs['og:image']) and not mine['og:image']: problems.append('%s: no og:image (live has one)' % slug)
    if slug == 'industries': continue
    main = body.split('<main id="main">')[1].split('class="newsletter"')[0]
    if not re.search(r'<h1[^>]*>', main): content.append('%s: no H1' % slug)
    page = live.split('data-elementor-type="wp-page"')[1].split('data-elementor-type="footer"')[0] if 'data-elementor-type="wp-page"' in live else ''
    live_imgs = {base(u) for u in re.findall(r'(?:src|href)="(https://arrsys\.com/media/(?!cache/)[^"]+\.(?:jpe?g|png|webp|gif))"', page, re.I)}
    mine_imgs = {base(u) for u in re.findall(r'(?:src|href)="[^"]*/uploads/industries/([^"]+\.(?:jpe?g|png|webp|gif))"', main, re.I)}
    icons = {base(u) for box in re.findall(r'elementor-widget-image-box.*?</div>\s*</div>\s*</div>', page, re.S) for u in re.findall(r'src="([^"]+)"', box)}
    missing = sorted(i for i in live_imgs - mine_imgs - icons if not re.search(r'icons8|logo', i))   # image-box icons: decorative (their text is kept)
    if slug != 'flexible-packaging-and-custom-mylar-bags' and missing:   # the pilot uses the design's photos
        content.append('%s: %d live photo(s) not on the page: %s' % (slug, len(missing), ', '.join(missing[:6])))
    for vid in set(re.findall(r'youtu(?:\.be/|be\.com/(?:embed/|watch\?v=))([\w-]{11})', page)):
        if vid not in main: content.append('%s: live video %s missing' % (slug, vid))
    for p in set(re.findall(r'href="https://arrsys\.com/product/([^/"]+)/"', page)):
        if 'class="pcard"' not in main: content.append('%s: live product link %s, but no product cards' % (slug, p))
    if re.search(r'(?:src|href)="https://arrsys\.com/(?:media|product/|industries/)', main): content.append('%s: still loads / links arrsys.com media, products or industries' % slug)

print('1. URLs: %d checked (/industries/ + %d industries)' % (len(slugs), len(slugs) - 1))
print('2. SEO parity: %d problems' % len([p for p in problems if 'HTTP' not in p]))
for p in problems: print('   -', p)
print('3. Content: %d notes' % len(content))
for c in content: print('   -', c)
code, home = get('/')
print('4. Other pages:', ', '.join('%s %s' % (p, get(p)[0]) for p in ['/', '/blog/', '/exhibitions/', '/product/arrowjet-aqua-330r-lite-2/']),
      '| homepage section E:', 'yes' if 'class="section industries"' in home else 'MISSING')
