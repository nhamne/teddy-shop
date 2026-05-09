# WordPress Teddy Shop - Homepage Product Carousel Implementation

## Summary
The homepage product carousel is implemented using **Elementor's Shortcode widget** to display WooCommerce `[products]` shortcodes, combined with custom CSS and JavaScript from the Storefront Child theme to create the carousel effect.

---

## 1. Template File Rendering Homepage

**Primary Template File:** [wp-content/themes/storefront/template-fullwidth.php](wp-content/themes/storefront/template-fullwidth.php)

```php
<?php
/**
 * The template for displaying full width pages.
 * Template Name: Full width
 */

get_header(); ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php
            while ( have_posts() ) :
                the_post();
                do_action( 'storefront_page_before' );
                get_template_part( 'content', 'page' );
                do_action( 'storefront_page_after' );
            endwhile;
            ?>
        </main>
    </div>
<?php
get_footer();
```

**How it works:**
1. The homepage is set to a **static page** (WordPress Setting: Settings → Reading → "A static page")
2. **Page ID:** 58 (Vietnamese title: "Trang chủ" = Home)
3. **Page Template:** `template-fullwidth.php` (selected in page settings)
4. This template calls `get_template_part('content', 'page')` which renders [wp-content/themes/storefront/content-page.php](wp-content/themes/storefront/content-page.php)
5. The actual content is managed by **Elementor page builder** (detected via `_elementor_edit_mode` post meta)

---

## 2. Product Carousel Shortcodes

The homepage uses **WooCommerce `[products]` shortcode** displayed via Elementor's Shortcode widget.

### Shortcodes Found:

Three instances of the products shortcode in Elementor:

```shortcode
[products ids="164 , 15, 148, 158, 168 " columns="4"]
```
(appears twice - likely for 2 different product carousels)

```shortcode
[products ids="147 , 148, 145 , 146, 149 " columns="4"]
```
(appears once - third carousel)

### Shortcode Details:

**Source:** [wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php](wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php)

```php
'products' => __CLASS__ . '::products',
```

**Attributes:**
- `ids` - Specific product IDs to display
- `columns` - Number of columns (4 in this case)
- Other possible attributes: `orderby`, `order`, `per_page`, `visibility`, `category`, `tag`, etc.

---

## 3. Elementor Page Configuration

**Page Meta Details:**

| Setting | Value |
|---------|-------|
| Page Type | Static Page (ID 58) |
| Post Type | page |
| Template | template-fullwidth.php |
| Elementor Edit Mode | builder |
| Elementor Version | 4.0.6 |
| Page Builder | Elementor |

### Elementor Widgets on Homepage:

```
- image-carousel: 1 (for banner images at top)
- image: 7 (various images/banners)
- shortcode: 3 (the product carousels)
- e-button: 2 (call-to-action buttons)
```

**Elementor Shortcode Widgets Location:**
[wp-content/themes/storefront/index.php](wp-content/themes/storefront/index.php) and rendered through Elementor's rendering system

---

## 4. Product Carousel Implementation (CSS & JavaScript)

### Custom Carousel Script

**Location:** [wp-content/themes/storefront-child/functions.php](wp-content/themes/storefront-child/functions.php) (lines 13-75)

```php
// Thêm Script tạo Carousel cho lưới sản phẩm Elementor Shortcode
add_action('wp_footer', 'custom_product_carousel_script');
function custom_product_carousel_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousels = document.querySelectorAll('.elementor-widget-shortcode');
        carousels.forEach(function(widget) {
            if (widget.dataset.carouselEnhanced === '1') return;

            const productList = widget.querySelector('ul.products');
            if (!productList) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'custom-carousel-wrapper';
            
            const prevBtn = document.createElement('button');
            prevBtn.className = 'custom-carousel-prev';
            prevBtn.type = 'button';
            prevBtn.innerHTML = '&#10094;'; // Left arrow

            const nextBtn = document.createElement('button');
            nextBtn.className = 'custom-carousel-next';
            nextBtn.type = 'button';
            nextBtn.innerHTML = '&#10095;'; // Right arrow

            // Restructure DOM to create carousel container
            productList.parentNode.insertBefore(wrapper, productList);
            wrapper.appendChild(prevBtn);
            
            const trackContainer = document.createElement('div');
            trackContainer.className = 'custom-carousel-track-container';
            wrapper.appendChild(trackContainer);
            trackContainer.appendChild(productList);
            wrapper.appendChild(nextBtn);

            // Scroll handler
            const getScrollStep = function() {
                return trackContainer.clientWidth;
            };

            // Update button states
            const updateButtons = function() {
                const maxScrollLeft = trackContainer.scrollWidth - trackContainer.clientWidth;
                prevBtn.disabled = trackContainer.scrollLeft <= 1;
                nextBtn.disabled = trackContainer.scrollLeft >= (maxScrollLeft - 1);
            };

            nextBtn.addEventListener('click', function() {
                trackContainer.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
            });
            
            prevBtn.addEventListener('click', function() {
                trackContainer.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
            });

            trackContainer.addEventListener('scroll', updateButtons, { passive: true });
            window.addEventListener('resize', updateButtons);

            widget.dataset.carouselEnhanced = '1';
            updateButtons();
        });
    });
    </script>
    <?php
}
```

**How it works:**
1. Targets all `.elementor-widget-shortcode` elements (Elementor's shortcode widget wrapper)
2. Finds the `ul.products` (WooCommerce product list) inside each shortcode widget
3. Creates a carousel wrapper with prev/next buttons
4. Implements smooth horizontal scrolling using CSS `scroll-behavior: smooth`
5. Navigation buttons are disabled when at the start/end of the carousel

### Custom Carousel Styling

**Location:** [wp-content/themes/storefront-child/style.css](wp-content/themes/storefront-child/style.css) (lines 189-275)

```css
/* Carousel Container Wrapper */
.custom-carousel-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

/* Scrollable Container */
.custom-carousel-track-container {
  overflow-x: auto;
  scroll-behavior: smooth;
  scroll-snap-type: x mandatory;
  scrollbar-width: none; /* Hide scrollbar */
  -ms-overflow-style: none;
  width: 100%;
}

/* Hide scrollbar (Webkit browsers) */
.custom-carousel-track-container::-webkit-scrollbar {
  display: none;
}

/* WooCommerce Product List */
.elementor-widget-shortcode ul.products {
  display: flex !important;
  flex-wrap: nowrap !important;
  margin: 0 !important;
  padding: 10px 5px !important;
  gap: 20px;
  overflow: visible !important;
}

/* Remove pseudo-elements to prevent layout issues */
.elementor-widget-shortcode ul.products::before,
.elementor-widget-shortcode ul.products::after {
  display: none !important;
}

/* Individual Product Item */
.elementor-widget-shortcode ul.products li.product {
  flex: 0 0 calc(25% - 15px) !important;
  max-width: calc(25% - 15px) !important;
  margin-bottom: 0 !important;
  clear: none !important;
  scroll-snap-align: start;
}

/* Responsive: Tablet (1024px) */
@media (max-width: 1024px) {
  .elementor-widget-shortcode ul.products li.product {
    flex: 0 0 calc(33.333% - 14px) !important;
    max-width: calc(33.333% - 14px) !important;
  }
}

/* Responsive: Small Tablet (768px) */
@media (max-width: 768px) {
  .elementor-widget-shortcode ul.products li.product {
    flex: 0 0 calc(50% - 10px) !important;
    max-width: calc(50% - 10px) !important;
  }
}

/* Responsive: Mobile (480px) */
@media (max-width: 480px) {
  .elementor-widget-shortcode ul.products li.product {
    flex: 0 0 80% !important;
    max-width: 80% !important;
  }
}

/* Navigation Buttons */
.custom-carousel-prev,
.custom-carousel-next {
  background-color: #fff;
  border: 1px solid #eef2f5;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  color: #333;
  font-size: 18px;
  transition: all 0.3s ease;
  line-height: 1;
}
```

---

## 5. Related Theme Hooks (Storefront Framework)

Although the homepage uses Elementor, the Storefront theme still has these hooks that could interfere if they were called:

**Location:** [wp-content/themes/storefront/inc/woocommerce/storefront-woocommerce-template-hooks.php](wp-content/themes/storefront/inc/woocommerce/storefront-woocommerce-template-hooks.php)

```php
add_action( 'homepage', 'storefront_product_categories', 20 );
add_action( 'homepage', 'storefront_recent_products', 30 );
add_action( 'homepage', 'storefront_featured_products', 40 );
add_action( 'homepage', 'storefront_popular_products', 50 );
add_action( 'homepage', 'storefront_on_sale_products', 60 );
add_action( 'homepage', 'storefront_best_selling_products', 70 );
add_action( 'homepage', 'storefront_woocommerce_brands_homepage_section', 80 );
```

**Note:** These hooks are NOT called because:
- The homepage uses the `template-fullwidth.php` template, which does NOT call the `homepage` action
- Elementor completely overrides the page rendering through the `storefront_page_content` hook
- The `homepage` action is only called by `template-homepage.php` (which is not used for this site's homepage)

---

## 6. Active Plugins

| Plugin | Purpose |
|--------|---------|
| elementor/elementor.php | Page builder for homepage design |
| woocommerce/woocommerce.php | E-commerce functionality, product display, [products] shortcode |
| snapchat-for-woocommerce/snapchat-for-woocommerce.php | Social media integration |
| tiktok-for-business/tiktok-for-woocommerce.php | Social media integration |

**No dedicated carousel plugins** - The carousel is implemented entirely through:
1. Elementor's shortcode widget rendering the [products] shortcode
2. Custom CSS and JavaScript in the Storefront Child theme

---

## 7. Key File Paths Summary

| File | Purpose |
|------|---------|
| [wp-content/themes/storefront/template-fullwidth.php](wp-content/themes/storefront/template-fullwidth.php) | Main homepage template |
| [wp-content/themes/storefront/content-page.php](wp-content/themes/storefront/content-page.php) | Page content wrapper |
| [wp-content/themes/storefront-child/functions.php](wp-content/themes/storefront-child/functions.php) | Custom carousel JavaScript (lines 13-75) |
| [wp-content/themes/storefront-child/style.css](wp-content/themes/storefront-child/style.css) | Carousel CSS styling (lines 189-275) |
| [wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php](wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php) | WooCommerce [products] shortcode definition |
| [wp-content/plugins/elementor/](wp-content/plugins/elementor/) | Elementor page builder plugin |

---

## 8. Database Configuration

- **Database:** `teddy_db`
- **Hostname:** `localhost`
- **Homepage Setting:** `show_on_front` = `page`, `page_on_front` = `58`

---

## Product Display Flow Diagram

```
User visits homepage
        ↓
WordPress loads page ID 58 ("Trang chủ")
        ↓
template-fullwidth.php is used (page template setting)
        ↓
get_template_part('content', 'page') loads content-page.php
        ↓
do_action('storefront_page') is called
        ↓
Elementor hooks into storefront_page_content (priority 20)
        ↓
Elementor renders 8 widgets:
    - 1 image-carousel widget (banner)
    - 7 image widgets
    - 3 shortcode widgets [products]
    - 2 button widgets
        ↓
Each [products] shortcode is processed by WooCommerce
        ↓
Products are displayed as ul.products list
        ↓
Child theme JavaScript wraps each in custom carousel
        ↓
Child theme CSS styles carousel with flexbox + scroll
        ↓
Navigation buttons allow prev/next scrolling
```
