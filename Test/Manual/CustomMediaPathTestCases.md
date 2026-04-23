# Custom Media Path Support - Test Cases

## Prerequisites

- Cloudinary extension enabled (Stores > Config > Cloudinary > Enable = Yes)
- Cloudinary credentials configured and valid
- Auto Upload Mapping enabled AND active (toggled to Yes, then clicked "Map media directory")

## Setup

### 1. Deploy changes

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### 2. Create test images

```bash
mkdir -p pub/media/contentmanager/content
mkdir -p pub/media/blog
mkdir -p pub/media/blog/images
mkdir -p pub/media/custommod

# Copy a sample image into each directory
cp pub/media/logo/default/logo.png pub/media/contentmanager/content/test-image.jpg
cp pub/media/logo/default/logo.png pub/media/blog/post-hero.jpg
cp pub/media/logo/default/logo.png pub/media/blog/images/photo.jpg
cp pub/media/logo/default/logo.png pub/media/custommod/banner.jpg
```

(Replace the source with any available image on the server.)

### 3. Create a CMS test page

Go to Content > Pages > Add New Page. Set URL Key to `cloudinary-custom-path-test`.

In the content area, use a **HTML Code** PageBuilder block (or a plain CMS Block via Content > Blocks) and paste:

```html
<h2>Single-level path (blog)</h2>
<img src="{{media url='blog/post-hero.jpg'}}" alt="Blog hero" />

<h2>Two-level path (contentmanager/content)</h2>
<img src="{{media url='contentmanager/content/test-image.jpg'}}" alt="Content manager" />

<h2>Two-level path (blog/images)</h2>
<img src="{{media url='blog/images/photo.jpg'}}" alt="Blog photo" />

<h2>Unconfigured path (should NOT be replaced)</h2>
<img src="{{media url='custommod/banner.jpg'}}" alt="Custom module banner" />

<h2>Image with query string</h2>
<img src="{{media url='contentmanager/content/test-image.jpg'}}?v=12345" alt="With cache buster" />
```

Save and enable the page.

---

## Test Cases

### TC-1: Feature disabled (default state)

**Steps:**
1. Confirm "Enable Custom Media Path Support" is set to **No** under Stores > Config > Cloudinary > Advanced
2. Flush cache: `bin/magento cache:flush`
3. Visit the test CMS page on the frontend
4. Right-click each image > Inspect Element

**Expected:** All `<img>` src attributes point to the local server URL (e.g., `https://your-store.com/media/blog/post-hero.jpg`). No Cloudinary URLs.

---

### TC-2: Enable with a single-level path

**Steps:**
1. Go to Stores > Config > Cloudinary > Advanced
2. Set "Enable Custom Media Path Support" to **Yes**
3. In "Custom Media Paths" enter: `blog`
4. Save Config
5. Flush cache: `bin/magento cache:flush`
6. Visit the test CMS page
7. Inspect the "Single-level path (blog)" image

**Expected:**
- `blog/post-hero.jpg` image src is a Cloudinary URL (e.g., `https://res.cloudinary.com/CLOUD/image/upload/f_auto,q_auto/.../media/blog/post-hero.jpg`)
- The image loads and displays correctly
- All other images remain as local URLs (they are not configured)

---

### TC-3: Enable with a two-level path

**Steps:**
1. Change "Custom Media Paths" to: `contentmanager/content`
2. Save Config, flush cache
3. Visit the test CMS page
4. Inspect the "Two-level path (contentmanager/content)" image

**Expected:**
- `contentmanager/content/test-image.jpg` image src is a Cloudinary URL
- The `blog/post-hero.jpg` image is back to a local URL (no longer configured)

---

### TC-4: Multiple paths

**Steps:**
1. Set "Custom Media Paths" to (one per line):
   ```
   contentmanager/content
   blog
   blog/images
   ```
2. Save Config, flush cache
3. Visit the test CMS page
4. Inspect all images

**Expected:**
- `contentmanager/content/test-image.jpg` — Cloudinary URL
- `blog/post-hero.jpg` — Cloudinary URL
- `blog/images/photo.jpg` — Cloudinary URL
- `custommod/banner.jpg` — Local URL (not configured)

---

### TC-5: Query string handling

**Steps:**
1. With paths configured from TC-4
2. Inspect the "Image with query string" image

**Expected:** The image URL before the `?` is replaced with a Cloudinary URL. The `?v=12345` portion remains after the Cloudinary URL or is handled gracefully. The image loads correctly.

---

### TC-6: Validation — path traversal

**Steps:**
1. In "Custom Media Paths" enter: `../etc`
2. Click Save Config

**Expected:** Error message: `Invalid path "../etc": path traversal ("..") is not allowed.`

---

### TC-7: Validation — invalid characters

**Steps:**
1. In "Custom Media Paths" enter: `path with spaces`
2. Click Save Config

**Expected:** Error message: `Invalid path "path with spaces": only alphanumeric characters, hyphens, underscores, and forward slashes are allowed.`

---

### TC-8: Validation — reserved path (catalog/product)

**Steps:**
1. In "Custom Media Paths" enter: `catalog/product`
2. Click Save Config

**Expected:** Error message: `Path "catalog/product" is already handled by Cloudinary and cannot be added as a custom path.`

---

### TC-9: Validation — reserved path prefix (catalog/category/something)

**Steps:**
1. In "Custom Media Paths" enter: `catalog/category/subcategory`
2. Click Save Config

**Expected:** Error message indicating the path is already handled by Cloudinary.

---

### TC-10: Validation — reserved path (wysiwyg)

**Steps:**
1. In "Custom Media Paths" enter: `wysiwyg`
2. Click Save Config

**Expected:** Error message indicating the path is already handled by Cloudinary.

---

### TC-11: Validation — empty and whitespace lines

**Steps:**
1. In "Custom Media Paths" enter (with blank lines):
   ```
   contentmanager/content

   blog
   
   ```
2. Click Save Config

**Expected:** Save succeeds. Empty lines are stripped. Saved value contains only `contentmanager/content` and `blog`.

---

### TC-12: Validation — leading/trailing slashes

**Steps:**
1. In "Custom Media Paths" enter: `/contentmanager/content/`
2. Click Save Config

**Expected:** Save succeeds. Slashes are stripped. Saved value is `contentmanager/content`.

---

### TC-13: Rollback — disable feature

**Steps:**
1. Set "Enable Custom Media Path Support" to **No**
2. Save Config, flush cache
3. Visit the test CMS page

**Expected:** All images revert to local server URLs. No Cloudinary URLs for custom paths. Existing Cloudinary-served images (product images, logo, etc.) are unaffected.

---

### TC-14: No regression — existing Cloudinary images

**Steps:**
1. Enable the feature with any custom path configured
2. Visit a product page with images
3. Visit a category page with a category image
4. Visit the homepage (logo, CMS blocks)

**Expected:** All existing Cloudinary-served images (product, category, CMS, logo, swatches) continue to work correctly and are not double-processed or broken.

---

### TC-15: Auto Upload Mapping not active

**Steps:**
1. Disable Auto Upload Mapping (set "Enable auto-upload" to No under Cloudinary > Auto Upload)
2. Enable Custom Media Path Support with a valid path
3. Flush cache
4. Visit the test CMS page

**Expected:** Custom path images remain as local URLs. The feature requires auto-upload mapping to be active — without it, the plugin skips processing.

---

### TC-16: Admin panel field visibility

**Steps:**
1. Go to Stores > Config > Cloudinary > Advanced
2. Set "Enable Custom Media Path Support" to **No**

**Expected:** The "Custom Media Paths" textarea is hidden (depends on toggle).

3. Set "Enable Custom Media Path Support" to **Yes**

**Expected:** The "Custom Media Paths" textarea appears.

---

## Image format coverage

When testing TC-2 through TC-4, also verify with different file extensions if possible:
- `.jpg` / `.jpeg`
- `.png`
- `.gif`
- `.webp`
- `.svg`
- `.avif`

All supported formats should be replaced. Non-image files (`.pdf`, `.mp4`, `.html`) should never be matched.
