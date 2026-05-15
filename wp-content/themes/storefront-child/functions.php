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
?>