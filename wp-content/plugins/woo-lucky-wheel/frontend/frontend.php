<?php
/*
Class Name: VI_WOO_LUCKY_WHEEL_Frontend_Frontend
Author: Andy Ha (support@villatheme.com)
Author URI: http://villatheme.com
Copyright 2015 villatheme.com. All rights reserved.
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VI_WOO_LUCKY_WHEEL_Frontend_Frontend {
    protected $settings;
    protected $pointer_position;
    protected $characters_array;

    function __construct() {
        $this->settings = VI_WOO_LUCKY_WHEEL_DATA::get_instance();
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
        if ( $this->settings->get_params( 'ajax_endpoint' ) === 'ajax' ) {
            add_action( 'wp_ajax_wlwl_get_email', array( $this, 'get_email' ) );
            add_action( 'wp_ajax_nopriv_wlwl_get_email', array( $this, 'get_email' ) );
        } else {
            add_action( 'rest_api_init', array( $this, 'register_api' ) );
        }
    }

    public function send_email( $user_email, $customer_name, $coupon_code, $date_expires = '', $coupon_label = '', $email_template = '' ) {
        $coupon_label = str_replace( '/n', ' ', $coupon_label );
        $coupon_label = preg_replace( '/ +/', ' ', $coupon_label );
        $use_template = false;
        $content      = $subject = '';
        $mailer       = WC()->mailer();
        $email        = new WC_Email();
        if ( $email_template && VI_WOO_LUCKY_WHEEL_Plugins_WooCommerce_Email_Template_Customizer::$is_active ) {
            $email_template_obj = get_post( $email_template );
            if ( $email_template_obj && $email_template_obj->post_type === 'viwec_template' ) {
                add_filter( 'viwec_disable_woocommerce_email_inline_style', '__return_false' );
                $use_template = true;
                $viwec_email  = new VIWEC_Render_Email_Template( array(
                        'template_id' => $email_template
                ) );
                ob_start();
                $viwec_email->get_content();
                $content = ob_get_clean();
                $subject = $viwec_email->get_subject();
                $replace = [
                        '{wlwl_coupon_label}'  => $coupon_label,
                        '{wlwl_customer_name}' => $customer_name,
                        '{wlwl_coupon_code}'   => strtoupper( $coupon_code ),
                        '{wlwl_date_expires}'  => empty( $date_expires ) ? esc_html__( 'never expires', 'woo-lucky-wheel' ) : date_i18n( 'F d, Y', strtotime( $date_expires ) ),
                ];
                $replace = apply_filters( 'wlwl_replace_email_content', $replace, $user_email, '', $coupon_label, $coupon_code, $date_expires );
                if ( is_array( $replace ) && ! empty( $replace ) ) {
                    $content = str_replace( array_keys( $replace ), array_values( $replace ), $content );
                    $subject = str_replace( array_keys( $replace ), array_values( $replace ), $subject );
                }
            }
        }
        if ( ! $use_template ) {
            $button_shop_now = '<a href="' . esc_url( $this->settings->get_params( 'button_shop_url' ) ) . '" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;font-size:16px;color:#ffffff;background:#000;">' . esc_html__( 'Shop now', 'woo-lucky-wheel' ) . '</a>';
            $email_temp      = $this->settings->get_params( 'result', 'email' );
            $content         = stripslashes( $email_temp['content'] );
            $replace         = [
                    '{coupon_label}'  => $coupon_label,
                    '{customer_name}' => $customer_name,
                    '{coupon_code}'   => strtoupper( $coupon_code ),
                    '{date_expires}'  => empty( $date_expires ) ? esc_html__( 'never expires', 'woo-lucky-wheel' ) : date_i18n( 'F d, Y', strtotime( $date_expires ) ),
                    '{shop_now}'      => $button_shop_now,
            ];
            $replace         = apply_filters( 'wlwl_replace_email_content', $replace, $user_email, '', $coupon_label, $coupon_code, $date_expires );
            if ( is_array( $replace ) && ! empty( $replace ) ) {
                $content = str_replace( array_keys( $replace ), array_values( $replace ), $content );
            }
//			$content         = str_replace( '{coupon_label}', $coupon_label, $content );
//			$content         = str_replace( '{customer_name}', $customer_name, $content );
//			$content         = str_replace( '{coupon_code}', strtoupper( $coupon_code ), $content );
//			$content         = str_replace( '{date_expires}', empty( $date_expires ) ? esc_html__( 'never expires', 'woo-lucky-wheel' ) : date_i18n( 'F d, Y', strtotime( $date_expires ) ), $content );
//			$content         = str_replace( '{shop_now}', $button_shop_now, $content );
            $subject       = stripslashes( $email_temp['subject'] );
            $email_heading = $email_temp['heading'];
            if ( isset( $replace['{coupon_label}'] ) ) {
                $subject       = str_replace( '{coupon_label}', $replace['{coupon_label}'], $subject );
                $email_heading = str_replace( '{coupon_label}', $replace['{coupon_label}'], $email_heading );
            }
//			$subject         = str_replace( '{coupon_label}', $coupon_label, $subject );
//			$email_heading   = str_replace( '{coupon_label}', $coupon_label, $email_heading );
            $content = wp_kses_post( do_shortcode( $content ) );
            $content = $email->style_inline( $mailer->wrap_message( $email_heading, $content ) );
        }
        $headers = "Content-Type: text/html\r\n";

        $email->send( $user_email, $subject, $content, $headers, array() );
    }

    public function frontend_enqueue() {
        if ( isset( $_COOKIE['wlwl_cookie'] ) ) {
            return;
        }
        if ( ! $this->settings || $this->settings->get_params( 'general', 'enable' ) != 'on' ) {
            return;
        }
        $show = true;
        if ( $this->settings->get_params( 'notify', 'show_only_front' ) == 'on' || $this->settings->get_params( 'notify', 'show_only_blog' ) == 'on' || $this->settings->get_params( 'notify', 'show_only_shop' ) == 'on' ) {
            $show = false;
            if ( is_front_page() && $this->settings->get_params( 'notify', 'show_only_front' ) == 'on' ) {
                $show = true;
            }
            if ( is_home() && $this->settings->get_params( 'notify', 'show_only_blog' ) == 'on' ) {
                $show = true;
            }
            if ( is_shop() && $this->settings->get_params( 'notify', 'show_only_shop' ) == 'on' ) {
                $show = true;
            }
        }
        if ( ! $show ) {
            return;
        }
        $logic_value = $this->settings->get_params( 'notify', 'conditional_tags' );
        if ($logic_value){
            preg_match('/\)[^a-zA-Z]+\(|\)\(|[^a-zA-Z0-9()_\'"\s!|&,\[\]]/',$logic_value,$exclude);
            if (!empty($exclude)){
                $logic_value = '';
            }
        }
        if ( $logic_value ) {
            $logic_check = false;
            preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $logic_value, $logics);
            $allow_func  = [
                    'is_home',
                    'is_front_page',
                    'is_single',
                    'is_page',
                    'is_attachment',
                    'is_singular',
                    'is_archive',
                    'is_category',
                    'is_tag',
                    'is_author',
                    'is_date',
                    'is_year',
                    'is_month',
                    'is_day',
                    'is_search',
                    'is_404',
                    'is_post_type_archive',
                    'is_admin',
                    'is_user_logged_in',
                    'is_active_sidebar',
                    'is_woocommerce',
                    'is_shop',
                    'is_product',
                    'is_product_category',
                    'is_product_tag',
                    'is_product_taxonomy',
                    'is_account_page',
                    'is_cart',
                    'is_checkout',
                    'is_order_received_page',
            ];
            if ( ! empty( $logics[1] ) ) {
                $logic_check = true;
                foreach ( $logics[1] as $logic ) {
                    if ($logic ==='array'){
                        continue;
                    }
                    if ( strpos( $logic, 'is_' ) !== 0 ) {
                        $logic_check = false;
                        break;
                    }
                    $allow = false;
                    foreach ($allow_func as $func) {
                        if ( strpos( $logic, $func ) === 0 ) {
                            $allow = true;
                            break;
                        }
                    }
                    if (!$allow){
                        $logic_check = false;
                        break;
                    }
                }
            }
            if ( $logic_check && stristr( $logic_value, "return" ) === false ) {
                $logic_value = "return (" . $logic_value . ");";
            } else {
                $logic_value = '';
            }
        }
        if ( $logic_value ) {
            try {
                $logic_show = eval( $logic_value );//phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
            } catch ( \Error $e ) {
                $logic_show = false;
            } catch ( \Exception $e ) {
                $logic_show = false;
            }
            if ( ! $logic_show ) {
                return;
            }
        }
        $mobile_enable = $this->settings->get_params( 'general', 'mobile' ) === 'on';
        $this->settings::enqueue_script(
                array( 'woocommerce-lucky-wheel-frontend-javascript' ),
                array( 'woocommerce-lucky-wheel' ),
                array( 0 ),
        );
        $font = $this->settings->get_params( 'wheel_wrap', 'font' );
        if ( $font ) {
            wp_enqueue_style( 'woocommerce-lucky-wheel-google-font', '//fonts.googleapis.com/css?family=' . $font . ':300,400,700', array(), VI_WOO_LUCKY_WHEEL_VERSION );
            $font = str_replace( '+', ' ', $font );
        }
        $this->settings::enqueue_style(
                array( 'woocommerce-lucky-wheel-frontend-style' ),
                array( 'woocommerce-lucky-wheel' ),
                array()
        );
        $css = '.wlwl_lucky_wheel_content {';
        if ( $this->settings->get_params( 'wheel_wrap', 'bg_image' ) ) {
            $bg_image_url = wc_is_valid_url( $this->settings->get_params( 'wheel_wrap', 'bg_image' ) ) ? $this->settings->get_params( 'wheel_wrap', 'bg_image' ) : wp_get_attachment_url( $this->settings->get_params( 'wheel_wrap', 'bg_image' ) );
            $css          .= 'background-image:url("' . $bg_image_url . '");background-repeat: no-repeat;background-size:cover;background-position:center;';
        }
        if ( $this->settings->get_params( 'wheel_wrap', 'bg_color' ) ) {
            $css .= 'background-color:' . $this->settings->get_params( 'wheel_wrap', 'bg_color' ) . ';';
        }
        if ( $this->settings->get_params( 'wheel_wrap', 'text_color' ) ) {
            $css .= 'color:' . $this->settings->get_params( 'wheel_wrap', 'text_color' ) . ';';
        }
        $css .= '}';
        if ( 'on' == $this->settings->get_params( 'wheel', 'show_full_wheel' ) ) {
            $css .= '.wlwl_lucky_wheel_content .wheel_content_left{margin-left:0 !important;}';
            $css .= '.wlwl_lucky_wheel_content .wheel_content_right{width:48% !important;}';
//			$css .= '.wlwl_lucky_wheel_content .wheel_content_right .wlwl_user_lucky{max-width:300px !important;}';
        }
        $css .= '.wlwl_wheel_icon{';
        switch ( $this->settings->get_params( 'notify', 'position' ) ) {
            case 'top-left':
                $css .= 'top:15px;left:0;margin-left: -100%;';
                break;
            case 'top-right':
                $css .= 'top:15px;right:0;margin-right: -100%;';
                break;
            case 'bottom-left':
                $css .= 'bottom:5px;left:5px;margin-left: -100%;';
                break;
            case 'bottom-right':
                $css .= 'bottom:5px;right:5px;margin-right: -100%;';
                break;
            case 'middle-left':
                $css .= 'bottom:45%;left:0;margin-left: -100%;';
                break;
            case 'middle-right':
                $css .= 'bottom:45%;right:0;margin-right: -100%;';
                break;
        }
        $css .= '}';

        if ( $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) ) {
            $css .= '.wlwl_pointer:before{color:' . $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) . ';}';
        }
        //wheel wrap design
        $css .= '.wheel_content_right .wlwl_user_lucky .wlwl_spin_button{';
        if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) ) {
            $css .= 'color:' . $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) . ';';
        }

        if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) ) {
            $css .= 'background-color:' . $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) . ';';
        }
        $css .= '}';
        if ( $font ) {
            $css .= '.wlwl_lucky_wheel_content .wheel-content-wrapper .wheel_content_right,.wlwl_lucky_wheel_content .wheel-content-wrapper .wheel_content_right input,.wlwl_lucky_wheel_content .wheel-content-wrapper .wheel_content_right span,.wlwl_lucky_wheel_content .wheel-content-wrapper .wheel_content_right a,.wlwl_lucky_wheel_content .wheel-content-wrapper .wheel_content_right .wlwl-frontend-result{font-family:"' . $font . '" !important;}';
        }
        $css .= $this->settings->get_params( 'wheel_wrap', 'custom_css' );
        wp_add_inline_style( 'woocommerce-lucky-wheel-frontend-style', wp_kses_post( $css ) );
        $wheel = $this->settings->get_params( 'wheel' );
        if ( empty( $wheel['coupon_type'] ) || ! is_array( $wheel['coupon_type'] ) ) {
            return;
        }
        $label = array();
        foreach ( $wheel['coupon_type'] as $count => $v ) {
            $wheel_label = ( isset( $wheel['custom_label'][ $count ] ) && $wheel['custom_label'][ $count ] ) ? $wheel['custom_label'][ $count ] : $this->settings->get_params( 'wheel', 'label_coupon' );
            if ( $wheel['coupon_type'][ $count ] == 'existing_coupon' ) {
                $code   = get_post( $wheel['existing_coupon'][ $count ] )->post_title;
                $coupon = new WC_Coupon( $code );
                if ( $coupon->get_discount_type() == 'percent' ) {
                    $wheel_label = str_replace( '{coupon_amount}', $coupon->get_amount() . '%', $wheel_label );
                } else {
                    $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $coupon->get_amount() ), $wheel_label );
                }
            } elseif ( in_array( $wheel['coupon_type'][ $count ], array(
                    'fixed_product',
                    'fixed_cart',
                    'percent'
            ) ) ) {
                if ( $wheel['coupon_type'][ $count ] == 'percent' ) {
                    $wheel_label = str_replace( '{coupon_amount}', $wheel['coupon_amount'][ $count ] . '%', $wheel_label );
                } else {
                    $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $wheel['coupon_amount'][ $count ] ), $wheel_label );
                }
            }
            $label[] = $wheel_label;
        }
        $wheel['label'] = $label;
        $time_if_close  = (int) $this->settings->get_params( 'notify', 'time_on_close' );
        if ( $this->settings->get_params( 'notify', 'time_on_close_unit' )  && $time_if_close) {
            switch ( $this->settings->get_params( 'notify', 'time_on_close_unit' ) ) {
                case 'm':
                    $time_if_close *= 60;
                    break;
                case 'h':
                    $time_if_close *= 3600;
                    break;
                case 'd':
                    $time_if_close *= 86400;
                    break;
                default:
            }
        }
        $limit_time_warning = esc_html__( 'You have to wait until your next spin.', 'woo-lucky-wheel' );
        switch ( $this->settings->get_params( 'notify', 'show_again_unit' ) ) {
            case 's':
                $limit_time_warning = sprintf( esc_html__( 'You can only spin 1 time every %s seconds', 'woo-lucky-wheel' ), $this->settings->get_params( 'notify', 'show_again' ) );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                break;
            case 'm':
                $limit_time_warning = sprintf( esc_html__( 'You can only spin 1 time every %s minutes', 'woo-lucky-wheel' ), $this->settings->get_params( 'notify', 'show_again' ) );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                break;
            case 'h':
                $limit_time_warning = sprintf( esc_html__( 'You can only spin 1 time every %s hours', 'woo-lucky-wheel' ), $this->settings->get_params( 'notify', 'show_again' ) );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                break;
            case 'd':
                $limit_time_warning = sprintf( esc_html__( 'You can only spin 1 time every %s days', 'woo-lucky-wheel' ), $this->settings->get_params( 'notify', 'show_again' ) );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                break;
        }
        $this->pointer_position = 'center';
        wp_localize_script( 'woocommerce-lucky-wheel-frontend-javascript', '_wlwl_get_email_params',  wp_parse_args(array(
                'ajaxurl'            => $this->settings->get_params( 'ajax_endpoint' ) == 'ajax' ? ( admin_url( 'admin-ajax.php' ) . '?action=wlwl_get_email' ) : site_url() . '/wp-json/woocommerce_lucky_wheel/spin',
                'pointer_position'   => $this->pointer_position,
                'wheel_dot_color'    => '#000000',
                'wheel_border_color' => '#ffffff',
                'wheel_center_color' => $this->settings->get_params( 'wheel_wrap', 'wheel_center_color' ),
                'gdpr'               => $this->settings->get_params( 'wheel_wrap', 'gdpr' ) === 'on' ? 1 : '',
                'gdpr_warning'       => esc_html__( 'Please agree with our term and condition.', 'woo-lucky-wheel' ),

                'position'        => $this->settings->get_params( 'notify', 'position' ),
                'show_again'      => $this->settings->get_params( 'notify', 'show_again' ),
                'show_again_unit' => $this->settings->get_params( 'notify', 'show_again_unit' ),
                'intent'          => $this->settings->get_params( 'notify', 'intent' ),
                'hide_popup'      => $this->settings->get_params( 'notify', 'hide_popup' ) === 'on' ? 1 : '',

                'slice_text_color'                => ( isset( $wheel['slice_text_color'] ) && $wheel['slice_text_color'] ) ? $wheel['slice_text_color'] : '#ffffff',
                'bg_color'                        => $wheel['bg_color'] ?? [],
                'label'                           => $label,
                'coupon_type'                     => $wheel['coupon_type'],
                'spinning_time'                   => 8,
                'wheel_speed'                     => 5,
                'wheel_size'                      => 100,
                'font_size'                       => 100,
                'auto_close'                      => $this->settings->get_params( 'result', 'auto_close' ),
                'show_wheel'                      => wlwl_get_explode( $this->settings->get_params( 'notify', 'show_wheel' ), ',' ),
                'time_if_close'                   => $time_if_close,
                'empty_email_warning'             => esc_html__( '*Please enter your email', 'woo-lucky-wheel' ),
                'invalid_email_warning'           => esc_html__( '*Please enter a valid email address', 'woo-lucky-wheel' ),
                'limit_time_warning'              => $limit_time_warning,
                'custom_field_name_enable'        => $this->settings->get_params( 'custom_field_name_enable' ) === 'on' ? 1 : '',
                'custom_field_name_enable_mobile' => $this->settings->get_params( 'custom_field_name_enable_mobile' ) === 'on' ? 1 : '',
                'custom_field_name_required'      => $this->settings->get_params( 'custom_field_name_required' ) === 'on' ? 1 : '',
                'custom_field_name_message'       => esc_html__( 'Name is required!', 'woo-lucky-wheel' ),
                'show_full_wheel'                 => $this->settings->get_params( 'wheel', 'show_full_wheel' ),
                'wlwl_mobile_enable'              => $mobile_enable,
                'nonce'           => apply_filters('wlwl_get_wheel_nonce',1)?wp_create_nonce( 'woocommerce_lucky_wheel_nonce_action' ):'',
        ),apply_filters('wlwl_get_default_params',[])) );
        add_action( 'wp_footer', array( $this, 'draw_wheel' ) );
    }

    /**
     * Register API json
     */
    public function register_api() {
        register_rest_route(
                'woocommerce_lucky_wheel', '/spin', array(
                        'methods'             => 'POST',
                        'callback'            => array( $this, 'get_email' ),
                        'permission_callback' => '__return_true',
                )
        );
    }

    public function draw_wheel() {
        if ( isset( $_COOKIE['wlwl_cookie'] ) ) {
            return;
        }
        echo '<div class="wlwl_lucky_wheel_wrap">';
        $class = array( 'wlwl_lucky_wheel_content' );//lucky_wheel_content_tablet,wlwl_lucky_wheel_content_mobile
        ?>
        <div class="wlwl-overlay"></div>
        <div class="<?php echo esc_attr( implode( ' ', $class ) ) ?>">
            <div class="wheel-content-wrapper">
                <div class="wheel_content_left">
                    <div class="wlwl-frontend-result"></div>
                    <div class="wheel_spin wlwl_wheel_spin">
                        <canvas id="wlwl_canvas"></canvas>
                        <canvas id="wlwl_canvas1"></canvas>
                        <canvas id="wlwl_canvas2"></canvas>
                        <div class="wheel_spin_container wlwl_wheel_spin_container">
                            <div class="wlwl_pointer_before"></div>
                            <div class="wlwl_pointer_content">
                                <span class="wlwl-location wlwl_pointer"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wheel_content_right">
                    <div class="wheel_description">
                        <?php echo do_shortcode( $this->settings->get_params( 'wheel_wrap', 'description' ) ); ?>
                    </div>
                    <div class="wlwl-congratulations-effect">
                        <div class="wlwl-congratulations-effect-before"></div>
                        <div class="wlwl-congratulations-effect-after"></div>
                    </div>
                    <div class="wlwl_user_lucky">
                        <?php
                        if ( 'on' == $this->settings->get_params( 'custom_field_name_enable' ) ) {
                            ?>
                            <div class="wlwl_field_name_wrap">
                                <input type="text" class="wlwl_field_input wlwl_field_name" name="wlwl_player_name"
                                       placeholder="<?php esc_html_e( "Please enter your name", 'woo-lucky-wheel' ) ?>"
                                       id="wlwl_player_name">
                                <span id="wlwl_error_name" class="wlwl_error_field"></span>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="wlwl_field_email_wrap">
                            <input type="email" class="wlwl_field_input wlwl_field_email" name="wlwl_player_mail"
                                   placeholder="<?php esc_html_e( "Please enter your email", 'woo-lucky-wheel' ) ?>"
                                   id="wlwl_player_mail">
                            <span id="wlwl_error_mail" class="wlwl_error_field"></span>
                        </div>
                        <span class="wlwl_chek_mail wlwl_spin_button button-primary" id="wlwl_chek_mail">
							<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button' ) ) {
                                echo esc_html( $this->settings->get_params( 'wheel_wrap', 'spin_button' ) );
                            } else {
                                esc_html_e( "Try Your Lucky", 'woo-lucky-wheel' );
                            } ?>
                            </span>
                        <?php
                        if ( 'on' == $this->settings->get_params( 'wheel_wrap', 'gdpr' ) ) {
                            $gdpr_message = $this->settings->get_params( 'wheel_wrap', 'gdpr_message' );
                            if ( empty( $gdpr_message ) ) {
                                $gdpr_message = esc_html__( 'I agree with the term and condition', 'woo-lucky-wheel'  );
                            }
                            ?>
                            <div class="wlwl-gdpr-checkbox-wrap">
                                <input type="checkbox" id="wlwl-gdpr-checkbox-input">
                                <label for="wlwl-gdpr-checkbox-input"><?php echo wp_kses_post( $gdpr_message ) ?></label>
                            </div>
                            <?php
                        }
                        if ( 'on' === $this->settings->get_params( 'wheel_wrap', 'close_option' ) ) {
                            ?>
                            <div class="wlwl-show-again-option">
                                <div class="wlwl-never-again">
                                    <span><?php esc_html_e( "Never", 'woo-lucky-wheel' ); ?></span>
                                </div>
                                <div class="wlwl-reminder-later">
                                    <span class="wlwl-reminder-later-a"><?php esc_html_e( "Remind later", 'woo-lucky-wheel' ); ?></span>
                                </div>
                                <div class="wlwl-close">
                                    <span><?php esc_html_e( "No thanks", 'woo-lucky-wheel' ); ?></span>
                                </div>
                            </div>
                            <?php
                        }

                        ?>
                    </div>
                </div>
            </div>
            <div class="wlwl-close-wheel"><span class="wlwl-cancel"></span></div>
            <div class="wlwl-hide-after-spin"><span class="wlwl-cancel"></span></div>
        </div>
        <?php
        $wheel_icon_class = 'wlwl_wheel_icon woocommerce-lucky-wheel-popup-icon wlwl-wheel-position-' . $this->settings->get_params( 'notify', 'position' );
        ?>
        <canvas id="wlwl_popup_canvas" class="<?php echo esc_attr( $wheel_icon_class ) ?>" width="64" height="64"></canvas>
        <?php
        echo '</div>';
    }

    public function wc_price( $price, $args = array() ) {
        extract(
                apply_filters(
                        'wc_price_args', wp_parse_args(
                                $args, array(
                                        'ex_tax_label'       => false,
                                        'currency'           => apply_filters( 'wlwl_woocommerce_currency',get_option( 'woocommerce_currency' )),
                                        'decimal_separator'  => apply_filters( 'wlwl_woocommerce_price_decimal_sep',get_option( 'woocommerce_price_decimal_sep' )),
                                        'thousand_separator' => apply_filters( 'wlwl_woocommerce_price_thousand_sep',get_option( 'woocommerce_price_thousand_sep' )),
                                        'decimals'           => apply_filters( 'wlwl_woocommerce_price_num_decimals', get_option( 'woocommerce_price_num_decimals', 2 ) ),
                                        'price_format'       => get_woocommerce_price_format(),
                                )
                        )
                )
        );
        $currency_pos = get_option( 'woocommerce_currency_pos' );

        switch ( $currency_pos ) {
            case 'right' :
                $price_format = '%2$s%1$s';
                break;
            case 'left_space' :
                $price_format = '%1$s&nbsp;%2$s';
                break;
            case 'right_space' :
                $price_format = '%2$s&nbsp;%1$s';
                break;
            default:
                $price_format = '%1$s%2$s';
        }
        $price_format = apply_filters( 'wlwl_get_price_format',$price_format);
        $price = apply_filters('wlwl_get_price', $price);

        $unformatted_price = $price;
        $negative          = $price < 0;
        $price             = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * - 1 : $price ) );
        $price             = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

        if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
            $price = wc_trim_zeros( $price );
        }
        $formatted_price = ( $negative ? '-' : '' ) . sprintf( $price_format, wlwl_get_currency_symbol( $currency ), $price );

        return $formatted_price;
    }

    public function get_email() {
        if (apply_filters('wlwl_get_wheel_nonce',1) && ( !isset($_REQUEST['_woocommerce_lucky_wheel_nonce']) ||
             !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_woocommerce_lucky_wheel_nonce'])), 'woocommerce_lucky_wheel_nonce_action')) ) {
            $msg            = array(
                    'allow_spin'              => 'Invalid nonce, please reload and try again.',
            );
            wp_send_json( $msg );
            die;
        }
        if ( $this->settings->get_params( 'ajax_endpoint' ) === 'rest_api' ) {
            header( "Access-Control-Allow-Origin: *" );
            header( 'Access-Control-Allow-Methods: POST' );
        }
        $email  = isset( $_POST["user_email"] ) ? sanitize_email( strtolower( wp_unslash( $_POST["user_email"] ) ) ) : '';
        $name   = ( isset( $_POST["user_name"] ) && $_POST["user_name"] ) ? sanitize_text_field( wp_unslash( $_POST["user_name"] ) ) : 'Sir/Madam';
        $mobile = '';
        if ( ! $email || ! is_email( $email ) ) {
            wp_send_json(
                    array(
                            'allow_spin' => esc_html__( 'Email is invalid', 'woo-lucky-wheel' ),
                    )
            );
        }
        if ( ! $name && 'on' == $this->settings->get_params( 'custom_field_name_required' ) &&
             ( ! empty( $_POST['is_desktop'] ) || ( 'on' == $this->settings->get_params( 'custom_field_name_enable_mobile' ) ) ) ) {
            wp_send_json(
                    array(
                            'allow_spin' => esc_html__( 'Name is required', 'woo-lucky-wheel' ),
                    )
            );
        }

        $allow       = 'no';
        $email_delay = intval($this->settings->get_params( 'general', 'delay' ));
        if ($email_delay) {
            switch ( $this->settings->get_params( 'general', 'delay_unit' ) ) {
                case 'm':
                    $email_delay *= 60;
                    break;
                case 'h':
                    $email_delay *= 60 * 60;
                    break;
                case 'd':
                    $email_delay *= 60 * 60 * 24;
                    break;
                default:
            }
        }
        $stop   = - 1;
        $result = 'lost';
        if ( $this->settings->get_params( 'result', 'notification' )['lost'] ) {
            $result_notification = $this->settings->get_params( 'result', 'notification' )['lost'];
        } else {
            $result_notification = esc_html__( 'OOPS! You are not lucky today. Sorry.', 'woo-lucky-wheel' );
        }
        $now   = time();
        $wheel = $this->settings->get_params( 'wheel' );
        $weigh = $wheel['probability'];
        if ( $this->settings->get_params( 'general', 'enable' ) != 'on' ) {
            $allow = 'Wrong email.';
            $data  = array( 'allow_spin' => $allow );
            wp_send_json( $data );
        }
        if ( $this->settings->get_params( 'mailchimp', 'enable' ) == 'on' ) {
            $mailchimp = new VI_WOO_LUCKY_WHEEL_Admin_Mailchimp();
            $mailchimp->add_email( $email, $name, '', $mobile );
        }
        do_action( 'woo_lucky_wheel_get_email_before_validating_email', $email, $name, $mobile );
        $trash_email = new WP_Query( array(
                'post_type'      => 'wlwl_email',
                'posts_per_page' => - 1,
                'title'          => $email,
                'post_status'    => array( // (string | array) - use post status. Retrieves posts by Post Status, default value i'publish'.
                        'trash', // - post is in trashbin (available with Version 2.9).
                )
        ) );
        if ( $trash_email->have_posts() ) {
            $allow = esc_html__( 'Sorry, this email is marked as spam now. Please enter another email to continue.', 'woo-lucky-wheel' );
            wp_reset_postdata();
            $data = array( 'allow_spin' => $allow );
            wp_send_json( $data );
        }
        $email_templates  = $this->settings->get_params( 'wheel', 'email_templates' );
        $wlwl_emails_args = array(
                'post_type'      => 'wlwl_email',
                'posts_per_page' => - 1,
                'title'          => $email,
                'post_status'    => array( // (string | array) - use post status. Retrieves posts by Post Status, default value i'publish'.
                        'publish', // - a published post or page.
                        'pending', // - post is pending review.
                        'draft',  // - a post in draft status.
                        'auto-draft', // - a newly created post, with no content.
                        'future', // - a post to publish in the future.
                        'private', // - not visible to users who are not logged in.
                        'inherit', // - a revision. see get_children.
                        'trash', // - post is in trashbin (available with Version 2.9).
                )
        );
        $the_query        = new WP_Query( $wlwl_emails_args );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $email_id                  = get_the_ID();
                $post_data                 = (array) get_post();
                $post_data['post_content'] = $name;
                wp_update_post( $post_data );
                $spin_meta = get_post_meta( $email_id, 'wlwl_spin_times', true );
                $spin_num  = $this->settings->get_params( 'general', 'spin_num' );
                if ( is_numeric( $spin_num ) && $spin_meta['spin_num'] >= floatval( $spin_num ) ) {
                    $allow = esc_html__( 'This email has reach the maximum spins.', 'woo-lucky-wheel' );
                } elseif ( ( $now - $spin_meta['last_spin'] ) < $email_delay ) {
                    $wait      = $email_delay + $spin_meta['last_spin'] - $now;
                    $wait_day  = floor( $wait / 86400 );
                    $wait_hour = floor( ( $wait - $wait_day * 86400 ) / 3600 );
                    $wait_min  = floor( ( $wait - $wait_day * 86400 - $wait_hour * 3600 ) / 60 );
                    $wait_sec  = $wait - $wait_day * 86400 - $wait_hour * 3600 - $wait_min * 60;

                    $wait_return = $wait_sec . esc_html__( ' seconds', 'woo-lucky-wheel' );
                    if ( $wait_day ) {
                        $wait_return = sprintf( esc_html__( '%s days %s hours %s minutes %s seconds', 'woo-lucky-wheel' ), $wait_day, $wait_hour, $wait_min, $wait_sec );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment,WordPress.WP.I18n.UnorderedPlaceholdersText
                    } elseif ( $wait_hour ) {
                        $wait_return = sprintf( esc_html__( '%s hours %s minutes %s seconds', 'woo-lucky-wheel' ), $wait_hour, $wait_min, $wait_sec );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment,WordPress.WP.I18n.UnorderedPlaceholdersText
                    } elseif ( $wait_min ) {
                        $wait_return = sprintf( esc_html__( '%s minutes %s seconds', 'woo-lucky-wheel' ), $wait_min, $wait_sec );// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment,WordPress.WP.I18n.UnorderedPlaceholdersText
                    }
                    $allow = esc_html__( 'You have to wait ', 'woo-lucky-wheel' ) . ( $wait_return ) . esc_html__( ' to be able to spin again.', 'woo-lucky-wheel' );
                } else {
                    $allow = 'yes';
                    $spin_meta['spin_num'] ++;
                    update_post_meta(
                            $email_id, 'wlwl_spin_times', array(
                                    'spin_num'  => $spin_meta['spin_num'],
                                    'last_spin' => $now,
                                    'gdpr'      => 1
                            )
                    );
                    for ( $i = 1; $i < sizeof( $weigh ); $i ++ ) {
                        $weigh[ $i ] += $weigh[ $i - 1 ];
                    }
                    for ( $i = 0; $i < sizeof( $weigh ); $i ++ ) {
                        if ( $wheel['probability'] == 0 ) {
                            $weigh[ $i ] = 0;
                        }
                    }
                    $random = wp_rand( 1, 100 );
                    $stop   = 0;
                    foreach ( $weigh as $v ) {
                        if ( $random <= $v ) {
                            break;
                        }
                        $stop ++;
                    }
                    if ( $wheel['coupon_type'][ $stop ] != 'non' ) {
                        $result = 'win';
                        if ( $this->settings->get_params( 'result', 'notification' )['win'] ) {
                            $result_notification = $this->settings->get_params( 'result', 'notification' )['win'];
                        } else {
                            $result_notification = esc_html__( 'Congrats! You have won a {coupon_label} discount coupon. The coupon was sent to the email address that you had entered to spin. Thank You!', 'woo-lucky-wheel' );
                        }
                        $email_template = isset( $email_templates[ $stop ] ) ? $email_templates[ $stop ] : '';
                        $wheel_label    = $wheel['custom_label'][ $stop ];
                        if ( $wheel['coupon_type'][ $stop ] == 'custom' ) {
                            $code = $wheel['custom_value'][ $stop ];
                            $this->send_email( $email, $name, $code, '', $wheel_label, $email_template );
                            $email_coupons   = is_array( get_post_meta( $email_id, 'wlwl_email_coupons', true ) ) ? get_post_meta( $email_id, 'wlwl_email_coupons', true ) : array();
                            $email_coupons[] = $code;
                            update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );
                            $email_labels   = is_array( get_post_meta( $email_id, 'wlwl_email_labels', true ) ) ? get_post_meta( $email_id, 'wlwl_email_labels', true ) : array();
                            $email_labels[] = $wheel_label;
                            update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                            $result_notification = str_replace( '{coupon_code}', '<strong>' . $code . '</strong>', $result_notification );
                        } elseif ( $wheel['coupon_type'][ $stop ] == 'existing_coupon' ) {
                            $code   = get_post( $wheel['existing_coupon'][ $stop ] )->post_title;
                            $coupon = new WC_Coupon( $code );
                            if ( $coupon->get_discount_type() == 'percent' ) {
                                $wheel_label = str_replace( '{coupon_amount}', $coupon->get_amount() . '%', $wheel_label );
                            } else {
                                $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $coupon->get_amount() ), $wheel_label );
                            }
                            $email_restrict = is_array( $coupon->get_email_restrictions() ) ? $coupon->get_email_restrictions() : array();
                            if ( ! in_array( $email, $email_restrict ) ) {
                                $email_restrict[] = $email;
                                $coupon->set_email_restrictions( $email_restrict );
                                $coupon->save();
                            }
                            $this->send_email( $email, $name, $coupon->get_code(), $coupon->get_date_expires(), $wheel_label, $email_template );
                            $email_coupons   = is_array( get_post_meta( $email_id, 'wlwl_email_coupons', true ) ) ? get_post_meta( $email_id, 'wlwl_email_coupons', true ) : array();
                            $email_coupons[] = $coupon->get_code();
                            update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );

                            $email_labels   = is_array( get_post_meta( $email_id, 'wlwl_email_labels', true ) ) ? get_post_meta( $email_id, 'wlwl_email_labels', true ) : array();
                            $email_labels[] = $wheel_label;
                            update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                            $result_notification = str_replace( '{coupon_code}', '<strong>' . $coupon->get_code() . '</strong>', $result_notification );
                        } else {
                            if ( $wheel['coupon_type'][ $stop ] == 'percent' ) {
                                $wheel_label = str_replace( '{coupon_amount}', $wheel['coupon_amount'][ $stop ] . '%', $wheel_label );
                            } else {
                                $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $wheel['coupon_amount'][ $stop ] ), $wheel_label );
                            }
                            $code             = $this->create_coupon( $wheel['coupon_type'][ $stop ], $wheel['coupon_amount'][ $stop ] );
                            $coupon           = new WC_Coupon( $code );
                            $email_restrict   = array();
                            $email_restrict[] = $email;
                            $coupon->set_email_restrictions( $email_restrict );
                            $coupon->save();
                            $this->send_email( $email, $name, $code, $coupon->get_date_expires(), $wheel_label, $email_template );
                            $email_coupons   = is_array( get_post_meta( $email_id, 'wlwl_email_coupons', true ) ) ? get_post_meta( $email_id, 'wlwl_email_coupons', true ) : array();
                            $email_coupons[] = $code;
                            update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );

                            $email_labels   = is_array( get_post_meta( $email_id, 'wlwl_email_labels', true ) ) ? get_post_meta( $email_id, 'wlwl_email_labels', true ) : array();
                            $email_labels[] = $wheel_label;
                            update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                            $result_notification = str_replace( '{coupon_code}', '<strong>' . $code . '</strong>', $result_notification );
                        }
                        $result_notification = str_replace( '{coupon_label}', '<strong>' . $wheel_label . '</strong>', $result_notification );
                        $result_notification = str_replace( '{customer_name}', '<strong>' . ( isset( $_POST['user_name'] ) ? sanitize_text_field( wp_unslash( $_POST['user_name'] ) ) : '' ) . '</strong>', $result_notification );
                        $result_notification = str_replace( '{customer_email}', '<strong>' . $email . '</strong>', $result_notification );
                        $result_notification = str_replace( '{checkout}', '<a href="' . wc_get_checkout_url() . '">' . esc_html__( 'Checkout', 'woo-lucky-wheel' ) . '</a>', $result_notification );
                    }
                }
            }
            wp_reset_postdata();
        } else {
            $allow = 'yes';
            //save email
            $email_id = wp_insert_post(
                    array(
                            'post_title'   => $email,
                            'post_name'    => $email,
                            'post_content' => $name,
                            'post_author'  => 1,
                            'post_status'  => 'publish',
                            'post_type'    => 'wlwl_email',
                    )
            );
            update_post_meta( $email_id, 'wlwl_email_mobile', $mobile );
            update_post_meta( $email_id, 'wlwl_spin_times', array(
                    'spin_num'  => 1,
                    'last_spin' => $now,
                    'gdpr'      => 1
            ) );
            //get stop position
            for ( $i = 1; $i < sizeof( $weigh ); $i ++ ) {
                $weigh[ $i ] += $weigh[ $i - 1 ];
            }
            for ( $i = 0; $i < sizeof( $weigh ); $i ++ ) {
                if ( $wheel['probability'] == 0 ) {
                    $weigh[ $i ] = 0;
                }
            }
            $random = wp_rand( 1, 100 );
            $stop   = 0;
            foreach ( $weigh as $v ) {
                if ( $random <= $v ) {
                    break;
                }
                $stop ++;
            }
            $email_coupons = array();
            $email_labels  = array();
            $wheel_label   = $wheel['custom_label'][ $stop ];

            if ( $wheel['coupon_type'][ $stop ] != 'non' ) {
                $result = 'win';
                if ( $this->settings->get_params( 'result', 'notification' )['win'] ) {
                    $result_notification = $this->settings->get_params( 'result', 'notification' )['win'];
                } else {
                    $result_notification = esc_html__( 'Congrats! You have won a {coupon_label} discount coupon. The coupon was sent to the email address that you had entered to spin. Thank You!', 'woo-lucky-wheel' );
                }
                $email_template = isset( $email_templates[ $stop ] ) ? $email_templates[ $stop ] : '';
                if ( $wheel['coupon_type'][ $stop ] == 'custom' ) {
                    $code = $wheel['custom_value'][ $stop ];
                    $this->send_email( $email, $name, $code, '', $wheel_label, $email_template );
                    $email_coupons[] = $code;
                    update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );
                    $email_labels[] = $wheel_label;
                    update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                    $result_notification = str_replace( '{coupon_code}', '<strong>' . $code . '</strong>', $result_notification );
                } elseif ( $wheel['coupon_type'][ $stop ] == 'existing_coupon' ) {
                    $code   = get_post( $wheel['existing_coupon'][ $stop ] )->post_title;
                    $coupon = new WC_Coupon( $code );
                    if ( $coupon->get_discount_type() == 'percent' ) {
                        $wheel_label = str_replace( '{coupon_amount}', $coupon->get_amount() . '%', $wheel_label );
                    } else {
                        $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $coupon->get_amount() ), $wheel_label );
                    }
                    $email_restrict = is_array( $coupon->get_email_restrictions() ) ? $coupon->get_email_restrictions() : array();
                    if ( ! in_array( $email, $email_restrict ) ) {
                        $email_restrict[] = $email;
                        $coupon->set_email_restrictions( $email_restrict );
                        $coupon->save();
                    }

                    $this->send_email( $email, $name, $code, $coupon->get_date_expires(), $wheel_label, $email_template );
                    $email_coupons[] = $coupon->get_code();
                    update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );
                    $email_labels[] = $wheel_label;
                    update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                    $result_notification = str_replace( '{coupon_code}', '<strong>' . $coupon->get_code() . '</strong>', $result_notification );
                } else {
                    if ( $wheel['coupon_type'][ $stop ] == 'percent' ) {
                        $wheel_label = str_replace( '{coupon_amount}', $wheel['coupon_amount'][ $stop ] . '%', $wheel_label );
                    } else {
                        $wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $wheel['coupon_amount'][ $stop ] ), $wheel_label );
                    }
                    $code             = $this->create_coupon( $wheel['coupon_type'][ $stop ], $wheel['coupon_amount'][ $stop ] );
                    $coupon           = new WC_Coupon( $code );
                    $email_restrict   = array();
                    $email_restrict[] = $email;
                    $coupon->set_email_restrictions( $email_restrict );
                    $coupon->save();

                    $this->send_email( $email, $name, $code, $coupon->get_date_expires(), $wheel_label, $email_template );
                    $email_coupons[] = $code;
                    update_post_meta( $email_id, 'wlwl_email_coupons', $email_coupons );
                    $email_labels[] = $wheel_label;
                    update_post_meta( $email_id, 'wlwl_email_labels', $email_labels );
                    $result_notification = str_replace( '{coupon_code}', '<strong>' . $code . '</strong>', $result_notification );
                }
                $result_notification = str_replace( '{coupon_label}', '<strong>' . $wheel_label . '</strong>', $result_notification );
                $result_notification = str_replace( '{customer_name}', '<strong>' . ( isset( $_POST['user_name'] ) ? sanitize_text_field( wp_unslash( $_POST['user_name'] ) ) : '' ) . '</strong>', $result_notification );
                $result_notification = str_replace( '{customer_email}', '<strong>' . $email . '</strong>', $result_notification );
                $result_notification = str_replace( '{checkout}', '<a href="' . wc_get_checkout_url() . '">' . esc_html__( 'Checkout', 'woo-lucky-wheel' ) . '</a>', $result_notification );
                $result_notification = str_replace( array( '\n', '/n' ), ' ', $result_notification );
            }
        }
        do_action( 'woo_lucky_wheel_get_email', $email, $name, $mobile );
        $data = array(
                'allow_spin'          => $allow,
                'stop_position'       => $stop,
                'result_notification' => do_shortcode( $result_notification ),
                'result'              => $result,
        );
        $data = apply_filters( 'woo_lucky_wheel_get_email_response', $data, $email, $name, $mobile );
        wp_send_json( $data );
    }

    protected function rand() {
        if ( $this->characters_array === null ) {
            $this->characters_array = array_merge( range( 0, 9 ), range( 'a', 'z' ) );
        }
        $rand = wp_rand( 0, count( $this->characters_array ) - 1 );

        return $this->characters_array[ $rand ];
    }

    protected function create_code() {
        $code = $this->settings->get_params( 'coupon', 'coupon_code_prefix' );
        for ( $i = 0; $i < 6; $i ++ ) {
            $code .= $this->rand();
        }
        $args      = array(
                'post_type'      => 'shop_coupon',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'title'          => $code
        );
        $the_query = new WP_Query( $args );
        if ( $the_query->have_posts() ) {
            wp_reset_postdata();
            $code = $this->create_code();
        }
        wp_reset_postdata();

        return $code;
    }

    public function create_coupon( $coupon_type, $coupon_amount ) {
        //Create coupon
        $code         = $this->create_code();
        $coupon       = new WC_Coupon( $code );
        $today        = strtotime( date( 'Ymd' ) );//phpcs:ignore 	WordPress.DateTime.RestrictedFunctions.date_date
        $date_expires = ( $this->settings->get_params( 'coupon', 'expiry_date' ) ) ? ( (intval( $this->settings->get_params( 'coupon', 'expiry_date' ) )+ 1 ) * 86400 + $today ) : '';
        $coupon->set_amount( $coupon_amount );
        $coupon->set_date_expires( $date_expires );
        $coupon->set_discount_type( $coupon_type );
        $coupon->set_individual_use( $this->settings->get_params( 'coupon', 'individual_use' ) == 'yes' ? 1 : 0 );
        if ( $this->settings->get_params( 'coupon', 'product_ids' ) ) {
            $coupon->set_product_ids( $this->settings->get_params( 'coupon', 'product_ids' ) );
        }
        if ( $this->settings->get_params( 'coupon', 'exclude_product_ids' ) ) {
            $coupon->set_excluded_product_ids( $this->settings->get_params( 'coupon', 'exclude_product_ids' ) );
        }
        $coupon->set_usage_limit( $this->settings->get_params( 'coupon', 'limit_per_coupon' ) );
        $coupon->set_usage_limit_per_user( $this->settings->get_params( 'coupon', 'limit_per_user' ) );
        $coupon->set_limit_usage_to_x_items( $this->settings->get_params( 'coupon', 'limit_to_x_items' ) );
        $coupon->set_free_shipping( $this->settings->get_params( 'coupon', 'allow_free_shipping' ) == 'yes' ? 1 : 0 );
        $coupon->set_product_categories( $this->settings->get_params( 'coupon', 'product_categories' ) );
        $coupon->set_excluded_product_categories( $this->settings->get_params( 'coupon', 'exclude_product_categories' ) );
        $coupon->set_exclude_sale_items( $this->settings->get_params( 'coupon', 'exclude_sale_items' ) == 'yes' ? 1 : 0 );
        $coupon->set_minimum_amount( $this->settings->get_params( 'coupon', 'min_spend' ) );
        $coupon->set_maximum_amount( $this->settings->get_params( 'coupon', 'max_spend' ) );
        $coupon->save();
        $code = $coupon->get_code();
        update_post_meta( $coupon->get_id(), 'wlwl_unique_coupon', 'yes' );

        return $code;
    }

}
