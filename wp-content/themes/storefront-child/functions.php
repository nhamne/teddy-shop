<?php
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');
function storefront_child_enqueue_styles()
{
    // Gọi CSS của theme gốc
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    // Gọi CSS của child theme với filemtime để tránh cache
    $version = filemtime(get_stylesheet_directory() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'), $version);
}

/* ========================================================
   INJECT CSS CĂN TRÁI BREADCRUMB & TIÊU ĐỀ TRANG
   Dùng wp_head priority 9999 để chạy SAU tất cả CSS khác
   (kể cả Customizer, plugin, inline styles của theme)
======================================================== */
add_action('wp_head', 'teddy_force_left_align_css', 9999);
function teddy_force_left_align_css()
{
    ?>
    <style id="teddy-left-align-override">
        /* Căn trái breadcrumb (Trang chủ > Thú bông > ...) */
        .storefront-breadcrumb,
        .woocommerce-breadcrumb {
            text-align: left !important;
        }

        /* Căn trái tiêu đề trang danh mục sản phẩm */
        .woocommerce-products-header,
        .woocommerce-products-header__title,
        h1.woocommerce-products-header__title {
            text-align: left !important;
        }

        /* Căn trái tiêu đề trang thông thường (Liên hệ, v.v.) */
        .entry-title,
        .page-title,
        h1.entry-title,
        h1.page-title {
            text-align: left !important;
        }

        /* Ghi đè rule của Storefront trong @media (min-width: 768px) */
        @media (min-width: 768px) {
            .storefront-full-width-content .woocommerce-products-header,
            .storefront-full-width-content .woocommerce-products-header__title,
            .storefront-full-width-content.woocommerce-cart .entry-header,
            .storefront-full-width-content.woocommerce-checkout .entry-header,
            .storefront-full-width-content.woocommerce-account .entry-header,
            .storefront-full-width-content .entry-header,
            .storefront-full-width-content .entry-title,
            .storefront-full-width-content .page-title {
                text-align: left !important;
                padding: 1em 0 !important;
            }

            .storefront-full-width-content .storefront-breadcrumb,
            .storefront-full-width-content .woocommerce-breadcrumb {
                text-align: left !important;
            }
        }
    </style>
    <?php
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
add_action('wp', 'teddy_remove_top_sorting_pagination');
function teddy_remove_top_sorting_pagination()
{
    remove_action('woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30);
    remove_action('woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31);
}

// Dịch chữ và các chuỗi WooCommerce sang Tiếng Việt
add_filter('gettext', 'teddy_translate_woocommerce_strings', 10, 3);
function teddy_translate_woocommerce_strings($translated, $text, $domain)
{
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
        // Dịch cho trang Giỏ hàng
        if ($text === 'Cart') return 'Giỏ hàng';
        if ($text === 'Product') return 'Sản phẩm';
        if ($text === 'Price') return 'Giá';
        if ($text === 'Quantity') return 'Số lượng';
        if ($text === 'Subtotal') return 'Tạm tính';
        if ($text === 'Cart totals') return 'Tổng cộng giỏ hàng';
        if ($text === 'Total') return 'Tổng';
        if ($text === 'Proceed to checkout') return 'Tiến hành thanh toán';
        if ($text === 'Apply coupon') return 'Áp dụng';
        if ($text === 'Coupon code') return 'Nhập mã giảm giá';
        if ($text === 'Return to shop') return 'Tiếp tục xem sản phẩm';
        if ($text === 'Free shipping') return 'Phí vận chuyển';
    }
    return $translated;
}

// Đổi tên trang Cart và Checkout (vì WooCommerce Blocks có thể không dùng chuỗi dịch chuẩn)
add_filter('the_title', 'teddy_rename_cart_checkout_title', 10, 2);
function teddy_rename_cart_checkout_title($title, $id = null)
{
    if (is_page() && in_the_loop()) {
        if ($title === 'Cart') {
            return 'GIỎ HÀNG';
        }
        if ($title === 'Checkout') {
            return 'THANH TOÁN';
        }
    }
    return $title;
}

/* ============================================================
   TÙY BIẾN TEDDY SHOP - LUCKY WHEEL + TỰ ĐỘNG ÁP MÃ
   ============================================================ */

// --- 1. XỬ LÝ THÊM QUÀ VẬT LÝ QUA AJAX ---
if (!function_exists('nhi_add_gift_to_cart_handler')) {
    add_action('wp_ajax_nhi_add_gift_to_cart', 'nhi_add_gift_to_cart_handler');
    add_action('wp_ajax_nopriv_nhi_add_gift_to_cart', 'nhi_add_gift_to_cart_handler');
    function nhi_add_gift_to_cart_handler()
    {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if ($product_id > 0) {
            WC()->cart->add_to_cart($product_id);
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}

// --- 2. INJECT COUPON_CODE VÀO RESPONSE CỦA PLUGIN ---
// Đọc mã coupon thật từ database (post meta wlwl_email_coupons) thay vì parse HTML
add_filter('woo_lucky_wheel_get_email_response', 'nhi_inject_coupon_code_to_response', 10, 4);
function nhi_inject_coupon_code_to_response($data, $email, $name, $mobile)
{
    if (!isset($data['result']) || $data['result'] !== 'win') {
        return $data;
    }
    // Tìm bản ghi email trong database của plugin
    $email_posts = get_posts([
        'post_type' => 'wlwl_email',
        'title' => $email,
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);
    if (!empty($email_posts)) {
        $coupons = get_post_meta($email_posts[0]->ID, 'wlwl_email_coupons', true);
        if (is_array($coupons) && !empty($coupons)) {
            // Lấy mã coupon MỚI NHẤT (cuối mảng)
            $latest_coupon = end($coupons);
            $data['coupon_code'] = strtolower(trim($latest_coupon));
        }
    }
    return $data;
}

// --- 3. ENDPOINT AJAX ĐỂ ÁP MÃ COUPON AN TOÀN TỪ SERVER ---
add_action('wp_ajax_nhi_apply_lucky_coupon', 'nhi_apply_lucky_coupon_handler');
add_action('wp_ajax_nopriv_nhi_apply_lucky_coupon', 'nhi_apply_lucky_coupon_handler');
function nhi_apply_lucky_coupon_handler()
{
    // Kiểm tra nonce bảo mật
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'nhi_lucky_coupon_nonce')) {
        wp_send_json_error(['message' => 'Bảo mật thất bại.']);
        return;
    }
    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field(wp_unslash($_POST['coupon_code'])) : '';
    if (empty($coupon_code)) {
        wp_send_json_error(['message' => 'Mã coupon rỗng.']);
        return;
    }
    // Kiểm tra mã coupon có tồn tại không
    $coupon = new WC_Coupon($coupon_code);
    if (!$coupon->get_id()) {
        wp_send_json_error(['message' => 'Mã coupon không hợp lệ: ' . $coupon_code]);
        return;
    }
    // Áp mã vào giỏ hàng WooCommerce
    if (WC()->cart->has_discount($coupon_code)) {
        wp_send_json_success(['message' => 'Mã đã được áp trước đó.', 'already_applied' => true]);
        return;
    }

    // --- XÓA VĩNH VIỄN CÁC RESTRICTION TRƯỜC KHI ÁP ---
    // Coupon này unique (usage_limit=1), khách đã thắng hợp lệ.
    // Nếu khôi phục email_restriction, WooCommerce sẽ tự xóa mã khi trang reload
    // vì billing email chưa điền → giải pháp: xóa hẳn restriction.
    $coupon->set_email_restrictions([]);      // Xóa khóa email
    $coupon->set_limit_usage_to_x_items(0);  // Áp cho toàn bộ giỏ hàng
    $coupon->set_minimum_amount('');          // Không giới hạn min spend
    $coupon->save();

    $result = WC()->cart->apply_coupon($coupon_code);
    // Không khôi phục — để WooCommerce không re-validate và xóa coupon khi reload

    if ($result) {
        wp_send_json_success(['message' => 'Áp mã thành công!', 'coupon' => $coupon_code]);
    } else {
        // Lấy lỗi từ WooCommerce notices
        $notices = wc_get_notices('error');
        $error_msg = !empty($notices) ? strip_tags($notices[0]['notice']) : 'Không thể áp mã.';
        wc_clear_notices();
        wp_send_json_error(['message' => $error_msg]);
    }
}

// --- 4. LOGIC TỔNG HỢP VÒNG QUAY ---
add_action('wp_footer', 'nhi_teddy_lucky_wheel_logic', 9999);
function nhi_teddy_lucky_wheel_logic()
{
    if (!is_checkout()) {
        echo '<style>.woocommerce-lucky-wheel-popup-wrapper, .wlwl_wheel_icon, .wlwl-overlay { display: none !important; }</style>';
        return;
    }

    $cart_total = (is_object(WC()->cart)) ? WC()->cart->get_subtotal() : 0;
    if ($cart_total < 500000) {
        echo '<style>.woocommerce-lucky-wheel-popup-wrapper, .wlwl_wheel_icon, .wlwl-overlay { display: none !important; }</style>';
        return;
    }

    $ajax_url = admin_url('admin-ajax.php');
    $nonce_val = wp_create_nonce('nhi_lucky_coupon_nonce');
    ?>
    <style>
        .wlwl-never-again,
        .wlwl-remember-later,
        .wlwl_no_thanks,
        .wlwl-close-bottom {
            display: none !important;
        }

        .wlwl_wheel_icon {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: auto !important;
        }

        .wlwl_wheel_icon.nhi-show-icon {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }

        /* Thông báo áp mã tự động */
        #nhi-coupon-notice {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: #fff;
            padding: 14px 28px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 6px 24px rgba(255, 20, 147, 0.4);
            z-index: 99999;
            display: none;
            white-space: nowrap;
            animation: nhi-bounce-in 0.4s ease;
        }

        @keyframes nhi-bounce-in {
            0% {
                transform: translateX(-50%) translateY(30px);
                opacity: 0;
            }

            70% {
                transform: translateX(-50%) translateY(-5px);
                opacity: 1;
            }

            100% {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }
    </style>

    <div id="nhi-coupon-notice">🎉 Đã áp mã giảm giá thành công!</div>

    <script>
        (function ($) {
            var NHI_AJAX = '<?php echo esc_js($ajax_url); ?>';
            var NHI_NONCE = '<?php echo esc_js($nonce_val); ?>';
            var GIFT_ID = 165; // << NHI THAY ID SẢN PHẨM QUÀ VÀO ĐÂY

            // ================================================================
            // A. ÁP MÃ TỰ ĐỘNG KHI TRANG CHECKOUT LOAD (nếu có mã trong session)
            // ================================================================
            var savedCoupon = sessionStorage.getItem('nhi_lucky_coupon');
            if (savedCoupon) {
                // Gọi AJAX endpoint PHP để áp mã an toàn từ server
                $.ajax({
                    type: 'POST',
                    url: NHI_AJAX,
                    data: {
                        action: 'nhi_apply_lucky_coupon',
                        coupon_code: savedCoupon,
                        nonce: NHI_NONCE
                    },
                    success: function (response) {
                        if (response.success) {
                            sessionStorage.removeItem('nhi_lucky_coupon');
                            // Hiển thị thông báo đẹp
                            var $notice = $('#nhi-coupon-notice');
                            $notice.text('\ud83c\udf89 Đã áp mã "' + savedCoupon.toUpperCase() + '" giảm giá thành công!').fadeIn(400);
                            setTimeout(function () { $notice.fadeOut(600); }, 4500);

                            // Reload sau 1.5s — coupon không còn email restriction nên không bị xóa
                            setTimeout(function () { location.reload(); }, 1500);
                        } else {
                            console.warn('[Lucky Wheel] Không thể áp mã:', response.data ? response.data.message : 'Lỗi không xác định');
                            // Fallback: thử áp bằng DOM nếu server thất bại
                            nhi_apply_coupon_via_dom(savedCoupon);
                        }
                    },
                    error: function () {
                        console.warn('[Lucky Wheel] AJAX thất bại, thử fallback DOM...');
                        nhi_apply_coupon_via_dom(savedCoupon);
                    }
                });
            }

            // Fallback: áp mã qua DOM (WooCommerce Block checkout)
            function nhi_apply_coupon_via_dom(coupon) {
                var attempts = 0;
                var checkExist = setInterval(function () {
                    attempts++;
                    if (attempts > 40) { clearInterval(checkExist); return; } // timeout 20s

                    // Mở ô nhập coupon nếu đang đóng
                    var toggleBtn = document.querySelector('.wc-block-components-totals-coupon__button');
                    if (toggleBtn && toggleBtn.getAttribute('aria-expanded') === 'false') {
                        toggleBtn.click();
                        return;
                    }

                    var input = document.getElementById('wc-block-components-totals-coupon__input-coupon');
                    var btn = document.querySelector('.wc-block-components-totals-coupon__form button[type="submit"]')
                        || document.querySelector('.wc-block-components-totals-coupon__form button');

                    if (input && btn) {
                        clearInterval(checkExist);
                        // Inject giá trị vào React input
                        var nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                        nativeSetter.call(input, coupon);
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));

                        setTimeout(function () {
                            btn.removeAttribute('disabled');
                            btn.setAttribute('aria-disabled', 'false');
                            btn.style.pointerEvents = 'auto';
                            btn.click();
                            sessionStorage.removeItem('nhi_lucky_coupon');
                            console.log('[Lucky Wheel] Đã áp mã qua DOM:', coupon);
                        }, 800);
                    }
                }, 500);
            }

            // ================================================================
            // B. TỰ MỞ VÒNG QUAY SAU 2 GIÂY
            // ================================================================
            setTimeout(function () {
                if (window.woocommerce_lucky_wheel_free) {
                    window.woocommerce_lucky_wheel_free.open_wheel();
                } else {
                    var icon = document.querySelector('.wlwl_wheel_icon');
                    if (icon) icon.click();
                }
            }, 2000);

            // ================================================================
            // C. BẮT COUPON KHI PLUGIN XỬ LÝ XONG (HOOK VÀO AJAX SUCCESS)
            // Plugin gọi spins_wheel() -> hiển thị result_notification
            // Ta monkey-patch XMLHttpRequest để bắt response
            // ================================================================
            var origOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function (method, url) {
                this._nhi_url = url;
                origOpen.apply(this, arguments);
            };
            var origSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (body) {
                var xhr = this;
                var origOnLoad = xhr.onload;
                xhr.addEventListener('load', function () {
                    // Chỉ xử lý request của woo-lucky-wheel
                    if (xhr._nhi_url && (xhr._nhi_url.indexOf('wlwl_get_email') !== -1 || xhr._nhi_url.indexOf('woocommerce_lucky_wheel') !== -1)) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            // Plugin trả về coupon_code mà ta đã inject qua PHP filter
                            if (resp && resp.allow_spin === 'yes' && resp.coupon_code) {
                                var code = resp.coupon_code;
                                console.log('[Lucky Wheel] Trúng mã coupon:', code);
                                sessionStorage.setItem('nhi_lucky_coupon', code);

                                // Xử lý quà vật lý (sticker/gấu/móc khóa)
                                var prizeName = (resp.result_notification || '').toLowerCase();
                                if (prizeName.indexOf('sticker') !== -1 || prizeName.indexOf('gấu') !== -1 || prizeName.indexOf('móc khóa') !== -1) {
                                    $.ajax({
                                        type: 'POST',
                                        url: NHI_AJAX,
                                        data: { action: 'nhi_add_gift_to_cart', product_id: GIFT_ID },
                                        success: function () { /* reload sẽ xảy ra sau */ }
                                    });
                                }
                            }
                        } catch (e) { /* JSON parse error, bỏ qua */ }
                    }
                });
                origSend.apply(this, arguments);
            };

            // ================================================================
            // D. THÊM NÚT "CHECKOUT NOW" VÀO POPUP KHI QUAY XONG
            // ================================================================
            $(document).on('DOMNodeInserted', '.wlwl_user_lucky', function () {
                // Kiểm tra xem đã có kết quả chưa
                if ($(this).find('.wlwl-frontend-result').text().trim() !== '' &&
                    !$(this).find('#nhi-checkout-now-btn').length) {
                    var code = sessionStorage.getItem('nhi_lucky_coupon');
                    if (code) {
                        var $btn = $('<a id="nhi-checkout-now-btn" class="button" style="display:block;margin-top:14px;text-align:center;background:linear-gradient(135deg,#ff69b4,#ff1493);color:#fff;border-radius:999px;padding:12px 24px;font-weight:700;text-decoration:none;">🛒 Checkout Now (áp mã tự động)</a>');
                        $btn.attr('href', '<?php echo esc_js(wc_get_checkout_url()); ?>');
                        $(this).append($btn);
                    }
                }
            });

            // ================================================================
            // E. CHẶN NÚT THOÁT VÒNG QUAY
            // ================================================================
            document.addEventListener('click', function (e) {
                var closeBtn = e.target.closest('.wlwl-close-wheel');
                if (closeBtn) {
                    var code = sessionStorage.getItem('nhi_lucky_coupon');
                    if (code && !confirm('Thoát là mất mã giảm giá "' + code.toUpperCase() + '" đó! Bạn có chắc không?')) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    } else if (!code) {
                        var icon = document.querySelector('.wlwl_wheel_icon');
                        if (icon) icon.classList.add('nhi-show-icon');
                    }
                }
            }, true);

        }(jQuery));
    </script>
    <?php
}

/* ========================================================
   XÓA SIDEBAR Ở CÁC TRANG (NGOẠI TRỪ TRANG SHOP & CATEGORY)
======================================================== */
add_action('wp', 'teddy_remove_sidebar_logic', 99);
function teddy_remove_sidebar_logic()
{
    // Nếu không phải là trang Cửa hàng, Danh mục sản phẩm, Từ khóa sản phẩm, và Kết quả tìm kiếm
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
        // Bỏ sidebar của Storefront
        remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    }
}

// Bổ sung class full-width cho các trang đã bị xóa sidebar để giao diện tràn viền
add_filter('body_class', 'teddy_force_full_width_body_class', 99);
function teddy_force_full_width_body_class($classes)
{
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
        $classes[] = 'storefront-full-width-content';

        // Xóa class right-sidebar hoặc left-sidebar nếu có
        $key_right = array_search('right-sidebar', $classes);
        if (false !== $key_right) {
            unset($classes[$key_right]);
        }
        $key_left = array_search('left-sidebar', $classes);
        if (false !== $key_left) {
            unset($classes[$key_left]);
        }
    }
    return $classes;
}

/* ========================================================
   THÊM NÚT CỘNG/TRỪ (+ / -) CHO Ô SỐ LƯỢNG SẢN PHẨM
======================================================== */
add_action('wp_footer', 'teddy_add_quantity_plus_minus');
function teddy_add_quantity_plus_minus() {
    // Chỉ chạy ở trang sản phẩm hoặc giỏ hàng
    if (!is_product() && !is_cart()) return;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Chèn nút - và + vào trước và sau input số lượng
            $('form.cart .quantity, .woocommerce-cart-form .quantity').each(function() {
                if (!$(this).hasClass('buttons_added')) {
                    $(this).addClass('buttons_added')
                           .prepend('<input type="button" value="-" class="minus" />')
                           .append('<input type="button" value="+" class="plus" />');
                }
            });

            // Xử lý sự kiện click
            $(document).on('click', '.plus, .minus', function() {
                var $qty = $(this).siblings('.qty');
                var currentVal = parseFloat($qty.val());
                var max = parseFloat($qty.attr('max'));
                var min = parseFloat($qty.attr('min'));
                var step = $qty.attr('step');

                // Giá trị mặc định
                if (!currentVal || currentVal === '' || currentVal === 'NaN') currentVal = 0;
                if (max === '' || max === 'NaN') max = '';
                if (min === '' || min === 'NaN') min = 0;
                if (step === 'any' || step === '' || step === undefined || parseFloat(step) === 'NaN') step = 1;

                if ($(this).is('.plus')) {
                    if (max && (max == currentVal || currentVal > max)) {
                        $qty.val(max);
                    } else {
                        $qty.val(currentVal + parseFloat(step));
                    }
                } else {
                    if (min && (min == currentVal || currentVal < min)) {
                        $qty.val(min);
                    } else if (currentVal > 0) {
                        $qty.val(currentVal - parseFloat(step));
                    }
                }
                $qty.trigger('change');
            });
        });
    </script>
    <?php
}

/* ========================================================
   SHORTCODE TẠO TIÊU ĐỀ TRANG DÀNH CHO ELEMENTOR
   Dùng shortcode: [teddy_page_header title="Tin tức"]
======================================================== */
add_shortcode('teddy_page_header', 'teddy_page_header_shortcode');
function teddy_page_header_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => get_the_title(),
    ), $atts, 'teddy_page_header');

    ob_start();
    ?>
    <header class="entry-header teddy-elementor-header">
        <h1 class="entry-title"><?php echo esc_html($atts['title']); ?></h1>
    </header>
    <?php
    return ob_get_clean();
}// ==========================================
// PHẦN 1: SHORTCODE FORM ĐĂNG NHẬP [custom_admin_login]
// ==========================================
add_action('template_redirect', 'ts_process_admin_login_action');
function ts_process_admin_login_action() {
    global $ts_login_error_message;
    $ts_login_error_message = '';

    if (isset($_POST['ts_admin_login_submit'])) {
        if (isset($_POST['ts_admin_login_nonce']) && wp_verify_nonce($_POST['ts_admin_login_nonce'], 'ts_admin_login_action')) {
            $creds = array(
                'user_login'    => sanitize_user($_POST['ts_username']),
                'user_password' => $_POST['ts_password'],
                'remember'      => true
            );
            
            $user = wp_signon($creds, is_ssl());
            
            if (is_wp_error($user)) {
                $ts_login_error_message = "Tên đăng nhập hoặc mật khẩu không đúng.";
            } else {
                if (in_array('administrator', (array) $user->roles)) {
                    // Redirect bằng PHP an toàn vì hook chạy trước khi HTML xuất ra
                    wp_safe_redirect(home_url('/bang-dieu-khien-admin'));
                    exit;
                } else {
                    wp_logout();
                    $ts_login_error_message = "Lỗi: Khách hàng không có quyền truy cập trang quản trị này.";
                }
            }
        } else {
            $ts_login_error_message = "Lỗi bảo mật. Vui lòng thử lại.";
        }
    }
}

add_shortcode('custom_admin_login', 'ts_custom_admin_login_shortcode');
function ts_custom_admin_login_shortcode() {
    ob_start();
    
    global $ts_login_error_message;
    $login_error = $ts_login_error_message;

    // BƯỚC 2: NẾU ĐÃ ĐĂNG NHẬP VÀ LÀ ADMIN THÌ ẨN FORM
    if (is_user_logged_in() && current_user_can('administrator')) {
        echo '<p style="text-align:center;">Bạn đã đăng nhập với quyền Admin. <a href="'.esc_url(home_url('/bang-dieu-khien-admin')).'">Vào Bảng điều khiển</a></p>';
        return ob_get_clean();
    }

    // BƯỚC 3: GIAO DIỆN HTML & CSS CHO FORM
    ?>
    <style>
        .ts-login-container { max-width: 400px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: sans-serif; }
        .ts-login-container h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .ts-login-group { margin-bottom: 15px; }
        .ts-login-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .ts-login-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .ts-btn-login { width: 100%; padding: 14px; background-color: #ff69b4; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .ts-btn-login:hover { background-color: #ff1493; }
        .ts-error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 14px;}
    </style>

    <div class="ts-login-container">
        <h2>Đăng Nhập Quản Trị</h2>
        <?php if ($login_error) : ?>
            <div class="ts-error"><?php echo esc_html($login_error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <!-- Tạo trường Nonce để chống CSRF Attack -->
            <?php wp_nonce_field('ts_admin_login_action', 'ts_admin_login_nonce'); ?>
            <div class="ts-login-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="ts_username" required placeholder="Nhập username...">
            </div>
            <div class="ts-login-group">
                <label>Mật khẩu</label>
                <input type="password" name="ts_password" required placeholder="Nhập mật khẩu...">
            </div>
            <button type="submit" name="ts_admin_login_submit" class="ts-btn-login">Đăng Nhập</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// ==========================================
// PHẦN 2: BẢNG ĐIỀU KHIỂN MEGA ADMIN [frontend_dashboard]
// ==========================================
add_shortcode('frontend_dashboard', 'ts_mega_dashboard_shortcode');
function ts_mega_dashboard_shortcode() {
    // 1. Kiểm tra quyền
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        return '<div style="background:#ffebee; color:#d32f2f; padding:20px; text-align:center; font-weight:bold;"> Lỗi: Từ chối truy cập! </div>';
    }
    
    // 2. Định tuyến (Router)
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
    
    ob_start();
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap');
        .ts-dash-wrapper { display: flex; gap: 30px; font-family: 'Nunito', sans-serif; flex-wrap: wrap; background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); padding: 35px; border-radius: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .ts-sidebar { width: 250px; background: rgba(255,255,255,0.6); backdrop-filter: blur(20px); padding: 30px; border-radius: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.03); align-self: start; border: 1px solid rgba(255,255,255,0.9); }
        .ts-sidebar a { display: block; padding: 16px 22px; margin-bottom: 15px; color: #666; text-decoration: none; border-radius: 20px; font-weight: 800; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .ts-sidebar a.active, .ts-sidebar a:hover { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); color: #fff; box-shadow: 0 10px 25px rgba(255, 154, 158, 0.4); transform: translateX(8px); }
        .ts-content { flex: 1; min-width: 320px; background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); padding: 45px; border-radius: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.03); border: 1px solid rgba(255,255,255,0.9); }
        
        .ts-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 30px; font-size: 15px;}
        .ts-table th { background: rgba(255, 255, 255, 0.8); padding: 20px; text-align: left; font-weight: 800; color: #555; border-bottom: 2px solid rgba(0,0,0,0.03); }
        .ts-table th:first-child { border-top-left-radius: 20px; border-bottom-left-radius: 20px; }
        .ts-table th:last-child { border-top-right-radius: 20px; border-bottom-right-radius: 20px; }
        .ts-table td { padding: 20px; border-bottom: 1px solid rgba(0,0,0,0.03); vertical-align: middle; transition: background 0.3s; }
        .ts-table tr:hover td { background: rgba(255, 255, 255, 0.9); }
        .ts-table img { border-radius: 15px; object-fit: cover; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        .ts-btn { padding: 12px 24px; border: none; border-radius: 20px; cursor: pointer; color: #fff; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 14px; font-weight: 800; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .ts-btn:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 12px 25px rgba(0,0,0,0.15); }
        .ts-btn-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .ts-btn-red { background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); }
        .ts-btn-gray { background: linear-gradient(135deg, #a8abad 0%, #ccd2d6 100%); }
        
        .ts-form-group { margin-bottom: 25px; }
        .ts-form-group label { display: block; margin-bottom: 12px; font-weight: 800; color: #555;}
        .ts-form-group input, .ts-form-group textarea, .ts-form-group select { width: 100%; padding: 16px; border: 2px solid transparent; background: rgba(255,255,255,0.9); border-radius: 20px; box-sizing: border-box; font-family: 'Nunito', sans-serif; transition: all 0.3s; box-shadow: inset 0 2px 5px rgba(0,0,0,0.02); }
        .ts-form-group input:focus, .ts-form-group textarea:focus, .ts-form-group select:focus { outline: none; border-color: #ff9a9e; background: #fff; box-shadow: 0 0 0 5px rgba(255, 154, 158, 0.2); }
        .ts-alert { padding: 20px 25px; background: linear-gradient(to right, #d4edda, #c3e6cb); color: #155724; border-radius: 20px; margin-bottom: 35px; font-weight:800; box-shadow: 0 10px 25px rgba(21, 87, 36, 0.1); display:flex; align-items:center; gap:10px; animation: ts-slide-down 0.5s ease; }
        @keyframes ts-slide-down { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        .ts-stat-card { flex:1; min-width:220px; padding:35px; border-radius:30px; color:#fff; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);}
        .ts-stat-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 25px 45px rgba(0,0,0,0.15); }
        .ts-stat-card-1 { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .ts-stat-card-2 { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .ts-stat-card-3 { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .ts-stat-card h3 { font-size: 18px; margin-bottom: 15px; font-weight: 800; opacity: 0.95; color: #fff;}
        .ts-stat-card p { font-size: 46px; font-weight: 900; margin: 0; line-height: 1; text-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        h2 { font-weight: 900; color: #333; margin-top: 0; margin-bottom: 35px; letter-spacing: -0.5px; }
        h3 { font-weight: 800; color: #444; margin-bottom: 25px;}
        .ts-form-box { background: rgba(255,255,255,0.7); padding:40px; border-radius:30px; border:1px solid rgba(255,255,255,0.9); margin-top:45px; box-shadow: 0 15px 35px rgba(0,0,0,0.03); backdrop-filter: blur(10px); }
    </style>
    
    <div class="ts-dash-wrapper">
        <!-- MENU SIDEBAR -->
        <div class="ts-sidebar">
            <a href="?tab=dashboard" class="<?php echo $tab=='dashboard'?'active':''; ?>">📊 Tổng quan</a>
            <a href="?tab=products" class="<?php echo $tab=='products'?'active':''; ?>">🧸 Quản lý Sản phẩm</a>
            <a href="?tab=categories" class="<?php echo $tab=='categories'?'active':''; ?>">📁 Quản lý Danh mục</a>
            <a href="?tab=orders" class="<?php echo $tab=='orders'?'active':''; ?>">🛒 Quản lý Đơn hàng</a>
        </div>
        
        <!-- NỘI DUNG CHÍNH -->
        <div class="ts-content">
            <?php if (isset($_GET['msg'])) echo '<div class="ts-alert">✅ Thao tác thành công!</div>'; ?>
            <?php
            // Load giao diện theo Tab
            if ($tab === 'dashboard') ts_render_dashboard_tab();
            elseif ($tab === 'products') ts_render_products_tab();
            elseif ($tab === 'categories') ts_render_categories_tab();
            elseif ($tab === 'orders') ts_render_orders_tab();
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Giao diện: TỔNG QUAN
function ts_render_dashboard_tab() {
    $today = date('Y-m-d');
    $orders_today = wc_get_orders(['date_created' => '>=' . $today, 'limit' => -1, 'return' => 'ids']);
    
    $total_sales = 0;
    $all_orders = wc_get_orders(['status' => 'completed', 'limit' => -1]);
    foreach ($all_orders as $o) { $total_sales += $o->get_total(); }

    $low_stock = wc_get_products(['stock_status' => 'instock', 'limit' => -1]);

    $total_products = wp_count_posts('product')->publish;

    echo '<h2>📊 Tổng quan hệ thống</h2>';
    echo '<div style="display:flex; gap:25px; margin-bottom:40px; flex-wrap:wrap;">';
    echo '<div class="ts-stat-card ts-stat-card-1"><h3>🎁 Tổng SP Đang Bán</h3><p>'.$total_products.'</p></div>';
    echo '<div class="ts-stat-card ts-stat-card-3"><h3>📦 Đơn Mới Hôm Nay</h3><p>'.count($orders_today).'</p></div>';
    echo '<div class="ts-stat-card ts-stat-card-2"><h3>💰 Tổng Doanh Thu</h3><p>'.wc_price($total_sales).'</p></div>';
    echo '</div>';

    echo '<h3>⚠️ Sản phẩm sắp hết hàng (Dưới 5 con)</h3>';
    echo '<table class="ts-table"><tr><th>Tên sản phẩm</th><th>Tồn kho</th></tr>';
    $has_low = false;
    foreach ($low_stock as $p) {
        if ($p->managing_stock() && $p->get_stock_quantity() <= 5) {
            echo '<tr><td>'.$p->get_name().'</td><td><b style="color:red;">'.$p->get_stock_quantity().'</b></td></tr>';
            $has_low = true;
        }
    }
    if (!$has_low) echo '<tr><td colspan="2">Tất cả sản phẩm đều dồi dào.</td></tr>';
    echo '</table>';
}

// Giao diện: SẢN PHẨM
function ts_render_products_tab() {
    $products = wc_get_products(['limit' => 30, 'orderby' => 'date', 'order' => 'DESC', 'status' => ['publish', 'draft', 'private']]);
    
    echo '<h2>🧸 Quản lý sản phẩm</h2>';
    echo '<table class="ts-table"><tr><th>Ảnh</th><th>Tên Gấu</th><th>Giá</th><th>Kho</th><th>Trạng thái</th><th>Hành động</th></tr>';
    foreach ($products as $p) {
        $status = $p->get_status() == 'publish' ? '<span style="color:green;font-weight:bold;">Đang bán</span>' : '<span style="color:gray;font-weight:bold;">Tạm ẩn</span>';
        
        // Form cập nhật số lượng kho nhanh
        $stock_val = $p->managing_stock() ? $p->get_stock_quantity() : 0;
        $stock_form = '<form method="POST" style="display:flex;gap:5px;align-items:center;margin:0;">';
        $stock_form .= wp_nonce_field('update_stock_'.$p->get_id(), 'ts_nonce', true, false);
        $stock_form .= '<input type="hidden" name="ts_action" value="update_stock"><input type="hidden" name="ts_id" value="'.$p->get_id().'">';
        $stock_form .= '<input type="number" name="ts_stock_qty" value="'.$stock_val.'" style="width:60px;padding:6px;border:1px solid #ddd;border-radius:4px;text-align:center;">';
        $stock_form .= '<button type="submit" class="ts-btn ts-btn-blue" style="padding:6px 10px;font-size:11px;" title="Cập nhật số lượng kho">Lưu</button>';
        $stock_form .= '</form>';

        echo '<tr>';
        echo '<td>'.$p->get_image([50,50]).'</td>';
        echo '<td><strong>'.$p->get_name().'</strong></td>';
        echo '<td>'.$p->get_price_html().'</td>';
        echo '<td>'.$stock_form.'</td>';
        echo '<td>'.$status.'</td>';
        echo '<td>';
        // Nút Ẩn/Hiện
        echo '<form method="POST" style="display:inline-block;">';
        wp_nonce_field('hide_prod_'.$p->get_id(), 'ts_nonce');
        echo '<input type="hidden" name="ts_action" value="hide_product"><input type="hidden" name="ts_id" value="'.$p->get_id().'">';
        echo '<button type="submit" class="ts-btn ts-btn-gray" style="margin-right:5px;" title="Tạm dừng/Mở bán">'.($p->get_status()=='publish'?'Ẩn':'Hiện').'</button>';
        echo '</form>';
        // Nút Xóa
        echo '<form method="POST" style="display:inline-block;" onsubmit="return confirm(\'Xóa vĩnh viễn sản phẩm này?\');">';
        wp_nonce_field('del_prod_'.$p->get_id(), 'ts_nonce');
        echo '<input type="hidden" name="ts_action" value="delete_product"><input type="hidden" name="ts_id" value="'.$p->get_id().'">';
        echo '<button type="submit" class="ts-btn ts-btn-red">Xóa</button>';
        echo '</form>';
        echo '</td></tr>';
    }
    echo '</table>';

    // Form thêm sản phẩm (Có hỗ trợ Upload Ảnh)
    echo '<div class="ts-form-box">';
    echo '<h3>➕ Thêm Gấu Bông Mới</h3>';
    echo '<form method="POST" enctype="multipart/form-data">';
    wp_nonce_field('add_product', 'ts_nonce');
    echo '<input type="hidden" name="ts_action" value="add_product">';
    echo '<div class="ts-form-group"><label>Tên sản phẩm</label><input type="text" name="ts_name" required></div>';
    echo '<div class="ts-form-group"><label>Mô tả chi tiết</label><textarea name="ts_desc" rows="4"></textarea></div>';
    echo '<div style="display:flex; gap:15px;">';
    echo '<div class="ts-form-group" style="flex:1;"><label>Giá bán (VNĐ)</label><input type="number" name="ts_price" required></div>';
    echo '<div class="ts-form-group" style="flex:1;"><label>Số lượng trong kho</label><input type="number" name="ts_stock" required></div>';
    echo '</div>';
    echo '<div class="ts-form-group"><label>Tải lên ảnh sản phẩm</label><input type="file" name="ts_image" accept="image/*" required></div>';
    echo '<button type="submit" class="ts-btn ts-btn-blue">Lưu sản phẩm</button>';
    echo '</form></div>';
}

// Giao diện: ĐƠN HÀNG
function ts_render_orders_tab() {
    // Nếu đang xem chi tiết đơn
    if (isset($_GET['view_order'])) {
        $order = wc_get_order(intval($_GET['view_order']));
        if ($order) {
            echo '<a href="?tab=orders" class="ts-btn ts-btn-gray">⬅ Trở về danh sách</a>';
            echo '<div class="ts-form-box">';
            echo '<h3>Chi tiết đơn hàng #'.$order->get_id().'</h3>';
            echo '<p><strong>Tên khách hàng:</strong> '.$order->get_billing_first_name().' '.$order->get_billing_last_name().'</p>';
            echo '<p><strong>Điện thoại:</strong> '.$order->get_billing_phone().'</p>';
            echo '<p><strong>Địa chỉ giao hàng:</strong> '.$order->get_shipping_address_1().', '.$order->get_shipping_city().'</p>';
            echo '<hr>';
            echo '<h4>Gấu bông đã mua:</h4><ul>';
            foreach ($order->get_items() as $item) {
                echo '<li>'.$item->get_name().' <b>x '.$item->get_quantity().'</b> ('.wc_price($item->get_total()).')</li>';
            }
            echo '</ul>';
            echo '<p style="font-size:18px; color:#ef5350;"><strong>Tổng thanh toán:</strong> '.$order->get_formatted_order_total().'</p>';
            echo '</div>';
            return;
        }
    }

    $orders = wc_get_orders(['limit' => 20, 'orderby' => 'date', 'order' => 'DESC']);
    echo '<h2>🛒 Quản lý Đơn hàng</h2>';
    echo '<table class="ts-table"><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái (Cập nhật)</th><th>Hành động</th></tr>';
    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>#'.$order->get_id().'</td>';
        echo '<td>'.$order->get_date_created()->date_i18n('d/m/Y H:i').'</td>';
        echo '<td>'.$order->get_billing_first_name().'</td>';
        echo '<td>'.$order->get_formatted_order_total().'</td>';
        echo '<td>';
        // Dropdown Cập nhật trạng thái
        echo '<form method="POST" style="display:flex; gap:5px;">';
        wp_nonce_field('update_ord_'.$order->get_id(), 'ts_nonce');
        echo '<input type="hidden" name="ts_action" value="update_order"><input type="hidden" name="ts_id" value="'.$order->get_id().'">';
        echo '<select name="ts_status" onchange="this.form.submit()" style="padding:6px; border-radius:4px;">';
        $statuses = wc_get_order_statuses();
        foreach ($statuses as $key => $name) {
            $selected = ('wc-'.$order->get_status() === $key) ? 'selected' : '';
            echo '<option value="'.$key.'" '.$selected.'>'.$name.'</option>';
        }
        echo '</select></form>';
        echo '</td>';
        echo '<td><a href="?tab=orders&view_order='.$order->get_id().'" class="ts-btn ts-btn-blue">Xem chi tiết</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Giao diện: DANH MỤC
function ts_render_categories_tab() {
    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    echo '<h2>📁 Quản lý Danh mục (Phân loại Gấu)</h2>';
    echo '<table class="ts-table"><tr><th>ID</th><th>Tên danh mục</th><th>Số lượng SP</th><th>Hành động</th></tr>';
    foreach ($terms as $term) {
        if ($term->slug == 'uncategorized') continue;
        echo '<tr>';
        echo '<td>'.$term->term_id.'</td>';
        echo '<td><strong>'.$term->name.'</strong></td>';
        echo '<td>'.$term->count.'</td>';
        echo '<td>';
        echo '<form method="POST" onsubmit="return confirm(\'Xóa danh mục này?\');">';
        wp_nonce_field('del_cat_'.$term->term_id, 'ts_nonce');
        echo '<input type="hidden" name="ts_action" value="delete_category"><input type="hidden" name="ts_id" value="'.$term->term_id.'">';
        echo '<button type="submit" class="ts-btn ts-btn-red">Xóa</button>';
        echo '</form>';
        echo '</td></tr>';
    }
    echo '</table>';

    echo '<div class="ts-form-box">';
    echo '<h3>➕ Thêm Danh Mục Mới</h3>';
    echo '<form method="POST">';
    wp_nonce_field('add_cat', 'ts_nonce');
    echo '<input type="hidden" name="ts_action" value="add_category">';
    echo '<div class="ts-form-group"><label>Tên danh mục (Ví dụ: Gấu bông to, Gấu hoạt hình)</label><input type="text" name="ts_name" required></div>';
    echo '<button type="submit" class="ts-btn ts-btn-blue">Tạo danh mục</button>';
    echo '</form></div>';
}

// ==========================================
// PHẦN 3: LOGIC XỬ LÝ DỮ LIỆU BACKEND CHO MEGA DASHBOARD
// ==========================================
add_action('template_redirect', 'ts_mega_dashboard_actions_handler');
function ts_mega_dashboard_actions_handler() {
    if (!is_user_logged_in() || !current_user_can('administrator')) return;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_action'])) {
        $action = sanitize_text_field($_POST['ts_action']);
        
        // 1. THÊM SẢN PHẨM MỚI (Có Upload Ảnh)
        if ($action === 'add_product' && wp_verify_nonce($_POST['ts_nonce'], 'add_product')) {
            $name = sanitize_text_field($_POST['ts_name']);
            $desc = wp_kses_post($_POST['ts_desc']);
            $price = sanitize_text_field($_POST['ts_price']);
            $stock = intval($_POST['ts_stock']);
            
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_description($desc);
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stock);
            $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
            $product->set_status('publish');
            
            // Xử lý upload ảnh an toàn bằng API WordPress
            if (!empty($_FILES['ts_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $attachment_id = media_handle_upload('ts_image', 0);
                if (!is_wp_error($attachment_id)) {
                    $product->set_image_id($attachment_id);
                }
            }
            $product->save();
            wp_safe_redirect(add_query_arg(['tab' => 'products', 'msg' => 'added'], remove_query_arg(['msg']))); exit;
        }

        // 2. XÓA SẢN PHẨM
        if ($action === 'delete_product' && wp_verify_nonce($_POST['ts_nonce'], 'del_prod_'.$_POST['ts_id'])) {
            wp_delete_post(intval($_POST['ts_id']), true);
            wp_safe_redirect(add_query_arg(['tab' => 'products', 'msg' => 'deleted'], remove_query_arg(['msg']))); exit;
        }

        // 3. ẨN/HIỆN SẢN PHẨM (Đổi trạng thái)
        if ($action === 'hide_product' && wp_verify_nonce($_POST['ts_nonce'], 'hide_prod_'.$_POST['ts_id'])) {
            $product = wc_get_product(intval($_POST['ts_id']));
            if ($product) {
                $status = $product->get_status();
                $product->set_status($status === 'publish' ? 'draft' : 'publish'); // Đổi qua lại giữa Publish và Draft
                $product->save();
            }
            wp_safe_redirect(add_query_arg(['tab' => 'products', 'msg' => 'updated'], remove_query_arg(['msg']))); exit;
        }

        // CẬP NHẬT SỐ LƯỢNG KHO NHANH (TỪ TAB SẢN PHẨM)
        if ($action === 'update_stock' && wp_verify_nonce($_POST['ts_nonce'], 'update_stock_'.$_POST['ts_id'])) {
            $product = wc_get_product(intval($_POST['ts_id']));
            if ($product) {
                $product->set_manage_stock(true);
                $new_stock = intval($_POST['ts_stock_qty']);
                $product->set_stock_quantity($new_stock);
                $product->set_stock_status($new_stock > 0 ? 'instock' : 'outofstock');
                $product->save();
            }
            wp_safe_redirect(add_query_arg(['tab' => 'products', 'msg' => 'updated'], remove_query_arg(['msg']))); exit;
        }
        
        // 4. CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
        if ($action === 'update_order' && wp_verify_nonce($_POST['ts_nonce'], 'update_ord_'.$_POST['ts_id'])) {
            $order = wc_get_order(intval($_POST['ts_id']));
            if ($order) {
                $order->update_status(sanitize_text_field($_POST['ts_status']), 'Cập nhật từ Frontend Dashboard.');
            }
            wp_safe_redirect(add_query_arg(['tab' => 'orders', 'msg' => 'updated'], remove_query_arg(['msg']))); exit;
        }

        // 5. THÊM DANH MỤC MỚI
        if ($action === 'add_category' && wp_verify_nonce($_POST['ts_nonce'], 'add_cat')) {
            $name = sanitize_text_field($_POST['ts_name']);
            wp_insert_term($name, 'product_cat');
            wp_safe_redirect(add_query_arg(['tab' => 'categories', 'msg' => 'added'], remove_query_arg(['msg']))); exit;
        }

        // 6. XÓA DANH MỤC
        if ($action === 'delete_category' && wp_verify_nonce($_POST['ts_nonce'], 'del_cat_'.$_POST['ts_id'])) {
            wp_delete_term(intval($_POST['ts_id']), 'product_cat');
            wp_safe_redirect(add_query_arg(['tab' => 'categories', 'msg' => 'deleted'], remove_query_arg(['msg']))); exit;
        }
    }
}

// ==========================================
// PHẦN 4: SHORTCODE NÚT ĐĂNG NHẬP ĐỘNG [nut_dang_nhap_dong]
// ==========================================
add_shortcode('nut_dang_nhap_dong', 'ts_nut_dang_nhap_dong_shortcode');
function ts_nut_dang_nhap_dong_shortcode() {
    ob_start();
    
    $login_url = home_url('/dang-nhap-admin');
    $dashboard_url = home_url('/bang-dieu-khien-admin');
    $logout_url = wp_logout_url(home_url()); // Đăng xuất và quay về trang chủ

    ?>
    <style>
        .ts-dynamic-btn-group {
            display: inline-flex;
            gap: 10px;
            align-items: center;
        }
        .ts-btn-dynamic {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none !important;
            color: #fff !important;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        .ts-btn-primary {
            background-color: #5bc0de;
        }
        .ts-btn-primary:hover {
            background-color: #31b0d5;
            box-shadow: 0 6px 12px rgba(91, 192, 222, 0.3);
            transform: translateY(-2px);
            color: #fff !important;
        }
        .ts-btn-danger {
            background-color: #ef5350; /* Đỏ nhạt */
        }
        .ts-btn-danger:hover {
            background-color: #e53935;
            box-shadow: 0 6px 12px rgba(239, 83, 80, 0.3);
            transform: translateY(-2px);
            color: #fff !important;
        }
    </style>
    <div class="ts-dynamic-btn-group">
        <?php if (!is_user_logged_in()) : ?>
            <!-- Trạng thái Khách chưa đăng nhập -->
            <a href="<?php echo esc_url($login_url); ?>" class="ts-btn-dynamic ts-btn-primary">Đăng Nhập Quản Trị</a>
        <?php else : ?>
            <?php if (current_user_can('administrator')) : ?>
                <!-- Trạng thái Admin -->
                <a href="<?php echo esc_url($dashboard_url); ?>" class="ts-btn-dynamic ts-btn-primary">Vào Bảng Điều Khiển</a>
            <?php endif; ?>
            <!-- Nút Đăng Xuất (hiển thị cho mọi user đã đăng nhập) -->
            <a href="<?php echo esc_url($logout_url); ?>" class="ts-btn-dynamic ts-btn-danger">Đăng Xuất</a>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ==========================================
// PHẦN 5: HIỂN THỊ SỐ LƯỢNG KHO TRÊN TRANG CỬA HÀNG (SHOP)
// ==========================================
add_action('woocommerce_after_shop_loop_item_title', 'ts_display_stock_on_shop_page', 15);
function ts_display_stock_on_shop_page() {
    global $product;
    if ($product->managing_stock()) {
        $stock = $product->get_stock_quantity();
        if ($stock > 0) {
            echo '<p style="color:#2e7d32; font-weight:bold; margin-top:5px; font-size:13px;">🎁 Còn lại: ' . $stock . ' bé</p>';
        } else {
            echo '<p style="color:#c62828; font-weight:bold; margin-top:5px; font-size:13px;">❌ Đã hết hàng</p>';
        }
    } else {
        if ($product->is_in_stock()) {
            echo '<p style="color:#2e7d32; font-weight:bold; margin-top:5px; font-size:13px;">✅ Còn hàng</p>';
        } else {
            echo '<p style="color:#c62828; font-weight:bold; margin-top:5px; font-size:13px;">❌ Hết hàng</p>';
        }
    }
}

// ==========================================
// PHẦN 6: THÔNG BÁO THANH TOÁN THÀNH CÔNG (TRANG THANK YOU)
// ==========================================
add_action('woocommerce_before_thankyou', 'ts_custom_thankyou_message', 10);
function ts_custom_thankyou_message($order_id) {
    if (!$order_id) return;
    
    $order = wc_get_order($order_id);
    
    // Chỉ hiển thị thông báo rực rỡ nếu đơn hàng đã được tạo
    ?>
    <style>
        .ts-thankyou-box {
            background-color: #d4edda;
            color: #155724;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 40px;
            border: 2px dashed #4caf50;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
            animation: ts-pop-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes ts-pop-in {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .ts-thankyou-box h2 {
            color: #2e7d32 !important;
            margin-top: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .ts-thankyou-box p {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .ts-check-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 15px;
            animation: ts-bounce 2s infinite;
        }
        @keyframes ts-bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
    <div class="ts-thankyou-box">
        <span class="ts-check-icon">🎉</span>
        <h2>Thanh Toán Thành Công!</h2>
        <p>Cảm ơn bạn đã đặt gấu bông tại <strong>Teddy Shop</strong>.</p>
        <p>Đơn hàng <strong>#<?php echo $order->get_order_number(); ?></strong> của bạn đã được hệ thống ghi nhận.</p>
        <p>Chúng tôi sẽ gọi điện xác nhận và giao bé gấu đến bạn trong thời gian sớm nhất!</p>
    </div>
    <?php
}

// Ẩn dòng thông báo mặc định nhàm chán của WooCommerce
add_filter('woocommerce_thankyou_order_received_text', '__return_empty_string');