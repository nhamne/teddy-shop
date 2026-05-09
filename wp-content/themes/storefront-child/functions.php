<?php
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');
function storefront_child_enqueue_styles()
{
    // Gọi CSS của theme gốc
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    // Gọi CSS của child theme
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
}

// Thêm Script tạo Carousel cho lưới sản phẩm Elementor Shortcode
add_action('wp_footer', 'custom_product_carousel_script');
function custom_product_carousel_script()
{
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const carousels = document.querySelectorAll('.elementor-widget-shortcode');
            carousels.forEach(function (widget) {
                const productList = widget.querySelector('ul.products');
                if (!productList) return;

                // Skip if already processed
                if (widget.getAttribute('data-carousel-processed') === 'true') return;
                widget.setAttribute('data-carousel-processed', 'true');

                // Create wrapper with proper structure
                const carouselWrapper = document.createElement('div');
                carouselWrapper.className = 'carousel-wrapper';

                // Create track container
                const trackContainer = document.createElement('div');
                trackContainer.className = 'carousel-track-container';

                // Move product list into track container
                productList.parentNode.insertBefore(carouselWrapper, productList);
                carouselWrapper.appendChild(trackContainer);
                trackContainer.appendChild(productList);

                // Force inline styles to prevent CSS conflicts
                productList.style.setProperty('display', 'flex', 'important');
                productList.style.setProperty('flex-wrap', 'nowrap', 'important');
                productList.style.setProperty('overflow', 'visible', 'important');
                trackContainer.style.setProperty('overflow', 'hidden', 'important');

                // Create navigation buttons
                const prevBtn = document.createElement('button');
                prevBtn.className = 'carousel-btn carousel-btn-prev';
                prevBtn.type = 'button';
                prevBtn.setAttribute('aria-label', 'Previous');
                prevBtn.innerHTML = '&#10094;';
                carouselWrapper.insertBefore(prevBtn, trackContainer);

                const nextBtn = document.createElement('button');
                nextBtn.className = 'carousel-btn carousel-btn-next';
                nextBtn.type = 'button';
                nextBtn.setAttribute('aria-label', 'Next');
                nextBtn.innerHTML = '&#10095;';
                carouselWrapper.appendChild(nextBtn);

                const items = Array.from(productList.querySelectorAll('li.product'));
                let currentIndex = 0;

                const getItemsPerView = function () {
                    if (window.innerWidth <= 480) return 1;
                    if (window.innerWidth <= 768) return 2;
                    if (window.innerWidth <= 1024) return 3;
                    return 4;
                };

                const GAP = 20; // px — phải khớp với gap trong CSS

                // Tính và gán chiều rộng cố định cho từng item dựa theo container
                const resizeItems = function () {
                    const itemsPerView = getItemsPerView();
                    const containerWidth = trackContainer.offsetWidth;
                    const totalGap = GAP * (itemsPerView - 1);
                    const itemWidth = Math.floor((containerWidth - totalGap) / itemsPerView);
                    items.forEach(function (item) {
                        item.style.setProperty('flex', '0 0 ' + itemWidth + 'px', 'important');
                        item.style.setProperty('min-width', itemWidth + 'px', 'important');
                        item.style.setProperty('max-width', itemWidth + 'px', 'important');
                    });
                    return itemWidth;
                };

                const slideTrack = function () {
                    const itemsPerView = getItemsPerView();
                    const maxIndex = Math.max(0, items.length - itemsPerView);
                    currentIndex = Math.min(currentIndex, maxIndex);

                    const itemWidth = resizeItems();
                    const offset = currentIndex * (itemWidth + GAP);
                    productList.style.transform = 'translateX(-' + offset + 'px)';

                    prevBtn.disabled = currentIndex === 0;
                    nextBtn.disabled = currentIndex >= maxIndex;
                };

                nextBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const itemsPerView = getItemsPerView();
                    const maxIndex = Math.max(0, items.length - itemsPerView);
                    currentIndex = Math.min(currentIndex + 1, maxIndex);
                    slideTrack();
                });

                prevBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    currentIndex = Math.max(0, currentIndex - 1);
                    slideTrack();
                });

                window.addEventListener('resize', function () {
                    // Tắt transition tạm thời khi resize để không bị lag
                    productList.style.transition = 'none';
                    currentIndex = 0;
                    slideTrack();
                    // Bật lại transition sau khi resize xong
                    requestAnimationFrame(function () {
                        productList.style.transition = '';
                    });
                }, { passive: true });

                // Initial render (không cần transition lúc load)
                productList.style.transition = 'none';
                slideTrack();
                requestAnimationFrame(function () {
                    productList.style.transition = '';
                });
            });
        });
    </script>
    <?php
}

/* ========================================================
   STICKY NAV — JS SENTINEL PATTERN
   Dính thanh menu lên đầu màn hình khi scroll xuống
======================================================== */
add_action('wp_footer', 'teddy_sticky_nav_script');
function teddy_sticky_nav_script()
{
    ?>
    <script>
    (function () {
        var nav = document.querySelector('.storefront-primary-navigation');
        if (!nav) return;

        // Ghi nhớ vị trí gốc của nav TRƯỚC KHI tạo spacer hoặc thay đổi DOM
        var navOffsetTop = nav.getBoundingClientRect().top + window.pageYOffset;

        // Spacer giữ chỗ khi nav chuyển sang fixed (tránh layout jump)
        var spacer = document.createElement('div');
        spacer.id = 'sticky-nav-spacer';
        spacer.style.cssText = 'display:none;height:0;margin:0;padding:0;border:none;';
        nav.parentNode.insertBefore(spacer, nav);

        var adminBar = document.getElementById('wpadminbar');
        var isSticky = false;

        function getAdminBarHeight() {
            return adminBar ? adminBar.offsetHeight : 0;
        }

        function activate() {
            if (isSticky) return;
            isSticky = true;
            spacer.style.display = 'block';
            spacer.style.height = nav.offsetHeight + 'px';
            nav.classList.add('is-sticky');
        }

        function deactivate() {
            if (!isSticky) return;
            isSticky = false;
            spacer.style.display = 'none';
            spacer.style.height = '0';
            nav.classList.remove('is-sticky');
        }

        function update() {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var adminH = getAdminBarHeight();

            // So sánh với navOffsetTop đã lưu — KHÔNG đọc từ spacer
            if (scrollTop + adminH >= navOffsetTop) {
                activate();
            } else {
                deactivate();
            }
        }

        window.addEventListener('scroll', update, { passive: true });

        window.addEventListener('resize', function () {
            // Khi resize: tắt sticky, tính lại vị trí gốc, rồi check lại
            deactivate();
            navOffsetTop = nav.getBoundingClientRect().top + window.pageYOffset;
            update();
        }, { passive: true });

        // Chạy ngay lần đầu
        update();
    })();
    </script>
    <?php
}

/* ========================================================
   TÍCH HỢP CHATBOT TAWK.TO VÀO FOOTER
======================================================== */
add_action('wp_footer', 'teddy_shop_tawkto_chatbot');
function teddy_shop_tawkto_chatbot()
{
    ?>
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
        (function () {
            var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = 'https://embed.tawk.to/69ff648e825d401c3110707d/1jo6q1jmt';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
    <!--End of Tawk.to Script-->
    <?php
}

/* ========================================================
   TÙY CHỈNH WOOCOMMERCE: XÓA SORTING TOP & DỊCH THUẬT
======================================================== */

// Bỏ Default Sorting và Phân trang ở TRÊN danh sách sản phẩm (giữ lại ở dưới)
add_action( 'wp', 'teddy_remove_top_sorting_pagination' );
function teddy_remove_top_sorting_pagination() {
    remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
    remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
    remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31 );
}

// Dịch chữ "Showing X of Y results" sang Tiếng Việt
add_filter('gettext', 'teddy_translate_woocommerce_strings', 10, 3);
function teddy_translate_woocommerce_strings($translated, $text, $domain) {
    if ($domain === 'woocommerce' || $domain === 'storefront') {
        if ($text === 'Showing the single result') {
            return 'Hiển thị 1 kết quả';
        }
        if (strpos($text, 'Showing all %d results') !== false) {
            return 'Hiển thị tất cả %d kết quả';
        }
        if (strpos($text, 'Showing %1$d&ndash;%2$d of %3$d results') !== false) {
            return 'Hiển thị %1$d&ndash;%2$d trong %3$d kết quả';
        }
    }
    return $translated;
}
?>