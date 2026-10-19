#!/usr/bin/env python3
"""
Blog: fetch the posts published on the live site AFTER the export in data/ (the WordPress REST API only returns
published posts; drafts need a WordPress export or an application password). Adds, never rewrites:
  data/posts-N.json      the new posts (same fields as posts-1..4.json)
  data/media-N.json      their featured images (alt text, URL)
  data/tags-N.json       tags they use that are not saved yet (then run 03_taxonomy.php: it adds missing tags only)
  data/categories.json   re-fetched only when a new post uses an unknown category
  data/seo.json          + each new post's live <head>: title, description, robots, canonical, og:image (+ alt),
                         breadcrumb (main) category, reading time
  data/live-redirects.tsv + new posts whose URL already redirects on live (they are not imported)
Then import them:  /opt/lampp/bin/php storage/imports/blog/05_import.php --slugs=<printed slugs>
Run from the project root: python3 storage/imports/blog/10_fetch_new_posts.py
"""
import glob, html, json, os, re, urllib.request

D = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')
API = 'https://arrsys.com/wp-json/wp/v2/'
POST_FIELDS = 'author,categories,content,date,date_gmt,excerpt,featured_media,id,link,modified,modified_gmt,slug,status,tags,title'
UA = {'User-Agent': 'Mozilla/5.0 (Arrow Systems staging migration)'}

def get(url, raw=False):
    with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60) as r:
        body = r.read().decode('utf-8', 'replace')
        return (body, r.headers) if raw else json.loads(body)

def status(url):   # HTTP status without following redirects
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k): return None
    try:
        r = urllib.request.build_opener(NoRedirect).open(urllib.request.Request(url, headers=UA), timeout=60)
        return r.status, ''
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location', '')

def load_all(prefix):
    out = {}
    for f in glob.glob(f'{D}/{prefix}-*.json'):
        for item in json.load(open(f)): out[item['id']] = item
    return out

def next_file(prefix):
    nums = [int(m.group(1)) for f in glob.glob(f'{D}/{prefix}-*.json') if (m := re.search(r'-(\d+)\.json$', f))]
    return f'{D}/{prefix}-{max(nums, default=0) + 1}.json'

def seo_of(url):
    page, _ = get(url, raw=True)
    head = page.split('</head>')[0]
    meta = lambda attr, name: (lambda m: html.unescape(m.group(1)) if m else None)(re.search(rf'<meta {attr}="{re.escape(name)}" content="([^"]*)"', head))
    title = re.search(r'<title>(.*?)</title>', head, re.S)
    canonical = re.search(r'<link rel="canonical" href="([^"]*)"', head)
    crumb = None
    for block in re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', page, re.S):
        try: data = json.loads(block)
        except ValueError: continue
        for node in data.get('@graph', [data]) if isinstance(data, dict) else []:
            if node.get('@type') == 'BreadcrumbList':
                for li in node.get('itemListElement', []):
                    if str(li.get('position')) == '2': crumb = (li.get('item') or {}).get('@id') if isinstance(li.get('item'), dict) else li.get('item')
    read = re.search(r'<meta name="twitter:label2" content="Time to read"\s*/?>\s*<meta name="twitter:data2" content="([^"]*)"', head)
    return {'title': html.unescape(title.group(1)) if title else None, 'description': meta('name', 'description'),
            'robots': meta('name', 'robots'), 'canonical': canonical.group(1) if canonical else None,
            'ogImage': meta('property', 'og:image'), 'ogImageAlt': meta('property', 'og:image:alt'),
            'crumbCategory': crumb, 'readTime': read.group(1) if read else None}

known = load_all('posts')
known_slugs = {p['slug'] for p in known.values()}
live, page = [], 1
while True:
    body, headers = get(f'{API}posts?per_page=100&page={page}&_fields={POST_FIELDS}', raw=True)
    live += json.loads(body)
    if page >= int(headers.get('X-WP-TotalPages', 1)): break
    page += 1
new = [p for p in live if p['slug'] not in known_slugs]
print(f'live published posts: {len(live)}, already saved: {len(known_slugs)}, new: {len(new)}')
if not new: raise SystemExit(0)

redirected, importable = [], []
for p in new:
    code, to = status(p['link'])
    (redirected if code in (301, 302, 308) else importable).append((p, code, to))
with open(f'{D}/live-redirects.tsv', 'a') as f:
    for p, code, to in redirected: f.write(f"{p['slug']}\t{code}\t{to}\n")

posts = [p for p, _, _ in importable]
json.dump(posts, open(next_file('posts'), 'w'), ensure_ascii=False)

media_ids = sorted({p['featured_media'] for p in posts if p['featured_media']} - set(load_all('media')))
if media_ids:
    json.dump(get(f"{API}media?include={','.join(map(str, media_ids))}&per_page=100&_fields=alt_text,caption,id,mime_type,source_url,title"),
              open(next_file('media'), 'w'), ensure_ascii=False)

tag_ids = sorted({t for p in posts for t in p['tags']} - set(load_all('tags')))
if tag_ids:
    json.dump(get(f"{API}tags?include={','.join(map(str, tag_ids))}&per_page=100&_fields=count,id,link,name,slug"),
              open(next_file('tags'), 'w'), ensure_ascii=False)

cats = json.load(open(f'{D}/categories.json'))
if {c for p in posts for c in p['categories']} - {c['id'] for c in cats}:
    fields = ','.join(cats[0].keys())
    json.dump(get(f'{API}categories?per_page=100&_fields={fields}'), open(f'{D}/categories.json', 'w'), ensure_ascii=False)
    print('categories.json re-fetched (a new post uses a new category): run 03_taxonomy.php')

seo = json.load(open(f'{D}/seo.json'))
for p in posts: seo[p['slug']] = seo_of(p['link'])
json.dump(seo, open(f'{D}/seo.json', 'w'), ensure_ascii=False, indent=1)

print(f'saved {len(posts)} new posts, {len(media_ids)} images, {len(tag_ids)} new tags' + (f'; {len(redirected)} already redirect on live' if redirected else ''))
if tag_ids: print('new tags: run /opt/lampp/bin/php storage/imports/blog/03_taxonomy.php')
print('import: /opt/lampp/bin/php storage/imports/blog/05_import.php --slugs=' + ','.join(p['slug'] for p in posts))
