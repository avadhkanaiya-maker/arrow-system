#!/usr/bin/env python3
"""
Blog: refresh the saved live posts (data/posts-*.json) that were edited on the live site after they were saved
(QA 2026-10-27: e.g. live added a "Planning an in-house workflow?" box to 3 posts on 2026-10-20).
  - each saved post whose live `modified` date is newer is replaced in its own file (same fields)
  - data/media-N.json gets the featured images that are not saved yet (a post may have a new one)
Prints the refreshed slugs; rebuild them with  /opt/lampp/bin/php storage/imports/blog/05_import.php --update --slugs=<slugs>
New posts are 10_fetch_new_posts.py's job. Run from the project root: python3 storage/imports/blog/13_refresh_live_posts.py
"""
import glob, json, os, re, urllib.request

D = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')
API = 'https://arrsys.com/wp-json/wp/v2/'
POST_FIELDS = 'author,categories,content,date,date_gmt,excerpt,featured_media,id,link,modified,modified_gmt,slug,status,tags,title'
UA = {'User-Agent': 'Mozilla/5.0 (Arrow Systems staging migration)'}

def get(url):
    with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=120) as r:
        return json.loads(r.read().decode('utf-8', 'replace')), r.headers

live, page = {}, 1
while True:
    rows, headers = get(f'{API}posts?per_page=100&page={page}&_fields={POST_FIELDS}')
    live.update({p['slug']: p for p in rows})
    if page >= int(headers.get('X-WP-TotalPages', 1)): break
    page += 1

refreshed = []
for f in sorted(glob.glob(f'{D}/posts-*.json')):
    posts = json.load(open(f))
    changed = False
    for i, p in enumerate(posts):
        new = live.get(p['slug'])
        if new and new['modified_gmt'] > p['modified_gmt']:
            posts[i] = new
            refreshed.append(p['slug'])
            changed = True
    if changed: json.dump(posts, open(f, 'w'), ensure_ascii=False)

saved_media = {m['id'] for f in glob.glob(f'{D}/media-*.json') for m in json.load(open(f))}
posts = [p for f in glob.glob(f'{D}/posts-*.json') for p in json.load(open(f))]
media_ids = sorted({p['featured_media'] for p in posts if p['featured_media']} - saved_media)
if media_ids:
    nums = [int(m.group(1)) for f in glob.glob(f'{D}/media-*.json') if (m := re.search(r'-(\d+)\.json$', f))]
    rows, _ = get(f"{API}media?include={','.join(map(str, media_ids))}&per_page=100&_fields=alt_text,caption,id,mime_type,source_url,title")
    if rows: json.dump(rows, open(f'{D}/media-{max(nums, default=0) + 1}.json', 'w'), ensure_ascii=False)
    media_ids = [m['id'] for m in rows]   # private ones are not returned (the import then uses the live og:image)

print(f'live posts: {len(live)}; refreshed {len(refreshed)} saved posts; {len(media_ids)} new featured images')
print(','.join(refreshed))
