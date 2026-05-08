<?php
/*
Class Name: VI_WOO_LUCKY_WHEEL_Admin_Admin
Author: Andy Ha (support@villatheme.com)
Author URI: http://villatheme.com
Copyright 2015 villatheme.com. All rights reserved.
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WOO_LUCKY_WHEEL_Admin_Admin {
	protected $settings;
	protected $updated_sucessfully, $error;

	function __construct() {
		$this->settings = VI_WOO_LUCKY_WHEEL_DATA::get_instance();
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ), 99 );
		add_action( 'wp_ajax_wlwl_search_coupon', array( $this, 'search_coupon' ) );
		add_action( 'wp_ajax_wlwl_search_cate', array( $this, 'search_cate' ) );
		add_action( 'wp_ajax_wlwl_search_product', array( $this, 'search_product' ) );
		add_action( 'wp_ajax_wlwl_preview_emails', array( $this, 'preview_emails_ajax' ) );
		add_action( 'wp_ajax_wlwl_preview_wheel', array( $this, 'preview_wheel_ajax' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_action( 'media_buttons', array( $this, 'preview_emails_button' ) );
		add_action( 'admin_footer', array( $this, 'preview_emails_html' ) );

	}

	function preview_emails_html() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'woo-lucky-wheel' ) {//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
            <div class="preview-emails-html-container preview-html-hidden">
                <div class="preview-emails-html-overlay"></div>
                <div class="preview-emails-html"></div>
            </div>
			<?php
		}
	}

	function add_menu() {
		add_menu_page(
			esc_html__( 'Lucky Wheel for WooCommerce', 'woo-lucky-wheel' ), esc_html__( 'WC Lucky Wheel', 'woo-lucky-wheel' ), 'manage_options', 'woo-lucky-wheel', array(
			$this,
			'settings_page'
		), 'dashicons-wheel', 2
		);
		add_submenu_page( 'woo-lucky-wheel', esc_html__( 'Emails', 'woo-lucky-wheel' ), esc_html__( 'Emails', 'woo-lucky-wheel' ), 'manage_options', 'edit.php?post_type=wlwl_email' );
		add_submenu_page(
			'woo-lucky-wheel', esc_html__( 'Report', 'woo-lucky-wheel' ), esc_html__( 'Report', 'woo-lucky-wheel' ), 'manage_options', 'wlwl-report', array(
				$this,
				'report_callback'
			)
		);
		add_submenu_page(
			'woo-lucky-wheel', esc_html__( 'System Status', 'woo-lucky-wheel' ), esc_html__( 'System Status', 'woo-lucky-wheel' ), 'manage_options', 'wlwl-system-status', array(
				$this,
				'system_status'
			)
		);
	}


	public function preview_emails_button( $editor_id ) {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'woo-lucky-wheel' && $editor_id == 'content' ) {//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			ob_start();
			?>
            <span class="button wlwl-preview-emails-button"><?php esc_html_e( 'Preview emails', 'woo-lucky-wheel' ) ?></span>
			<?php
			echo ob_get_clean();// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function wc_price( $price, $args = array() ) {
		extract(
			apply_filters(
				'wc_price_args', wp_parse_args(
					$args, array(
						'ex_tax_label'       => false,
						'currency'           => get_option( 'woocommerce_currency' ),
						'decimal_separator'  => get_option( 'woocommerce_price_decimal_sep' ),
						'thousand_separator' => get_option( 'woocommerce_price_thousand_sep' ),
						'decimals'           => get_option( 'woocommerce_price_num_decimals', 2 ),
						'price_format'       => get_woocommerce_price_format(),
					)
				)
			)
		);
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		$price_format = '%1$s%2$s';

		switch ( $currency_pos ) {
			case 'left' :
				$price_format = '%1$s%2$s';
				break;
			case 'right' :
				$price_format = '%2$s%1$s';
				break;
			case 'left_space' :
				$price_format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space' :
				$price_format = '%2$s&nbsp;%1$s';
				break;
		}

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

	public function preview_wheel_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );
		$label         = isset( $_GET['label'] ) ? wc_clean( $_GET['label'] ) : array();//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$coupon_type   = isset( $_GET['coupon_type'] ) ? wc_clean( $_GET['coupon_type'] ) : array();//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$coupon_amount = isset( $_GET['coupon_amount'] ) ? wc_clean( $_GET['coupon_amount'] ) : array();//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$labels        = array();
		if ( is_array( $label ) && count( $label ) ) {
			for ( $i = 0; $i < count( $label ); $i ++ ) {
				$wheel_label = $label[ $i ];
				switch ( $coupon_type[ $i ] ) {
					case 'percent':
						$wheel_label = str_replace( '{coupon_amount}', $coupon_amount[ $i ] . '%', $wheel_label );
						break;
					case 'fixed_cart':
					case 'fixed_product':
						$wheel_label = str_replace( '{coupon_amount}', $this->wc_price( $coupon_amount[ $i ] ), $wheel_label );
						$wheel_label = str_replace( '&nbsp;', ' ', $wheel_label );
						break;
				}

				$labels[] = $wheel_label;
			}

		}
		wp_send_json( array( 'labels' => $labels ) );
	}

	public function preview_emails_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );
		$content              = isset( $_GET['content'] ) ? wp_kses_post( stripslashes( $_GET['content'] ) ) : '';//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$email_heading        = isset( $_GET['heading'] ) ? wc_clean( stripslashes( $_GET['heading'] ) ) : '';//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$button_shop_url      = isset( $_GET['button_shop_url'] ) ? wc_clean( stripslashes( $_GET['button_shop_url'] ) ) : '';//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$button_shop_size     = 16;
		$button_shop_color    = '#ffffff';
		$button_shop_bg_color = '#000';
		$button_shop_title    = esc_html__( 'Shop now', 'woo-lucky-wheel' );

		$button_shop_now = '<a href="' . $button_shop_url . '" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;font-size:' . $button_shop_size . 'px;color:' . $button_shop_color . ';background:' . $button_shop_bg_color . ';">' . $button_shop_title . '</a>';
		$coupon_label    = '10% OFF';
		$coupon_code     = 'LUCKY_WHEEL';
		$date_expires    = strtotime( '+30 days' );
		$customer_name   = 'John';
		$content         = str_replace( '{coupon_label}', $coupon_label, $content );
		$content         = str_replace( '{customer_name}', $customer_name, $content );
		$content         = str_replace( '{coupon_code}', '<span style="font-size: x-large;">' . strtoupper( $coupon_code ) . '</span>', $content );
		$content         = str_replace( '{date_expires}', empty( $date_expires ) ? esc_html__( 'never expires', 'woo-lucky-wheel' ) : date_i18n( 'F d, Y', ( $date_expires ) ), $content );
		$content         = str_replace( '{shop_now}', $button_shop_now, $content );
		$email_heading   = str_replace( '{coupon_label}', $coupon_label, $email_heading );

		// load the mailer class
		$mailer = WC()->mailer();

		// create a new email
		$email = new WC_Email();

		// wrap the content with the email template and then add styles
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $content ) ) );

		// print the preview email
		wp_send_json(
			array(
				'html' => $message,
			)
		);
	}

	public function search_cate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );

		ob_start();

		$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : '';
		if ( ! $keyword ) {
			$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		}
		if ( empty( $keyword ) ) {
			die();
		}
		$categories = get_terms(
			array(
				'taxonomy' => 'product_cat',
				'orderby'  => 'name',
				'order'    => 'ASC',
				'search'   => $keyword,
				'number'   => 100
			)
		);
		$items      = array();
		if ( count( $categories ) ) {
			foreach ( $categories as $category ) {
				$item    = array(
					'id'   => $category->term_id,
					'text' => $category->name
				);
				$items[] = $item;
			}
		}
		wp_send_json( $items );
	}

	public function search_product() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );

		ob_start();

		$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			die();
		}
		$arg            = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => 50,
			's'              => $keyword

		);
		$the_query      = new WP_Query( $arg );
		$found_products = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$prd = wc_get_product( get_the_ID() );

				if ( $prd->has_child() && $prd->is_type( 'variable' ) ) {

					$product_children = $prd->get_children();

					if ( count( $product_children ) ) {
						foreach ( $product_children as $product_child ) {
							if ( woocommerce_version_check() ) {
								$product = array(
									'id'   => $product_child,
									'text' => get_the_title( $product_child )
								);

							} else {
								$child_wc  = wc_get_product( $product_child );
								$get_atts  = $child_wc->get_variation_attributes();
								$attr_name = array_values( $get_atts )[0];
								$product   = array(
									'id'   => $product_child,
									'text' => get_the_title() . ' - ' . $attr_name
								);

							}
							$found_products[] = $product;
						}

					}
				}
				$product_id    = get_the_ID();
				$product_title = get_the_title();
				$the_product   = new WC_Product( $product_id );
				if ( ! $the_product->is_in_stock() ) {
					$product_title .= ' (out-of-stock)';
				}
				$product          = array( 'id' => $product_id, 'text' => $product_title );
				$found_products[] = $product;

			}
		}
		wp_send_json( $found_products );
	}

	public function search_coupon() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );
		ob_start();
		$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$arg            = array(
			'post_status'    => 'publish',
			'post_type'      => 'shop_coupon',
			'posts_per_page' => 50,
			'meta_query'     => array(//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => 'wlwl_unique_coupon',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => 'kt_unique_coupon',
					'compare' => 'NOT EXISTS'
				)
			),
			's'              => $keyword
		);
		$the_query      = new WP_Query( $arg );
		$found_products = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$coupon = new WC_Coupon( get_the_ID() );
				if ( $coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
					continue;
				}
				if ( $coupon->get_date_expires() && time() > $coupon->get_date_expires()->getTimestamp() ) {
					continue;
				}
				$product          = array( 'id' => get_the_ID(), 'text' => get_the_title() );
				$found_products[] = $product;
			}
		}
		wp_send_json( $found_products );
	}

	public function search_suggested_product() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_ajax_referer( 'wlwl_nonce', 'nonce' );

		ob_start();

		$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			die();
		}
		$arg            = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => 50,
			's'              => $keyword

		);
		$the_query      = new WP_Query( $arg );
		$found_products = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				$product_id    = get_the_ID();
				$product_title = get_the_title();
				$the_product   = new WC_Product( $product_id );
				if ( ! $the_product->is_in_stock() ) {
					$product_title .= ' (out-of-stock)';
				}
				$product          = array( 'id' => $product_id, 'text' => $product_title );
				$found_products[] = $product;
			}
		}
		wp_send_json( $found_products );
	}

	public function admin_enqueue() {
		$this->settings::enqueue_style(
			array( 'woocommerce-lucky-wheel-admin-icon-style' ),
			array( 'admin-icon-style' ),
			array( 0 )
		);
		if ( isset( $_REQUEST['page'] ) && wc_clean($_REQUEST['page']) == 'woo-lucky-wheel' ) {//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->settings::remove_other_script();
			wp_enqueue_editor();
			$this->settings::enqueue_style(
				array(
					'wlwl_semantic-ui-accordion',
					'wlwl_semantic-ui-button',
					'wlwl_semantic-ui-checkbox',
					'wlwl_semantic-ui-dropdown',
					'wlwl_semantic-ui-segment',
					'wlwl_semantic-ui-form',
					'wlwl_semantic-ui-label',
					'wlwl_semantic-ui-input',
					'wlwl_semantic-ui-icon',
					'wlwl_semantic-ui-table',
					'wlwl_semantic-ui-message',
					'wlwl_semantic-ui-menu',
					'wlwl_semantic-ui-tab',
					'wlwl_transition',
					'wlwl_select2',
				),
				array(
					'accordion',
					'button',
					'checkbox',
					'dropdown',
					'segment',
					'form',
					'label',
					'input',
					'icon',
					'table',
					'message',
					'menu',
					'tab',
					'transition',
					'select2',
				),
				array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 )
			);
			$this->settings::enqueue_style(
				array(
					'woocommerce-lucky-wheel-admin-settings',
					'woocommerce-lucky-wheel-fontselect',
				),
				array( 'admin-style',  'fontselect-default' ),
				array()
			);
			wp_enqueue_script( 'jquery-ui-sortable' );
			/*Color picker*/
			wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), VI_WOO_LUCKY_WHEEL_VERSION, true );

			wp_enqueue_script( 'media-upload' );
			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
			$this->settings::enqueue_script(
				array(
					'wordpress-lucky-wheel-fontselect',
					'wordpress-lucky-wheel-address',
					'wlwl_semantic-ui-checkbox',
					'wlwl_semantic-ui-dropdown',
					'wlwl_semantic-ui-accordion',
					'wlwl_semantic-ui-tab',
					'wlwl_transition',
					'wlwl_select2'
				),
				array(
					'jquery.fontselect',
					'address',
					'checkbox',
					'dropdown',
					'accordion',
					'tab',
					'transition',
					'select2'
				),
				array( 1, 1, 1, 1, 1, 1, 1, 1 )
			);
			$this->settings::enqueue_script(
				array( 'woocommerce-lucky-wheel-admin-javascript' ),
				array( 'admin-javascript' ),
				array( 0 ),
			);
			wp_localize_script( 'woocommerce-lucky-wheel-admin-javascript', 'woo_lucky_wheel_params_admin', array(
				'url' => admin_url( 'admin-ajax.php' ),
				'bg_img_default'   => VI_WOO_LUCKY_WHEEL_IMAGES . '2020.png',
				'nonce' => wp_create_nonce( 'wlwl_nonce' ),
				'time_on_close' => $this->settings->get_params( 'notify', 'time_on_close' ) ,
				'show_again' =>  $this->settings->get_params( 'notify', 'show_again' )  ,
				'time_on_close_unit' => $this->settings->get_params( 'notify', 'time_on_close_unit' ) ,
				'show_again_unit' =>  $this->settings->get_params( 'notify', 'show_again_unit' )  ,
			) );
		}
	}

	public function settings_page() {
		$tabs       = array(
			'general'   => esc_html__( 'General', 'woo-lucky-wheel' ),
			'popup'     => esc_html__( 'Pop-up', 'woo-lucky-wheel' ),
			'wheel'     => esc_html__( 'Wheel Settings', 'woo-lucky-wheel' ),
			'coupon'     => esc_html__( 'Unique Coupon', 'woo-lucky-wheel' ),
			'email'     => esc_html__( 'Email', 'woo-lucky-wheel' ),
			'email_api' => esc_html__( 'Email API', 'woo-lucky-wheel' ),
		);
		$tab_active = array_key_first( $tabs );
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Lucky Wheel for WooCommerce Settings', 'woo-lucky-wheel' ); ?></h2>
	        <?php
	        if ( $this->error  ) {
		        printf( '<div id="message" class="error"><p><strong>%s</strong></p></div>', esc_html(  $this->error ) );
	        }
	        if ( $this->updated_sucessfully  ) {
		        printf( '<div id="message" class="updated"><p><strong>%s</strong></p></div>', esc_html__( 'Your settings have been saved!', 'woo-lucky-wheel' ) );
	        }
	        ?>
            <form action="" method="POST" class="vi-ui form">
				<?php wp_nonce_field( 'wlwl_settings_page_save', 'wlwl_nonce_field' ); ?>
                <div class="vi-ui top attached tabular menu">
		            <?php
		            foreach ( $tabs as $slug => $text ) {
			            $active = $tab_active === $slug ? 'active' : '';
			            printf( ' <div class="item %s" data-tab="%s">%s</div>', esc_attr( $active ), esc_attr( $slug ), esc_html( $text ) );
		            }
		            ?>
                </div>
	            <?php
	            foreach ( $tabs as $slug => $text ) {
		            $active = $tab_active === $slug ? ' active' : '';
		            $method = str_replace( '-', '_', $slug ) . '_options';
		            $fields = [];
		            printf( '<div class="vi-ui bottom attached%s tab segment" data-tab="%s">', esc_attr( $active ), esc_attr( $slug ) );
		            if ( method_exists( $this, $method ) ) {
			            $fields = $this->$method();
		            }
		            $this->settings::villatheme_render_table_field( apply_filters( "wlwl_settings_fields", $fields, $slug ) );
		            do_action( 'wlwl_settings_tab', $slug );
		            printf( '</div>' );
	            }
	            ?>
                <p class="wlwl-button-save-settings-container">
                    <button class="vi-ui primary button labeled icon wlw-submit" name="wlwl_save_settings">
                        <i class="icon save"></i><?php esc_html_e( 'Save', 'woo-lucky-wheel' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <div class="woocommerce-lucky-wheel-preview preview-html-hidden">
            <div class="woocommerce-lucky-wheel-preview-overlay"></div>
            <div class="woocommerce-lucky-wheel-preview-html">
                <canvas id="wlwl_canvas"></canvas>
                <canvas id="wlwl_canvas1"></canvas>
                <canvas id="wlwl_canvas2"></canvas>
            </div>
        </div>
        <?php
		do_action( 'villatheme_support_woo-lucky-wheel' );
	}
    protected function general_options(){
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="wlwl_enable"><?php esc_html_e( 'Enable', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox" name="wlwl_enable"
                               id="wlwl_enable" <?php checked( $this->settings->get_params( 'general', 'enable' ), 'on' ) ?>>
                        <label></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_enable_mobile"><?php esc_html_e( 'Small screen', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox" name="wlwl_enable_mobile"
                               id="wlwl_enable_mobile" <?php checked( $this->settings->get_params( 'general', 'mobile' ), 'on' ) ?>>
                        <label></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Allow to display wheel for screen less than 760px', 'woo-lucky-wheel' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="ajax_endpoint"><?php esc_html_e( 'Ajax endpoint', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui toggle checkbox">
                                <input type="radio" name="ajax_endpoint"
                                       id="ajax_endpoint_ajax"
                                       value="ajax" <?php checked( $this->settings->get_params( 'ajax_endpoint' ), 'ajax' ) ?>>
                                <label for="ajax_endpoint_ajax"><?php esc_html_e( 'Ajax', 'woo-lucky-wheel' ); ?></label>
                            </div>
                        </div>
                        <div class="field">
                            <div class="vi-ui toggle checkbox">
                                <input type="radio" name="ajax_endpoint"
                                       id="ajax_endpoint_rest_api"
                                       value="rest_api" <?php checked( $this->settings->get_params( 'ajax_endpoint' ), 'rest_api' ) ?>>
                                <label for="ajax_endpoint_rest_api"><?php esc_html_e( 'REST API', 'woo-lucky-wheel' ); ?></label>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_spin_num"><?php esc_html_e( 'Times spinning per email', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input type="number" id="wlwl_spin_num" name="wlwl_spin_num" min="1"
                           value="<?php echo esc_attr( $this->settings->get_params( 'general', 'spin_num' ) ); ?>">
                    <p class="description">
	                    <?php esc_html_e( 'Leave empty to not set the limit.', 'woo-lucky-wheel' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_delay"><?php esc_html_e( 'Delay between each spin of an email', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="vi-ui right labeled fluid input">
                        <input type="number" id="wlwl_delay" name="wlwl_delay"
                               min="0"
                               value="<?php echo esc_attr( $this->settings->get_params( 'general', 'delay' ) ); ?>">
                        <select name="wlwl_delay_unit" class="vi-ui dropdown label">
                            <option value="s" <?php selected( $this->settings->get_params( 'general', 'delay_unit' ), 's' ) ?>>
			                    <?php esc_html_e( 'Seconds', 'woo-lucky-wheel' ); ?>
                            </option>
                            <option value="m" <?php selected( $this->settings->get_params( 'general', 'delay_unit' ), 'm' ) ?>><?php esc_html_e( 'Minutes', 'woo-lucky-wheel' ); ?></option>
                            <option value="h" <?php selected( $this->settings->get_params( 'general', 'delay_unit' ), 'h' ) ?>><?php esc_html_e( 'Hours', 'woo-lucky-wheel' ); ?></option>
                            <option value="d" <?php selected( $this->settings->get_params( 'general', 'delay_unit' ), 'd' ) ?>><?php esc_html_e( 'Days', 'woo-lucky-wheel' ); ?></option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Choose using white/black list', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Auto reset spins', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p class="description">
                        <?php esc_html_e('Reset the total spins of every email to zero at a specific time', 'woo-lucky-wheel' ); ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>
        <?php
        return '';
    }
	protected function popup_options(){
        ?>
        <div class="vi-ui secondary pointing tabular attached top menu">
            <div class="active item" data-tab="popup_general">
                <?php esc_html_e( 'General', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item" data-tab="popup_icon">
                <?php esc_html_e( 'Icon Design', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item" data-tab="popup_assign">
                <?php esc_html_e( 'Assign Page', 'woo-lucky-wheel' ) ?>
            </div>
        </div>
        <?php
		$notify_intent = $this->settings->get_params( 'notify', 'intent' );
		ob_start();
		?>
        <select name="notify_intent" class="vi-ui fluid dropdown">
            <option value="popup_icon" <?php selected( $this->settings->get_params( 'notify', 'intent' ), 'popup_icon' ) ?>><?php esc_html_e( 'Popup icon', 'woo-lucky-wheel' ); ?></option>
            <option value="show_wheel" <?php selected( $this->settings->get_params( 'notify', 'intent' ), 'show_wheel' ) ?>><?php esc_html_e( 'Automatically show wheel after initial time', 'woo-lucky-wheel' ); ?></option>
            <option value="on_scroll" disabled><?php esc_html_e( 'Show wheel after users scroll down a specific value - Premium version only', 'woo-lucky-wheel' ); ?></option>
            <option value="on_exit" disabled><?php esc_html_e( 'Show wheel when users move mouse over the top to close browser - Premium version only', 'woo-lucky-wheel' ); ?></option>
            <option value="random" disabled><?php esc_html_e( 'Random one of these above - Premium version only', 'woo-lucky-wheel' ); ?></option>
        </select>
		<?php
		$notify_intent_html = ob_get_clean();
		$time_on_close_unit = $this->settings->get_params( 'notify', 'time_on_close_unit' );
		ob_start();
		?>
        <div class="vi-ui right labeled fluid input">
            <input type="number" id="notify_time_on_close" name="notify_time_on_close"
                   min="0"
                   value="<?php echo esc_attr( $this->settings->get_params( 'notify', 'time_on_close' ) ); ?>">
            <select name="notify_time_on_close_unit" class="vi-ui label dropdown">
                <option value="m" <?php selected( $time_on_close_unit, 'm' ) ?>><?php esc_html_e( 'Minutes', 'woo-lucky-wheel' ); ?></option>
                <option value="h" <?php selected( $time_on_close_unit, 'h' ) ?>><?php esc_html_e( 'Hours', 'woo-lucky-wheel' ); ?></option>
                <option value="d" <?php selected( $time_on_close_unit, 'd' ) ?>><?php esc_html_e( 'Days', 'woo-lucky-wheel' ); ?></option>
            </select>
        </div>
		<?php
		$notify_time_on_close_html = ob_get_clean();
		$show_again_unit           = $this->settings->get_params( 'notify', 'show_again_unit' );
		ob_start();
		?>
        <div class="vi-ui right labeled fluid input">
            <input type="number" id="notify_show_again" name="notify_show_again"
                   min="0"
                   value="<?php echo esc_attr( $this->settings->get_params( 'notify', 'show_again' ) ); ?>">
            <select name="notify_show_again_unit" class="vi-ui label dropdown">
                <option value="s" <?php selected( $show_again_unit, 's' ) ?>><?php esc_html_e( 'Seconds', 'woo-lucky-wheel' ); ?></option>
                <option value="m" <?php selected( $show_again_unit, 'm' ) ?>><?php esc_html_e( 'Minutes','woo-lucky-wheel' ); ?></option>
                <option value="h" <?php selected( $show_again_unit, 'h' ) ?>><?php esc_html_e( 'Hours', 'woo-lucky-wheel' ); ?></option>
                <option value="d" <?php selected( $show_again_unit, 'd' ) ?>><?php esc_html_e( 'Days', 'woo-lucky-wheel' ); ?></option>
            </select>
        </div>
		<?php
		$notify_show_again_html = ob_get_clean();
		$args                   = [
			'notify_intent'        => [
				'title' => esc_html__( 'Action required to open the popup', 'woo-lucky-wheel' ),
				'html'  => $notify_intent_html,
			],
			'show_wheel'           => [
				'title' => esc_html__( 'Initial time', 'woo-lucky-wheel' ),
				'desc'  => esc_html__( 'Gap time before the popup icon appears after the action to trigger is done. This gap time is selected randomly within the range you add. Enter min,max time (seconds). For example: 1,2', 'woo-lucky-wheel' ),
				'html'  => sprintf( '<div class="vi-ui right labeled input">
                                    <input type="text" id="show_wheel" name="show_wheel"
                                           value="%s">
                                    <label class="vi-ui label">%s</label>
                                </div>', esc_attr( $this->settings->get_params( 'notify', 'show_wheel' ) ),
					esc_html__( 'Seconds', 'woo-lucky-wheel' ) ),
			],
			'notify_time_on_close' => [
				'title' => esc_html__( 'If the wheel is closed without a spin, show the popup again after', 'woo-lucky-wheel' ),
				'html'  => $notify_time_on_close_html,
			],
			'notify_show_again'    => [
				'title' => esc_html__( 'After one spin, show the popup again after', 'woo-lucky-wheel' ),
				'html'  => $notify_show_again_html,
			],
		];
		$fields                 = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'active'    => 1,
//				'class'     => 'wlwl-popup-general-accordion',
//				'title'     => esc_html__( 'Popup General', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields'        => $args,
		];
        ?>
        <div class="vi-ui bottom attached active tab" data-tab="popup_general">
            <?php
            $this->settings::villatheme_render_table_field( $fields );
            ?>
        </div>
        <?php
		$notify_position = $this->settings->get_params( 'notify', 'position' );
		ob_start();
		?>
        <select name="notify_position" id="notify_position" class="vi-ui fluid dropdown">
            <option value="top-left" <?php selected( $notify_position, 'top-left' ) ?>><?php esc_html_e( 'Top Left', 'woo-lucky-wheel' ); ?></option>
            <option value="top-right" <?php selected( $notify_position, 'top-right' ) ?>><?php esc_html_e( 'Top Right', 'woo-lucky-wheel' ); ?></option>
            <option value="middle-left" <?php selected( $notify_position, 'middle-left' ) ?>><?php esc_html_e( 'Middle Left', 'woo-lucky-wheel' ); ?></option>
            <option value="middle-right" <?php selected( $notify_position, 'middle-right' ) ?>><?php esc_html_e( 'Middle Right', 'woo-lucky-wheel' ); ?></option>
            <option value="bottom-left" <?php selected( $notify_position, 'bottom-left' ) ?>><?php esc_html_e( 'Bottom Left', 'woo-lucky-wheel' ); ?></option>
            <option value="bottom-right" <?php selected( $notify_position, 'bottom-right' ) ?>><?php esc_html_e( 'Bottom Right','woo-lucky-wheel' ); ?></option>
        </select>
		<?php
		$notify_position_html = ob_get_clean();
		ob_start();
		?>
        <a class="vi-ui button" target="_blank"
           href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
		<?php
		$popup_icon_html     = ob_get_clean();
		ob_start();
		?>
        <div class="equal width fields">
            <div class="field">
                <a class="vi-ui button" target="_blank"
                   href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                <p class="description"><?php esc_html_e( 'Color', 'woo-lucky-wheel' ); ?></p>
            </div>
            <div class="field">
                <a class="vi-ui button" target="_blank"
                   href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                <p class="description"><?php esc_html_e( 'Background', 'woo-lucky-wheel' ); ?></p>
            </div>
        </div>
		<?php
		$popup_icon_design_html = ob_get_clean();
		ob_start();
		?>
        <div class="vi-ui toggle checkbox">
            <input type="checkbox" name="notify_hide_popup"
                   id="notify_hide_popup" <?php checked( $this->settings->get_params( 'notify', 'hide_popup' ), 'on' ) ?>>
            <label for="notify_hide_popup"></label>
        </div>
		<?php
		$popup_icon_hide_html = ob_get_clean();
		$args                 = [
			'notify_position'   => [
				'title' => esc_html__( 'Popup icon position', 'woo-lucky-wheel' ),
				'desc'  => esc_html__( 'Position of the popup on screen', 'woo-lucky-wheel' ),
				'html'  => $notify_position_html,
			],
			'popup_icon'        => [
				'title' => esc_html__( 'Custom popup icon', 'woo-lucky-wheel' ),
				'html'  => $popup_icon_html,
			],
			'popup_design'      => [
				'title' => esc_html__( 'Custom popup icon design', 'woo-lucky-wheel' ),
				'html'  => $popup_icon_design_html,
			],
			'notify_hide_popup' => [
				'title' => esc_html__( 'Hide popup icon', 'woo-lucky-wheel' ),
				'desc'  => esc_html__( 'Enable to hide the popup icon after the user closes the wheel.', 'woo-lucky-wheel' ),
				'html'  => $popup_icon_hide_html,
			],
		];
		$fields               = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-popup-icon-accordion',
//				'title'     => esc_html__( 'Popup Icon', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields'        => $args,
		];
		?>
        <div class="vi-ui bottom attached tab" data-tab="popup_icon">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		ob_start();
		?>
        <input type="text" name="notify_conditional_tags"
               placeholder="<?php esc_html_e( 'Ex: !is_page(array(123,41,20))', 'woo-lucky-wheel' ) ?>"
               id="notify_conditional_tags"
               value="<?php if ( $this->settings->get_params( 'notify', 'conditional_tags' ) ) {
			       echo esc_attr( htmlentities( $this->settings->get_params( 'notify', 'conditional_tags' ) ) );
		       } ?>">
        <p class="description"><?php esc_html_e( 'Let you control on which pages Woocommerce Lucky wheel icon appears using ', 'woo-lucky-wheel' ) ?>
            <a href="https://codex.wordpress.org/Conditional_Tags"><?php esc_html_e( 'WP\'s conditional tags', 'woo-lucky-wheel' ) ?></a>
        </p>
        <p class="description">
            <strong>*</strong><?php esc_html_e( '"Home page", "Blog page" and "Shop page" options above must be disabled to run these conditional tags.', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description"><?php esc_html_e( 'Use ', 'woo-lucky-wheel' ); ?>
            <strong>is_cart()</strong><?php esc_html_e( ' to show only on cart page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description"><?php esc_html_e( 'Use ', 'woo-lucky-wheel' ); ?>
            <strong>is_checkout()</strong><?php esc_html_e( ' to show only on checkout page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description"><?php esc_html_e( 'Use ', 'woo-lucky-wheel' ); ?>
            <strong>is_product_category()</strong><?php esc_html_e( 'to show only on WooCommerce category page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description"><?php esc_html_e( 'Use ', 'woo-lucky-wheel' ); ?>
            <strong>is_shop()</strong><?php esc_html_e( ' to show only on WooCommerce shop page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description"><?php esc_html_e( 'Use ', 'woo-lucky-wheel' ); ?>
            <strong>is_product()</strong><?php esc_html_e( ' to show only on WooCommerce single product page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description">
            <strong>**</strong><?php esc_html_e( 'Combining 2 or more conditionals using || to show wheel if 1 of the conditionals matched. e.g use ', 'woo-lucky-wheel' ); ?>
            <strong>is_cart() ||
                is_checkout()</strong><?php esc_html_e( ' to show only on cart page and checkout page', 'woo-lucky-wheel' ) ?>
        </p>
        <p class="description">
            <strong>***</strong><?php esc_html_e( 'Use exclamation mark(!) before a conditional to hide wheel if the conditional matched. e.g use ', 'woo-lucky-wheel' ); ?>
            <strong>!is_home()</strong><?php esc_html_e( ' to hide wheel on homepage', 'woo-lucky-wheel' ) ?>
        </p>
		<?php
		$notify_conditional_tags_html = ob_get_clean();
		$args                         = [
			'notify_frontpage_only'   => [
				'title' => esc_html__( 'Show only on Homepage', 'woo-lucky-wheel' ),
				'html'  => sprintf( '<div class="vi-ui toggle checkbox">
                                    <input type="checkbox" name="notify_frontpage_only"
                                           id="notify_frontpage_only" %s>
                                    <label></label>
                                </div>', $this->settings->get_params( 'notify', 'show_only_front' ) == 'on' ? ' checked' : '' ),
			],
			'notify_blogpage_only'    => [
				'title' => esc_html__( 'Show only on Blog page', 'woo-lucky-wheel' ),
				'html'  => sprintf( '<div class="vi-ui toggle checkbox">
                                    <input type="checkbox" name="notify_blogpage_only"
                                           id="notify_blogpage_only" %s>
                                    <label></label>
                                </div>', $this->settings->get_params( 'notify', 'show_only_blog' ) == 'on' ? ' checked' : '' ),
			],
			'notify_shop_only'    => [
				'title' => esc_html__( 'Show only on Shop page', 'woo-lucky-wheel' ),
				'html'  => sprintf( '<div class="vi-ui toggle checkbox">
                                    <input type="checkbox" name="notify_shop_only"
                                           id="notify_shop_only" %s>
                                    <label></label>
                                </div>', $this->settings->get_params( 'notify', 'show_only_shop' ) == 'on' ? ' checked' : '' ),
				'desc' => __('Enable to make the popup icon only work on the Shop page', 'woo-lucky-wheel' )
			],
			'notify_conditional_tags' => [
				'title' => esc_html__( 'Conditional tags', 'woo-lucky-wheel' ),
				'html'  => $notify_conditional_tags_html,
			],
		];
		$fields                       = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-popup-assign-accordion',
//				'title'     => esc_html__( 'Popup Assign', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields'        => $args,
		];
		?>
        <div class="vi-ui bottom attached tab" data-tab="popup_assign">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
        return '';
	}
	protected function wheel_options() {
        ?>
        <div class="vi-ui secondary pointing tabular attached top menu">
            <div class="item" data-tab="wheel_fields">
				<?php esc_html_e( 'Input fields', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item active" data-tab="wheel_sildes">
				<?php esc_html_e( 'Wheel Slides', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item" data-tab="wheel_after_spining">
				<?php esc_html_e( 'After Finishing Spinning', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item" data-tab="wheel_design">
				<?php esc_html_e( 'Design', 'woo-lucky-wheel' ) ?>
            </div>
            <div class="item" data-tab="wheel_recaptcha">
				<?php esc_html_e( 'Google reCAPTCHA', 'woo-lucky-wheel' ) ?>
            </div>
        </div>
        <?php
		ob_start();
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php esc_html_e( 'Field', 'woo-lucky-wheel' ); ?></th>
                <th><?php esc_html_e( 'Enable', 'woo-lucky-wheel' ); ?></th>
                <th><?php esc_html_e( 'On mobile', 'woo-lucky-wheel' );?></th>
                <th><?php esc_html_e( 'Required', 'woo-lucky-wheel' );?></th>
                <th><?php esc_html_e( 'Country code', 'woo-lucky-wheel' );?></th>
            </tr>
            <tr>
                <th>
                    <label for="custom_field_name_enable"><?php esc_html_e( 'Name', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input class="custom_field_name_enable" type="checkbox"
                               id="custom_field_name_enable"
                               name="custom_field_name_enable"
                               value="on" <?php checked( $this->settings->get_params( 'custom_field_name_enable' ), 'on' ) ?>>
                        <label></label>
                    </div>
                </td>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input class="custom_field_name_enable_mobile" type="checkbox"
                               id="custom_field_name_enable_mobile"
                               name="custom_field_name_enable_mobile"
                               value="on" <?php checked( $this->settings->get_params( 'custom_field_name_enable_mobile' ), 'on' ) ?>>
                        <label></label>
                    </div>
                </td>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input class="custom_field_name_required" type="checkbox"
                               id="custom_field_name_required"
                               name="custom_field_name_required"
                               value="on" <?php checked( $this->settings->get_params( 'custom_field_name_required' ), 'on' ) ?>>
                        <label></label>
                    </div>
                </td>
                <td>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="custom_field_mobile_enable"><?php esc_html_e( 'Phone number', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_fields_html = ob_get_clean();
		$fields            = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-wheel-fields-accordion',
//				'title'     => esc_html__( 'Wheel fields', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_fields_html,
		];
		?>
        <div class="vi-ui bottom attached tab" data-tab="wheel_fields">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		ob_start();
		?>
        <span class="vi-ui positive button preview-lucky-wheel labeled icon tiny">
            <i class="icon eye"></i><?php esc_html_e( 'Preview Wheel', 'woo-lucky-wheel' ); ?>
        </span>
        <div class="vi-ui message positive tiny">
            <ul class="list">
                <li><?php echo wp_kses_post( __('Use <strong>{coupon_amount}</strong> for WooCommerce coupon type to refer to the amount of that coupon. e.g: Coupon type is percentage discount, coupon value is 10 then <strong>{coupon_amount}</strong> will become 10% when printing out on the wheel.','woo-lucky-wheel' ) ); ?></li>
                <li>
		            <?php
		            echo wp_kses_post(__('You can use <a href="https://1.envato.market/BZZv1" target="_blank">WooCommerce Email Template Customizer</a> or <a href="http://bit.ly/woo-email-template-customizer" target="_blank">Email Template Customizer for WooCommerce</a> to create and customize your own email template for each prize. If no email template is selected, the default setting at <a href="#email">the \'Email\' tab</a> will be used.','woo-lucky-wheel'));
		            ?>
                </li>
	            <?php
	            if ( VI_WOO_LUCKY_WHEEL_Plugins_WooCommerce_Email_Template_Customizer::$is_active ) {
		            ?>
                    <li>
                        <a href="edit.php?post_type=viwec_template"
                           target="_blank"><?php esc_html_e( 'View all Email templates', 'woo-lucky-wheel' ) ?></a>
			            <?php esc_html_e( 'or', 'woo-lucky-wheel' ) ?>
                        <a href="post-new.php?post_type=viwec_template&sample=wlwl_coupon_email&style=basic"
                           target="_blank"><?php esc_html_e( 'Create a new email template', 'woo-lucky-wheel' ) ?></a>
                    </li>
                    <li>
			            <?php printf( esc_html( 'Important note: The custom email template must be assigned to each index (wheel segment). Otherwise, notification for that segment will use the default generic email instead. For more info, please see this %s.' ), '<a target="_blank" href="https://docs.villatheme.com/woocommerce-email-template-customizer/#configuration_child_menu_4818">documentation</a>' ); ?>
                    </li>
		            <?php
	            }
	            ?>
                <li><?php esc_html_e('You can add only 6 slides. Please update to the premium version to add unlimited slices.','woo-lucky-wheel' )  ?>
                    <a class="vi-ui tiny button" href="https://1.envato.market/qXBNY"
                       target="_blank"><?php esc_html_e( 'Unlock This Feature', 'woo-lucky-wheel' ); ?> </a></li>
            </ul>
        </div>
        <table class="form-table wheel-settings" >
            <tbody>
            <tr class="wheel-slices" style="background-color: #000000;">
                <td width="40"><?php esc_attr_e( 'Index', 'woo-lucky-wheel' ) ?></td>
                <td><?php esc_attr_e( 'Coupon Type', 'woo-lucky-wheel' ) ?></td>
                <td><?php esc_attr_e( 'Label', 'woo-lucky-wheel' ) ?></td>
                <td><?php esc_attr_e( 'Value', 'woo-lucky-wheel' ) ?></td>
                <td><?php esc_attr_e( 'Probability(%)', 'woo-lucky-wheel' ) ?></td>
                <td><?php esc_attr_e( 'Color', 'woo-lucky-wheel' ) ?></td>
	            <?php do_action('wlwl_wheel_settings_slices_column'); ?>
            </tr>
            </tbody>
            <tbody class="ui-sortable">
			<?php
			$coupon_type         = $this->settings->get_params( 'wheel', 'coupon_type' );
			if (!is_array($coupon_type)){
				$coupon_type = [];
			}
			$coupon_count        = count( $coupon_type );
			for ( $count = 0; $count < $coupon_count; $count ++ ) {
				?>
                <tr class="wheel_col <?php echo esc_attr( "wheel_col-{$coupon_type[ $count ]}" ); ?>">
                    <td class="wheel_col_index" width="40"><?php echo esc_html( $count + 1 ); ?></td>
                    <td class="wheel_col_coupons">
                        <select name="coupon_type[]" class="coupons_select vi-ui fluid dropdown">
                            <option value="non" <?php selected( $coupon_type[ $count ], 'non' ); ?>><?php esc_attr_e( 'Non', 'woo-lucky-wheel' ) ?></option>
                            <option value="existing_coupon" <?php selected( $coupon_type[ $count ], 'existing_coupon' ); ?>><?php esc_attr_e( 'Existing coupon', 'woo-lucky-wheel' ) ?></option>
                            <option value="percent" <?php selected( $coupon_type[ $count ], 'percent' ); ?>><?php esc_attr_e( 'Percentage discount', 'woo-lucky-wheel' ) ?></option>
                            <option value="fixed_product" <?php selected( $coupon_type[ $count ], 'fixed_product' ); ?>><?php esc_attr_e( 'Fixed product discount', 'woo-lucky-wheel' ) ?></option>
                            <option value="fixed_cart" <?php selected( $coupon_type[ $count ], 'fixed_cart' ); ?>><?php esc_attr_e( 'Fixed cart discount', 'woo-lucky-wheel' ) ?></option>
                            <option value="custom" <?php selected( $coupon_type[ $count ], 'custom' ); ?>><?php esc_attr_e( 'Custom', 'woo-lucky-wheel' ) ?></option>
                        </select>
                    </td>
                    <td class="wheel_col_coupons_value">
                        <input type="text" name="custom_type_label[]"
							<?php
							echo ' class="custom_type_label" value="' . ( ( isset( $this->settings->get_params( 'wheel', 'custom_label' )[ $count ] ) && $this->settings->get_params( 'wheel', 'custom_label' )[ $count ] ) ? esc_attr( $this->settings->get_params( 'wheel', 'custom_label' )[ $count ] ) : esc_attr( $this->settings->get_params( 'wheel', 'label_coupon' ) ) ) . '"';
							?> placeholder="Label"/>
                    </td>
                    <td class="wheel_col_coupons_value">
                        <input type="number" name="coupon_amount[]" min="0"
                               class="coupon_amount <?php echo ( $coupon_type[ $count ] == 'non' ) ? 'coupon-amount-readonly' : ''; ?>"
                               value="<?php echo esc_attr( $this->settings->get_params( 'wheel', 'coupon_amount' )[ $count ] ); ?>"
                               placeholder="Coupon Amount"
                               style="<?php if ( ! in_array( $coupon_type[ $count ], array(
							       'percent',
							       'fixed_product',
							       'fixed_cart',
							       'non'
						       ) ) ) {
							       echo esc_attr( 'display:none;' );
						       } ?>" <?php if ( isset( $coupon_type[ $count ] ) && $coupon_type[ $count ] == 'non' ) {
							echo esc_attr( 'readonly' );
						} ?>/>
                        <input type="text" name="custom_type_value[]" class="custom_type_value"
                               value="<?php echo isset( $this->settings->get_params( 'wheel', 'custom_value' )[ $count ] ) ? esc_attr( $this->settings->get_params( 'wheel', 'custom_value' )[ $count ] ) : ''; ?>"
                               placeholder="Value/Code"
                               style="<?php if ( in_array( $coupon_type[ $count ], array(
							       'existing_coupon',
							       'percent',
							       'fixed_product',
							       'fixed_cart',
							       'non'
						       ) ) ) {
							       echo esc_attr( 'display:none;' );
						       } ?>"/>
                        <div class="wlwl_existing_coupon"
                             style="<?php if ( $coupon_type[ $count ] != 'existing_coupon' ) {
							     echo esc_attr( 'display:none;' );
						     } ?>">
                            <select name="wlwl_existing_coupon[]"
                                    class="coupon-search wlwl_existing_coupon select2-selection--single"
                                    data-placeholder="<?php esc_html_e( 'Enter Code', 'woo-lucky-wheel' ) ?>">
								<?php
								if ( isset( $this->settings->get_params( 'wheel', 'existing_coupon' )[ $count ] ) && '' !== $this->settings->get_params( 'wheel', 'existing_coupon' )[ $count ] ) {
									echo '<option value="' . esc_attr( $this->settings->get_params( 'wheel', 'existing_coupon' )[ $count ] ) . '" selected>' . ( isset( get_post( $this->settings->get_params( 'wheel', 'existing_coupon' )[ $count ] )->post_title ) ? esc_html( get_post( $this->settings->get_params( 'wheel', 'existing_coupon' )[ $count ] )->post_title ) : "" ) . '</option>';
								} else {
									echo '<option value=""></option>';
								}
								?>
                            </select>
                        </div>

                    </td>
                    <td class="wheel_col_probability">
                        <input type="number" name="probability[]"
                               class="probability probability_<?php echo esc_attr( $count ); ?>" min="0"
                               max="100" placeholder="Probability"
                               value="<?php echo absint( $this->settings->get_params( 'wheel', 'probability' )[ $count ] ) ?>"/>
                    </td>
                    <td class="remove_field_wrap">
                        <input type="text" id="bg_color" name="bg_color[]" class="color-picker"
                               value=" <?php echo esc_attr( trim( $this->settings->get_params( 'wheel', 'bg_color' )[ $count ] ) ); ?>"
                               style="background: <?php echo esc_attr( trim( $this->settings->get_params( 'wheel', 'bg_color' )[ $count ] ) ); ?>"/>
                        <span class="remove_field negative vi-ui button"><?php esc_attr_e( 'Remove', 'woo-lucky-wheel' ); ?></span>
                        <span class="clone_piece positive vi-ui button"><?php esc_attr_e( 'Clone', 'woo-lucky-wheel' ); ?></span>
                    </td>
	                <?php do_action('wlwl_wheel_settings_slices_column_content',$count); ?>
                </tr>
				<?php
			}
			?>
            <tbody>
            <tr>
                <td class="col_add_new" colspan="3">
                    <i><?php esc_attr_e( 'You can drag and drop slices to rearrange them.', 'woo-lucky-wheel' ); ?></i>
                </td>

                <td class="col_add_new col_total_probability">
                    <i><?php esc_attr_e( '*The total Probability: ', 'woo-lucky-wheel' ); ?>
                        <strong class="total_probability" data-total_probability=""> 100 </strong> (
                        % )</i>
                </td>
                <td></td>
                <td class="col_add_new">
					<?php
					self::auto_color();
					?>
                    <p>
                        <span class="auto_color positive vi-ui button"><?php esc_attr_e( 'Auto Color', 'woo-lucky-wheel' ) ?></span>
                    </p>

                    <p>
                        <a class="vi-ui button" target="_blank"
                           href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Random Color - Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                        <p class="description"><?php esc_html_e( 'Color is set randomly from predefined sets for each visitor', 'woo-lucky-wheel' ) ?></p>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields     = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'active'    => 1,
//				'class'     => 'wlwl-wheel-slide-accordion',
//				'title'     => esc_html__( 'Wheel Slides', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];
		?>
        <div class="vi-ui bottom attached tab active" data-tab="wheel_sildes">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		$congratulations_effect = $this->settings->get_params( 'wheel_wrap', 'congratulations_effect' );
		ob_start();
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="result_win"><?php esc_html_e( 'Automatically hide wheel after', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui right labeled input">
                        <input type="number" name="result-auto_close" min="0"
                               id="result-auto_close"
                               value="<?php echo intval( $this->settings->get_params( 'result', 'auto_close' ) ) ?>">
                        <label class="vi-ui label"><?php esc_html_e( 'Seconds', 'woo-lucky-wheel' ) ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Hide the wheel in how many seconds after one spin. Leave 0 to disable this feature', 'woo-lucky-wheel'); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="congratulations_effect"><?php esc_html_e( 'Winning effect', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="result_win"><?php esc_html_e( 'Winning message if win', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
					<?php
					$frontend_message = $this->settings->get_params( 'result', 'notification' );
					$win_option = array( 'editor_height' => 200, 'media_buttons' => true );
					ob_start();
					wp_editor( stripslashes( $frontend_message['win'] ??'' ), 'result_win', $win_option );
					$result_win_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'result_win' =>[
								'not_wrap_html' => 1,
								'result_win_option' => $win_option,
								'html' => $result_win_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <ul>
                        <li>{coupon_label}
                            - <?php esc_html_e( 'Label of coupon that customers win', 'woo-lucky-wheel' ) ?></li>
                        <li>{checkout}
                            - <?php esc_html_e( '"Checkout" with link to checkout page', 'woo-lucky-wheel' ) ?></li>
                        <li>{customer_name}
                            - <?php esc_html_e( 'Customers\'name if they enter', 'woo-lucky-wheel' ) ?></li>
                        <li>{customer_email}
                            - <?php esc_html_e( 'Email that customers enter to spin', 'woo-lucky-wheel' ) ?></li>
                        <li>{coupon_code}
                            - <?php esc_html_e( 'Coupon code/custom value will be sent to customer.', 'woo-lucky-wheel' ) ?></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="result_win_custom"><?php esc_html_e( 'Winning message if prize is custom type', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_button_apply_coupon"><?php esc_html_e( '"Apply Coupon" button', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Enable to show the "Apply Coupon" button if the prize is WooCommerce Coupon.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="result_lost"><?php esc_html_e( 'Frontend message if lost', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
					<?php
					$lost_option = array( 'editor_height' => 100, 'media_buttons' => true );
					ob_start();
					wp_editor( stripslashes( $this->settings->get_params( 'result', 'notification' )['lost'] ??''), 'result_lost', $lost_option );
					$result_win_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'result_lost' =>[
								'not_wrap_html' => 1,
								'result_lost_option' => $lost_option,
								'html' => $result_win_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields     = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-wheel-after-finishing-spinning-accordion',
//				'title'     => esc_html__( 'After Finishing Spinning', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];

		?>
        <div class="vi-ui bottom attached tab" data-tab="wheel_after_spining">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		ob_start();
		?>
        <table class="form-table wheel-settings">
            <tbody class="content">
            <tr>
                <th>
                    <label for="show_full_wheel"><?php esc_html_e( 'Show full wheel', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input class="show_full_wheel" type="checkbox" id="show_full_wheel"
                               name="show_full_wheel"
                               value="on" <?php checked( $this->settings->get_params( 'wheel', 'show_full_wheel' ), 'on' ) ?>>
                        <label></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Make all wheel segments visible on desktop. By default, the wheel on desktop shows partially. Enable this option to to make it show fully.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_speed"><?php esc_html_e( 'Wheel spin', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui right labeled input">
                                <select name="wheel_speed" id="wheel_speed" class="vi-ui fluid dropdown">
									<?php
									for ( $i = 1; $i <= 10; $i ++ ) {
										$tmp_name = $i;
										if ($i !== 5){
											$selected = 'disabled';
											$tmp_name = $i . esc_html__(' - Premium version only', 'woo-lucky-wheel');
										}else{
											$selected= 'selected';
										}
										?>
                                        <option value="<?php echo esc_attr( $i ) ?>" <?php echo esc_attr($selected); ?>>
											<?php echo esc_html( $tmp_name ); ?>
                                        </option>
										<?php
									}
									?>
                                </select>
                            </div>
                            <p class="description"><?php esc_html_e( 'The number of spins per one second. For example, if you select 10, it means the wheel spins 10 rolls in one second', 'woo-lucky-wheel' ) ?></p>
                        </div>
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            <p class="description"><?php esc_html_e( 'How long the wheel will spin. Valid duration from 3 to 15 seconds', 'woo-lucky-wheel' ); ?></p>
                        </div>
                    </div></td>
            </tr>
            <tr>
                <th>
                    <label for="font_size"><?php esc_html_e( 'Adjust size', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            <p class="description"><?php esc_html_e( 'Adjust font size of text on the wheel by (%)', 'woo-lucky-wheel' ) ?></p>
                        </div>
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            <p class="description"><?php esc_html_e( 'Adjust the size of the wheel by(%)', 'woo-lucky-wheel' ) ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl-currency"><?php esc_html_e( 'Displayed Currency', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="pointer_position"><?php esc_html_e( 'Wheel pointer', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <select name="pointer_position" id="pointer_position" class="vi-ui fluid dropdown">
                                <option value="center" <?php selected( $this->settings->get_params( 'wheel_wrap', 'pointer_position' ), 'center' ) ?>><?php esc_html_e( 'Center', 'woo-lucky-wheel' ); ?></option>
                                <option value="top" disabled><?php esc_html_e( 'Top - Premium version only', 'woo-lucky-wheel' ); ?></option>
                                <option value="right" disabled><?php esc_html_e( 'Right - Premium version only', 'woo-lucky-wheel' ); ?></option>
                                <option value="bottom" disabled><?php esc_html_e( 'Bottom - Premium version only', 'woo-lucky-wheel' ); ?></option>
                                <option value="random" disabled><?php esc_html_e( 'Random - Premium version only', 'woo-lucky-wheel' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Wheel pointer position', 'woo-lucky-wheel' ); ?></p>
                        </div>
                        <div class="field">
                            <input name="pointer_color" id="pointer_color" type="text"
                                   class="color-picker"
                                   value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) ) {
								       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) );
							       } ?>"
                                   style="background-color: <?php if ( $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) ) {
								       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'pointer_color' ) );
							       } ?>;">
                            <p class="description"><?php esc_html_e( 'Wheel pointer color', 'woo-lucky-wheel' ); ?></p>
                        </div>
                        <div class="field">
                            <select name="pointer_effect" id="pointer_effect" class="vi-ui fluid dropdown">
                                <option value="0" selected><?php esc_html_e( 'None', 'woo-lucky-wheel' ); ?></option>
                                <option value="1" disabled><?php esc_html_e( 'Change color - Premium version only', 'woo-lucky-wheel' ); ?></option>
                                <option value="2" disabled><?php esc_html_e( 'Move - Premium version only', 'woo-lucky-wheel' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'The wheel pointer\'s effect while spinning', 'woo-lucky-wheel' ); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl-center-image1"><?php esc_html_e( 'Wheel center background image', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td id="wlwl-bg-image1">
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_border_color"><?php esc_html_e( 'Color', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <input name="slice_text_color" id="slice_text_color" type="text"
                                   class="color-picker"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'wheel', 'slice_text_color' ) ); ?>"
                                   style="background-color: <?php echo esc_attr( $this->settings->get_params( 'wheel', 'slice_text_color' ) ); ?>;"/>
                            <p class="description"><?php esc_html_e( 'Slices text color', 'woo-lucky-wheel' ); ?></p>
                        </div>
                        <div class="field">
                            <input name="wheel_center_color" id="wheel_center_color" type="text"
                                   class="color-picker"
                                   value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'wheel_center_color' ) ) {
								       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'wheel_center_color' ) );
							       } ?>"
                                   style="background-color: <?php if ( $this->settings->get_params( 'wheel_wrap', 'wheel_center_color' ) ) {
								       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'wheel_center_color' ) );
							       } ?>;">
                            <p class="description"><?php esc_html_e( 'Wheel center color', 'woo-lucky-wheel' ); ?></p>
                        </div>
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            <p class="description"><?php esc_html_e( 'Wheel border color', 'woo-lucky-wheel' ); ?></p>
                        </div>
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            <p class="description"><?php esc_html_e( 'Wheel border dot color', 'woo-lucky-wheel' ); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_bg_image"><?php esc_html_e( 'Background image', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td id="wlwl-bg-image">
					<?php
                    $bg_color = $this->settings->get_params( 'wheel_wrap', 'bg_color' ) ;
					$bg_image = $this->settings->get_params( 'wheel_wrap', 'bg_image' );
					$bg_image_url = $bg_image && intval( $bg_image ) ? wp_get_attachment_url( $bg_image ) : $bg_image;
					$use_bg_image_default = $bg_image_url === VI_WOO_LUCKY_WHEEL_IMAGES . '2020.png' || str_ends_with($bg_image_url,'/woocommerce-lucky-wheel/images/2020.png');
					?>
                    <select name="wheel_wrap_bg_image_type" class="vi-ui fluid dropdown wheel_wrap_bg_image_type">
                        <option value="0" <?php selected($use_bg_image_default) ?>><?php esc_html_e('Default','woo-lucky-wheel') ?></option>
                        <option value="1" <?php selected($use_bg_image_default,false) ?>><?php esc_html_e('Custom image','woo-lucky-wheel') ?></option>
                    </select>
                    <div class="wheel_wrap_bg_image_custom">
                        <div class="wlwl-image-container">
                            <input class="wheel_wrap_bg_image" name="wheel_wrap_bg_image"
                                   type="hidden"
                                   value="<?php echo esc_attr( $bg_image_url ); ?>">
                            <img style="width: 300px;background: <?php echo esc_url( $bg_color ); ?>" class="review-images"
                                 src="<?php echo esc_url( $bg_image_url ); ?>">
                            <span class="wlwl-remove-image negative vi-ui button small"><?php esc_html_e( 'Remove', 'woo-lucky-wheel' ); ?></span>
                        </div>
                        <span class="positive vi-ui button wlwl-upload-custom-img small"><?php esc_html_e( 'Add Image', 'woo-lucky-wheel' ); ?></span>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_bg_color"><?php esc_html_e( 'Background color', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input name="wheel_wrap_bg_color" id="wheel_wrap_bg_color" type="text"
                           class="color-picker"
                           value="<?php if ( $bg_color ) {
						       echo esc_attr( $bg_color );
					       } ?>"
                           style="background: <?php if ( $bg_color ) {
						       echo esc_attr( $bg_color );
					       } ?>;">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_text_color"><?php esc_html_e( 'Text color', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input name="wheel_wrap_text_color" id="wheel_wrap_text_color" type="text"
                           class="color-picker"
                           value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'text_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'text_color' ) );
					       } ?>"
                           style="background: <?php if ( $this->settings->get_params( 'wheel_wrap', 'text_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'text_color' ) );
					       } ?>;">
                    <p class="description"><?php esc_html_e( 'Text color in the wheel background content, including wheel description, text to not show the wheel again... Note: This option may be affected by your theme.', 'woo-lucky-wheel' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_description"><?php esc_html_e( 'Wheel description', 'woo-lucky-wheel' ); ?>
                    </label>
                </th>
                <td>
					<?php
					$desc_option = array( 'editor_height' => 200, 'media_buttons' => true );
					ob_start();
					wp_editor( stripslashes( $this->settings->get_params( 'wheel_wrap', 'description' ) ), 'wheel_wrap_description', $desc_option );
					$wheel_wrap_description_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'wheel_wrap_description' =>[
								'not_wrap_html' => 1,
								'wheel_desc_option' => $desc_option,
								'html' => $wheel_wrap_description_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_spin_button"><?php esc_html_e( 'Spin Wheel button', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
					<?php
					ob_start();
					?>
                    <input type="text" name="wheel_wrap_spin_button" id="wheel_wrap_spin_button"
                           value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'spin_button' ) );
					       } ?>">
					<?php
					$wheel_wrap_spin_button_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'wheel_wrap_spin_button' =>[
								'not_wrap_html' => 1,
								'html' => $wheel_wrap_spin_button_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_spin_button_color"><?php esc_html_e( 'Spin Wheel button color', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input type="text" class="color-picker" name="wheel_wrap_spin_button_color"
                           id="wheel_wrap_spin_button_color"
                           value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) );
					       } ?>"
                           style="background-color:<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'spin_button_color' ) );
					       } ?>;">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_spin_button_bg_color"><?php esc_html_e( 'Spin Wheel button background color', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input type="text" class="color-picker" name="wheel_wrap_spin_button_bg_color"
                           id="wheel_wrap_spin_button_bg_color"
                           value="<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) );
					       } ?>"
                           style="background-color:<?php if ( $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) ) {
						       echo esc_attr( $this->settings->get_params( 'wheel_wrap', 'spin_button_bg_color' ) );
					       } ?>;">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="gdpr_policy"><?php esc_html_e( 'GDPR checkbox', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input class="gdpr_policy" type="checkbox" id="gdpr_policy"
                               name="gdpr_policy"
                               value="on" <?php checked( $this->settings->get_params( 'wheel_wrap', 'gdpr' ), 'on' ) ?>>
                        <label></label>
                    </div>
                </td>
            </tr>
            <tr class="wlwl-gdpr_policy-class">
                <th>
                    <label for="gdpr_message"><?php esc_html_e( 'GDPR message', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
					<?php
					$desc_option = array( 'editor_height' => 200, 'media_buttons' => false );
					ob_start();
					wp_editor( stripslashes( $this->settings->get_params( 'wheel_wrap', 'gdpr_message' ) ), 'gdpr_message', $desc_option );
					$wheel_wrap_description_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'gdpr_message' =>[
								'not_wrap_html' => 1,
								'gdpr_message_option' => $desc_option,
								'html' => $wheel_wrap_description_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="vi-ui message positive tiny">
            <p><?php esc_html_e('The options below will be specifically reserved for the popup.','woo-lucky-wheel' ); ?></p>
        </div>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="background_effect"><?php esc_html_e( 'Background effect', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wheel_wrap_close_option"><?php esc_html_e( 'Not display wheel again', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox" name="wheel_wrap_close_option"
                               id="wheel_wrap_close_option" <?php checked( $this->settings->get_params( 'wheel_wrap', 'close_option' ), 'on' ) ?>>
                        <label></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Enable this option to show "Never", "Remind later" and "No thanks" below the Spin Wheel button. The wheel will be hidden afterward if the user clicks one of these text.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl-google-font-select"><?php esc_html_e( 'Select font', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input type="text" name="wlwl_google_font_select"
                           id="wlwl-google-font-select"
                           value="<?php echo esc_attr( $wheel_wrap_font = $this->settings->get_params( 'wheel_wrap', 'font' ) ) ?>"><span
                            class="wlwl-google-font-select-remove wlwl-cancel"
                            style="<?php if ( ! $wheel_wrap_font ) {
								echo 'display:none';
							} ?>"></span>

                </td>
            </tr>
            <tr>
                <th>
                    <label for="custom_css"><?php esc_html_e( 'Custom css', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <textarea name="custom_css"><?php echo wp_kses_post( $this->settings->get_params( 'wheel_wrap', 'custom_css' ) ) ?></textarea>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields = [
			'section_start' => [],
			'section_end'   => [],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-wheel-design-accordion',
//				'title'     => esc_html__( 'Wheel Design', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];

		?>
        <div class="vi-ui bottom attached tab" data-tab="wheel_design">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		ob_start();
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for=""><?php esc_html_e( 'Enable', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Turn on to use Google ReCaptcha', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_recaptcha_version"><?php esc_html_e( 'Version', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for=""><?php esc_html_e( 'Guide', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div>
                        <strong class="wlwl-recaptcha-v3-wrap"
                                style="<?php echo esc_attr( $this->settings->get_params( 'wlwl_recaptcha_version' ) == 3 ? '' : 'display:none;' ); ?>">
							<?php esc_html_e( 'Get Google reCAPTCHA V3 Site and Secret key', 'woo-lucky-wheel' ) ?>
                        </strong>
                        <ul>
                            <li><?php echo wp_kses_post( __( '1, Visit <a target="_blank" href="https://www.google.com/recaptcha/admin">page</a> to sign up for an API key pair with your Gmail account', 'woo-lucky-wheel') ) ?></li>
                            <li class="wlwl-recaptcha-v3-wrap"
                                style="<?php echo esc_attr( $this->settings->get_params( 'wlwl_recaptcha_version' ) == 3 ? '' : 'display:none;' ); ?>">
								<?php esc_html_e( '2, Choose reCAPTCHA v3', 'woo-lucky-wheel' ) ?>
                            </li>
                            <li><?php esc_html_e( '3, Fill in authorized domains', 'woo-lucky-wheel' ) ?></li>
                            <li><?php esc_html_e( '4, Accept terms of service and click Register button', 'woo-lucky-wheel' ) ?></li>
                            <li><?php esc_html_e( '5, Copy and paste the site and secret key into the above field', 'woo-lucky-wheel' ) ?></li>
                        </ul>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields = [
			'section_start' => [],
			'section_end'   => [ ],
//			'section_start' => [
//				'accordion' => 1,
//				'class'     => 'wlwl-wheel-grecaptcha-accordion',
//				'title'     => esc_html__( 'Google reCAPTCHA', 'woo-lucky-wheel' ),
//			],
//			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];
		?>
        <div class="vi-ui bottom attached tab" data-tab="wheel_recaptcha">
			<?php
			$this->settings::villatheme_render_table_field( $fields );
			?>
        </div>
		<?php
		return '';
	}
	protected function coupon_options(){
		?>
        <table class="form-table">
            <tbody>
            <tr class="wlwl-custom-coupon">
                <th><?php esc_html_e( 'Email restriction', 'woo-lucky-wheel' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Add received email to coupon\'s allowed emails list', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th><?php esc_html_e( 'Allow free shipping', 'woo-lucky-wheel' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox"
                               class="checkbox" <?php checked( $this->settings->get_params( 'coupon', 'allow_free_shipping' ), 'yes' ) ?>
                               name="wlwl_free_shipping" id="wlwl_free_shipping" value="yes">
                        <label for="wlwl_free_shipping"></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Check this box if the coupon grants free shipping. A ', 'woo-lucky-wheel' ) ?>
                        <a href="https://docs.woocommerce.com/document/free-shipping/"
                           target="_blank"><?php esc_html_e( 'free shipping method', 'woo-lucky-wheel' ); ?></a><?php esc_html_e( ' must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woo-lucky-wheel' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_expiry_date"><?php esc_html_e( 'Time to live', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui right labeled input">
                        <input type="number" min="0" name="wlwl_expiry_date" id="wlwl_expiry_date"
                               value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'expiry_date' ) ); ?>">
                        <label class="vi-ui label"><?php esc_html_e( 'Day(s)', 'woo-lucky-wheel' ) ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Coupon will expire after x day(s) since it\'s generated and sent', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_min_spend"><?php esc_html_e( 'Minimum spend', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <input type="text" class="short wc_input_price" name="wlwl_min_spend"
                           id="wlwl_min_spend"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'min_spend' ) ); ?>"
                           placeholder="<?php esc_html_e( 'No minimum', 'woo-lucky-wheel' ) ?>">
                    <p class="description"><?php esc_html_e( 'The minimum spend to use the coupon.', 'woo-lucky-wheel' ) ?></p>
                </td>

            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_max_spend"><?php esc_html_e( 'Maximum spend', 'woo-lucky-wheel' ) ?></label>

                </th>
                <td>
                    <input type="text" class="short wc_input_price" name="wlwl_max_spend"
                           id="wlwl_max_spend"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'max_spend' ) ); ?>"
                           placeholder="<?php esc_html_e( 'No maximum', 'woo-lucky-wheel' ) ?>">
                    <p class="description"><?php esc_html_e( 'The maximum spend to use the coupon.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th><?php esc_html_e( 'Individual use only', 'woo-lucky-wheel' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox" <?php checked( $this->settings->get_params( 'coupon', 'individual_use' ), 'yes' ) ?>
                               class="checkbox" name="wlwl_individual_use" id="wlwl_individual_use"
                               value="yes">
                        <label for="wlwl_individual_use"></label>
                    </div>
                    <p><?php esc_html_e( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th><?php esc_html_e( 'Exclude sale items', 'woo-lucky-wheel' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox">
                        <input type="checkbox" <?php checked( $this->settings->get_params( 'coupon', 'exclude_sale_items' ), 'yes' ) ?>
                               class="checkbox" name="wlwl_exclude_sale_items"
                               id="wlwl_exclude_sale_items"
                               value="yes">
                        <label for="wlwl_exclude_sale_items"></label>
                    </div>
                    <p><?php esc_html_e( 'Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are items in the cart that are not on sale.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_product_ids"><?php esc_html_e( 'Include Products', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <select id="wlwl_product_ids" name="wlwl_product_ids[]" multiple="multiple"
                            class="product-search"
                            data-placeholder="<?php esc_html_e( 'Please Fill In Your Product Title', 'woo-lucky-wheel' ) ?>">
						<?php
						$product_ids = $this->settings->get_params( 'coupon', 'product_ids' );
						if ( !empty( $product_ids ) ) {
							foreach ( $product_ids as $ps ) {
								$product = wc_get_product( $ps );
								if ( $product ) {
									?>
                                    <option selected value="<?php echo esc_attr( $ps ) ?>"><?php echo esc_html( $product->get_title() ) ?></option>
									<?php
								}
							}
						}
						?>
                    </select>
                    <p><?php esc_html_e( 'Products that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_exclude_product_ids"><?php esc_html_e( 'Exclude Products', 'woo-lucky-wheel' ) ?></label>

                </th>
                <td>
                    <select id="wlwl_exclude_product_ids" name="wlwl_exclude_product_ids[]"
                            multiple="multiple"
                            class="product-search"
                            data-placeholder="<?php esc_html_e( 'Please Fill In Your Product Title', 'woo-lucky-wheel' ) ?>">
						<?php
						$exclude_product_ids = $this->settings->get_params( 'coupon', 'exclude_product_ids' );
						if ( !empty( $exclude_product_ids ) ) {
							foreach ( $exclude_product_ids as $ps ) {
								$product = wc_get_product( $ps );
								if ( $product ) {
									?>
                                    <option selected value="<?php echo esc_attr( $ps ) ?>"><?php echo esc_html( $product->get_title() ) ?></option>
									<?php
								}
							}
						}
						?>
                    </select>
                    <p><?php esc_html_e( 'Products that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_product_categories"><?php esc_html_e( 'Include categories', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <select id="wlwl_product_categories" name="wlwl_product_categories[]"
                            multiple="multiple"
                            class="category-search"
                            data-placeholder="<?php esc_html_e( 'Please enter category name', 'woo-lucky-wheel' ) ?>">
						<?php
						$product_categories = $this->settings->get_params( 'coupon', 'product_categories' );
						if ( !empty( $product_categories ) ) {
							foreach ( $product_categories as $category_id ) {
								$category = get_term( $category_id );
								if ( $category ) {
									?>
                                    <option value="<?php echo esc_attr( $category_id ) ?>"
                                            selected><?php echo esc_html( $category->name ); ?></option>
									<?php
								}
							}
						}
						?>
                    </select>
                    <p><?php esc_html_e( 'Product categories that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_exclude_product_categories"><?php esc_html_e( 'Exclude categories', 'woo-lucky-wheel' ) ?></label>

                </th>
                <td>
                    <select id="wlwl_exclude_product_categories" name="wlwl_exclude_product_categories[]"
                            multiple="multiple"
                            class="category-search"
                            data-placeholder="<?php esc_html_e( 'Please enter category name', 'woo-lucky-wheel' ) ?>">
						<?php
						$exclude_product_categories = $this->settings->get_params( 'coupon', 'exclude_product_categories' );
						if ( !empty( $exclude_product_categories ) ) {
							foreach ( $exclude_product_categories as $category_id ) {
								$category = get_term( $category_id );
								if ( $category ) {
									?>
                                    <option value="<?php echo esc_attr( $category_id ) ?>"
                                            selected><?php echo esc_html( $category->name ); ?></option>
									<?php
								}
							}
						}
						?>
                    </select>
                    <p><?php esc_html_e( 'Product categories that the coupon will not be applied to, or that cannot be in the cart in order for the "Fixed cart discount" to be applied.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_limit_per_coupon"><?php esc_html_e( 'Usage limit per coupon', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <input type="number" class="short" name="wlwl_limit_per_coupon"
                           id="wlwl_limit_per_coupon"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'limit_per_coupon' ) ); ?>"
                           placeholder="Unlimited usage" step="1" min="0">
                    <p><?php esc_html_e( 'How many times this coupon can be used before it is void.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_limit_to_x_items"><?php esc_html_e( 'Limit usage to X items', 'woo-lucky-wheel' ) ?></label>

                </th>
                <td>
                    <input type="number" class="short" name="wlwl_limit_to_x_items"
                           id="wlwl_limit_to_x_items"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'limit_to_x_items' ) ); ?>"
                           placeholder="<?php esc_html_e( 'Apply To All Qualifying Items In Cart', 'woo-lucky-wheel' ) ?>"
                           step="1" min="0">
                    <p><?php esc_html_e( 'The maximum number of individual items this coupon can apply to when using product discount.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_limit_per_user"><?php esc_html_e( 'Usage limit per user', 'woo-lucky-wheel' ) ?></label>

                </th>
                <td>
                    <input type="number" class="short" name="wlwl_limit_per_user"
                           id="wlwl_limit_per_user"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'limit_per_user' ) ); ?>"
                           placeholder="<?php esc_html_e( 'Unlimited Usage', 'woo-lucky-wheel' ) ?>"
                           step="1" min="0">
                    <p><?php esc_html_e( 'How many times this coupon can be used by an individual user.', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr class="wlwl-custom-coupon">
                <th>
                    <label for="wlwl_coupon_code_prefix"><?php esc_html_e( 'Coupon code prefix', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <input id="wlwl_coupon_code_prefix" type="text" name="wlwl_coupon_code_prefix"
                           value="<?php echo esc_attr( $this->settings->get_params( 'coupon', 'coupon_code_prefix' ) ); ?>">
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		return '';
	}
	protected function email_options() {
		ob_start();
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="subject"><?php esc_html_e( 'Email subject', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <input id="subject" type="text" name="subject"
                           value="<?php echo esc_attr( htmlentities( $this->settings->get_params( 'result', 'email' )['subject'] ??'') ); ?>">
                    <p class="description"><?php esc_html_e( 'The subject of emails sending to customers which include discount coupon code.', 'woo-lucky-wheel' ) ?></p>
                    <p>{coupon_label}
                        - <?php esc_html_e( 'Coupon label/custom label that customers win', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="heading"><?php esc_html_e( 'Email heading', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <input id="heading" type="text" name="heading"
                           value="<?php echo esc_attr( htmlentities( $this->settings->get_params( 'result', 'email' )['heading'] ??'') ); ?>">
                    <p><?php esc_html_e( 'The heading of emails sending to customers which include discount coupon code.', 'woo-lucky-wheel' ) ?></p>
                    <p>{coupon_label}
                        - <?php esc_html_e( 'Coupon label/custom label that customers win', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="content"><?php esc_html_e( 'Email content', 'woo-lucky-wheel' ) ?></label>
                    <p><?php esc_html_e( 'The content of email sending to customers to inform them the coupon code they receive', 'woo-lucky-wheel' ) ?></p>
                </th>
                <td>
					<?php
					$option = array( 'editor_height' => 300, 'media_buttons' => true );
					ob_start();
					wp_editor( stripslashes( $this->settings->get_params( 'result', 'email' )['content']??'' ), 'content', $option );
					$tmp_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'content' =>[
								'not_wrap_html' => 1,
								'editor_option' => $option,
								'html' => $tmp_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <ul>
                        <li>{customer_name}
                            - <?php esc_html_e( 'Customer\'s name.', 'woo-lucky-wheel' ) ?></li>
                        <li>{coupon_code}
                            - <?php esc_html_e( 'Coupon code/custom value will be sent to customer.', 'woo-lucky-wheel' ) ?></li>
                        <li>{coupon_label}
                            - <?php esc_html_e( 'Coupon label/custom label that customers win', 'woo-lucky-wheel' ) ?></li>
                        <li>{date_expires}
                            - <?php esc_html_e( 'Expiry date of the coupon.', 'woo-lucky-wheel' ) ?></li>
                        <li>{featured_products} - <a class="vi-ui button" target="_blank"
                                                    href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                            - <?php esc_html_e( 'List of featured products with product image thumbnail, product title, product price and a button linked to product page which is design the same as button {shop_now}(Beware of using this shortcode if your store has too many featured products)', 'woo-lucky-wheel' ) ?>
                            </li>
                        <li>{shop_now}
                            - <?php esc_html_e( 'Button ' );
							echo '<a class="wlwl-button-shop-now" href="' . esc_url( $this->settings->get_params( 'button_shop_url' ) ) . '" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;font-size:' . esc_attr( $this->settings->get_params( 'button_shop_size' ) ) . 'px;color:' . esc_attr( $this->settings->get_params( 'button_shop_color' ) ) . ';background:' . esc_attr( $this->settings->get_params( 'button_shop_bg_color' ) ) . ';">' . esc_html( $this->settings->get_params( 'button_shop_title' ) ) . '</a>' ?></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="footer_text"><?php esc_html_e( 'Footer text', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_suggested_products"><?php esc_html_e( 'Suggested products', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p><?php esc_html_e( 'These products will be added at the end of email content with product image thumbnail, product title, product price and a button linked to product page which is design the same as button {shop_now}', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="footer_text"><?php esc_html_e( '"Shop now" button title', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="footer_text"><?php esc_html_e( '"Shop now" button URL', 'woo-lucky-wheel' ); ?></label>
                </th>
                <td>
                    <input name="wlwl_button_shop_url" id="wlwl_button_shop_url" type="text"
                           value="<?php echo esc_attr( $this->settings->get_params( 'button_shop_url' ) ); ?>">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_button_shop_color"><?php esc_html_e( '"Shop now" button text color', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wlwl_button_shop_bg_color"><?php esc_html_e( '"Shop now" button background color', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wlwl_button_shop_size"><?php esc_html_e( '"Shop now" button font size', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields     = [
			'section_start' => [
				'accordion' => 1,
				'active' => 1,
				'class'     => 'wlwl-wheel-after-finishing-spinning-accordion',
				'title'     => esc_html__( 'Customer Notification', 'woo-lucky-wheel' ),
			],
			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];
		$this->settings::villatheme_render_table_field( $fields );
		ob_start();
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="admin_email_enable"><?php esc_html_e( 'Enable admin notification', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Send admin notification when someone wins', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="admin_email_to"><?php esc_html_e( 'Send notification to:', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p><?php esc_html_e( 'Send notification to this email when someone wins. The from email will be used if this field is blank', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="admin_email_subject"><?php esc_html_e( 'Notification Email subject', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
					<?php esc_html_e( 'The subject of emails sending to admin.', 'woo-lucky-wheel' ) ?>

                </td>
            </tr>
            <tr>
                <th>
                    <label for="admin_email_heading"><?php esc_html_e( 'Notification Email heading', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                    <p><?php esc_html_e( 'The heading of emails sending to admin.', 'woo-lucky-wheel' ) ?></p>
                    <p>{coupon_label}
                        - <?php esc_html_e( 'Coupon label/custom label that customers win', 'woo-lucky-wheel' ) ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="admin_email_content"><?php esc_html_e( 'Notification Email content', 'woo-lucky-wheel' ) ?></label>
                    <p><?php esc_html_e( 'The content of email sending to admin.', 'woo-lucky-wheel' ) ?></p>
                </th>
                <td>
                    <div class="field">
                        <div class="field">
                            <a class="vi-ui button" target="_blank"
                               href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                        </div>
                        <div class="field">
                            <ul>
                                <li>{customer_name}
                                    - <?php esc_html_e( 'Customer\'s name.', 'woo-lucky-wheel' ) ?></li>
                                <li>{customer_mobile}
                                    - <?php esc_html_e( 'Customer\'s mobile.', 'woo-lucky-wheel' ) ?></li>
                                <li>{coupon_code}
                                    - <?php esc_html_e( 'Coupon code/custom value will be sent to customer.', 'woo-lucky-wheel' ) ?></li>
                                <li>{coupon_label}
                                    - <?php esc_html_e( 'Coupon label/custom label that customers win', 'woo-lucky-wheel' ) ?></li>
                                <li>{customer_email}
                                    - <?php esc_html_e( 'Email of customer who wins', 'woo-lucky-wheel' ) ?></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
		$wheel_html = ob_get_clean();
		$fields     = [
			'section_start' => [
				'accordion' => 1,
				'class'     => 'wlwl-wheel-after-finishing-spinning-accordion',
				'title'     => esc_html__( 'Admin Notification', 'woo-lucky-wheel' ),
			],
			'section_end'   => [ 'accordion' => 1 ],
			'fields_html'   => $wheel_html,
		];
		$this->settings::villatheme_render_table_field( $fields );
		return '';
	}
	public function email_api_options() {
        ?>
        <table class="form-table">
            <tbody>
            <tr >
                <th scope="row">
                    <label for="mailchimp_enable"><?php esc_html_e( 'Enable Mailchimp', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <div class="vi-ui toggle checkbox checked">
                        <input type="checkbox" name="mailchimp_enable"
                               id="mailchimp_enable" <?php checked( $this->settings->get_params( 'mailchimp', 'enable' ), 'on' ) ?>>
                        <label for="mailchimp_enable"><?php esc_html_e( 'Enable', 'woo-lucky-wheel' ) ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Turn on to use MailChimp system', 'woo-lucky-wheel' ) ?></p>

                </td>
            </tr>
            <tr >
                <th scope="row">
                    <label for="mailchimp_api"></label><?php esc_html_e( 'API key', 'woo-lucky-wheel' ) ?>
                </th>
                <td>
                    <input type="text" id="mailchimp_api" name="mailchimp_api"
                           value="<?php echo esc_attr( $this->settings->get_params( 'mailchimp', 'api_key' ) ) ?>">

                    <p class="description"><?php esc_html_e( ' The API key for connecting with your MailChimp account. Get your API key ', 'woo-lucky-wheel' ) ?>
                        <a href="https://admin.mailchimp.com/account/api"><?php esc_html_e( 'here', 'woo-lucky-wheel' ) ?></a>.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="mailchimp_lists"><?php esc_html_e( 'Mailchimp lists', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
					<?php
					$mailchimp      = new VI_WOO_LUCKY_WHEEL_Admin_Mailchimp();
					$mail_lists     = $mailchimp->get_lists();
					$mailchimp_list = $mail_lists->lists ?? array();
					?>
                    <select class="select-who vi-ui fluid dropdown" name="mailchimp_lists"
                            id="mailchimp_lists">
						<?php
						if ( is_array( $mailchimp_list ) && ! empty( $mailchimp_list ) ) {
							foreach ( $mailchimp_list as $mail_list ) {
								?>
                                <option value='<?php echo esc_attr( $mail_list->id ); ?>' <?php selected( $this->settings->get_params( 'mailchimp', 'lists' ), $mail_list->id ); ?> ><?php echo esc_html( $mail_list->name ); ?></option>
								<?php
							}
						}
						?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wlwl_enable_active_campaign"><?php esc_html_e( 'Active Campaign', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wlwl_sendgrid_enable"><?php esc_html_e( 'SendGrid', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Metrilo', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Hubspot', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Klaviyo', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Brevo (Sendinblue)', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php esc_html_e( 'Sendy', 'woo-lucky-wheel' ) ?></label>
                </th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/qXBNY"><?php esc_html_e( 'Upgrade This Feature', 'woo-lucky-wheel' ) ?></a>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
		return '';
	}
	public function save_settings() {
		if ( empty( $_POST['wlwl_nonce_field'] ) || ! wp_verify_nonce(sanitize_text_field( $_POST['wlwl_nonce_field']), 'wlwl_settings_page_save' ) ) {
			return;
		}
		if ( !isset( $_POST['wlwl_save_settings'] ) ) {
			return;
		}
		if ( ! empty( $_POST['probability'] ) && is_array($_POST['probability'])) {
			if ( count( $_POST['probability'] ) > 6 || count( $_POST['probability'] ) < 3 ) {
				$this->error = esc_html__('Free version only includes from 3 to 6 slices. Please upgrade to Premium version to add unlimited slices.', 'woo-lucky-wheel' );
				return;
			}
			if ( array_sum( $_POST['probability'] )!= 100) {
				$this->error = esc_html__('The total probability must be equal to 100%!', 'woo-lucky-wheel' );
				return;
			}
			for ( $i = 0; $i < sizeof( wc_clean( $_POST['coupon_type'] ) ); $i ++ ) {
				if ( in_array( $_POST['coupon_type'][ $i ], array( 'fixed_cart', 'fixed_product', 'percent' ) ) ) {
					if ( $_POST['coupon_amount'][ $i ] < 0 ) {
						$this->error = esc_html__('The amount of Valued-coupon must be greater than or equal to zero!', 'woo-lucky-wheel' );
						break;
					}
				}
			}
			if ($this->error){
				return;
			}
		} else {
			$this->error = esc_html__('There must be at least 3 rows!', 'woo-lucky-wheel' );
			return;
		}
		if ( isset( $_POST['custom_type_label'] ) && is_array( $_POST['custom_type_label'] ) ) {
			foreach ( $_POST['custom_type_label'] as $key => $val ) {
				if ( $val === '' ) {
					$this->error = esc_html__('Label cannot be empty.', 'woo-lucky-wheel' );
					return;
				}
				if ( isset( $_POST['wlwl_existing_coupon'],$_POST['coupon_type'][ $key ] ) && is_array( $_POST['wlwl_existing_coupon'] ) ) {
					if ( $_POST['coupon_type'][ $key ] == 'existing_coupon' &&
                         ($_POST['wlwl_existing_coupon'][ $key ] == '' || $_POST['wlwl_existing_coupon'][ $key ] == 0 )) {
						$this->error = esc_html__('Please enter value for existing coupon.', 'woo-lucky-wheel' );
						return;
					}
				}
				if ( isset( $_POST['custom_type_value'], $_POST['coupon_type'][ $key ] ) && is_array( $_POST['custom_type_value'] ) ) {
					if ( $_POST['coupon_type'][ $key ] == 'custom' && $_POST['custom_type_value'][ $key ] == '' ) {
						$this->error = esc_html__('Please enter value for custom type.', 'woo-lucky-wheel' );
					}
				}
			}
		}
		global $woo_lucky_wheel_settings;
		$args = array(
			'general'    => array(
				'enable'     => isset( $_POST['wlwl_enable'] ) ? sanitize_text_field( $_POST['wlwl_enable'] ) : 'off',
				'mobile'     => isset( $_POST['wlwl_enable_mobile'] ) ? sanitize_text_field( $_POST['wlwl_enable_mobile'] ) : 'off',
				'spin_num'   => isset( $_POST['wlwl_spin_num'] ) ? sanitize_text_field( $_POST['wlwl_spin_num'] ) : 0,
				'delay'      => isset( $_POST['wlwl_delay'] ) ? sanitize_text_field( $_POST['wlwl_delay'] ) : 0,
				'delay_unit' => isset( $_POST['wlwl_delay_unit'] ) ? sanitize_text_field( $_POST['wlwl_delay_unit'] ) : 's',
			),
			'notify'     => array(
				'position'           => isset( $_POST['notify_position'] ) ? sanitize_text_field( $_POST['notify_position'] ) : '',
				'size'               => isset( $_POST['notify_size'] ) ? sanitize_text_field( $_POST['notify_size'] ) : 0,
				'color'              => isset( $_POST['notify_color'] ) ? sanitize_text_field( $_POST['notify_color'] ) : '',
				'intent'             => isset( $_POST['notify_intent'] ) ? sanitize_text_field( $_POST['notify_intent'] ) : '',
				'show_again'         => isset( $_POST['notify_show_again'] ) ? sanitize_text_field( $_POST['notify_show_again'] ) : 0,
				'hide_popup'         => isset( $_POST['notify_hide_popup'] ) ? sanitize_text_field( $_POST['notify_hide_popup'] ) : 'off',
				'show_wheel'         => isset( $_POST['show_wheel'] ) ? sanitize_text_field( $_POST['show_wheel'] ) : '',
				'show_again_unit'    => isset( $_POST['notify_show_again_unit'] ) ? sanitize_text_field( $_POST['notify_show_again_unit'] ) : 0,
				'show_only_front'    => isset( $_POST['notify_frontpage_only'] ) ? sanitize_text_field( $_POST['notify_frontpage_only'] ) : 'off',
				'show_only_blog'     => isset( $_POST['notify_blogpage_only'] ) ? sanitize_text_field( $_POST['notify_blogpage_only'] ) : 'off',
				'show_only_shop'     => isset( $_POST['notify_shop_only'] ) ? sanitize_text_field( $_POST['notify_shop_only'] ) : 'off',
				'conditional_tags'   => isset( $_POST['notify_conditional_tags'] ) ? stripslashes( sanitize_text_field( $_POST['notify_conditional_tags'] ) ) : '',
				'time_on_close'      => isset( $_POST['notify_time_on_close'] ) ? stripslashes( sanitize_text_field( $_POST['notify_time_on_close'] ) ) : '',
				'time_on_close_unit' => isset( $_POST['notify_time_on_close_unit'] ) ? stripslashes( sanitize_text_field( $_POST['notify_time_on_close_unit'] ) ) : '',
			),
			'wheel_wrap' => array(
				'description'          => isset( $_POST['wheel_wrap_description'] ) ? wp_kses_post( stripslashes( $_POST['wheel_wrap_description'] ) ) : '',
				'bg_image'             => isset( $_POST['wheel_wrap_bg_image'] ) ? sanitize_text_field( $_POST['wheel_wrap_bg_image'] ) : '',
				'bg_color'             => isset( $_POST['wheel_wrap_bg_color'] ) ? sanitize_text_field( $_POST['wheel_wrap_bg_color'] ) : '',
				'text_color'           => isset( $_POST['wheel_wrap_text_color'] ) ? sanitize_text_field( $_POST['wheel_wrap_text_color'] ) : '',
				'spin_button'          => isset( $_POST['wheel_wrap_spin_button'] ) ? sanitize_text_field( stripslashes( $_POST['wheel_wrap_spin_button'] ) ) : 'Try Your Lucky',
				'spin_button_color'    => isset( $_POST['wheel_wrap_spin_button_color'] ) ? sanitize_text_field( $_POST['wheel_wrap_spin_button_color'] ) : '',
				'spin_button_bg_color' => isset( $_POST['wheel_wrap_spin_button_bg_color'] ) ? sanitize_text_field( $_POST['wheel_wrap_spin_button_bg_color'] ) : '',
				'pointer_position'     => 'center',
				'pointer_color'        => isset( $_POST['pointer_color'] ) ? sanitize_text_field( $_POST['pointer_color'] ) : '',
				'wheel_center_image'   => '',
				'wheel_center_color'   => isset( $_POST['wheel_center_color'] ) ? sanitize_text_field( $_POST['wheel_center_color'] ) : '',
				'wheel_border_color'   => '#ffffff',
				'wheel_dot_color'      => '#000000',
				'close_option'         => isset( $_POST['wheel_wrap_close_option'] ) ? sanitize_text_field( $_POST['wheel_wrap_close_option'] ) : '',
				'font'                 => isset( $_POST['wlwl_google_font_select'] ) ? sanitize_text_field( $_POST['wlwl_google_font_select'] ) : '',
				'gdpr'                 => isset( $_POST['gdpr_policy'] ) ? sanitize_textarea_field( $_POST['gdpr_policy'] ) : "off",
				'gdpr_message'         => isset( $_POST['gdpr_message'] ) ? wp_kses_post( stripslashes( $_POST['gdpr_message'] ) ) : "",
				'custom_css'           => isset( $_POST['custom_css'] ) ? wp_kses_post( stripslashes( $_POST['custom_css'] ) ) : "",
			),
			'wheel'      => array(
				'label_coupon'     => isset( $_POST['wheel_label_coupon'] ) ? sanitize_text_field( $_POST['wheel_label_coupon'] ) : '{coupon_amount} OFF',
				'spinning_time'    => 8,
				'coupon_type'      => isset( $_POST['coupon_type'] ) ? stripslashes_deep( array_map( 'sanitize_text_field', $_POST['coupon_type'] ) ) : array(),
				'coupon_amount'    => isset( $_POST['coupon_amount'] ) ? array_map( 'sanitize_text_field', $_POST['coupon_amount'] ) : array(),
				'email_templates'    => isset( $_POST['email_templates'] ) ? array_map( 'sanitize_text_field', $_POST['email_templates'] ) : array(),
				'custom_value'     => isset( $_POST['custom_type_value'] ) ? array_map( 'wlwl_sanitize_text_field', $_POST['custom_type_value'] ) : array(),
				'custom_label'     => isset( $_POST['custom_type_label'] ) ? array_map( 'wlwl_sanitize_text_field', $_POST['custom_type_label'] ) : array(),
				'existing_coupon'  => isset( $_POST['wlwl_existing_coupon'] ) ? array_map( 'sanitize_text_field', $_POST['wlwl_existing_coupon'] ) : array(),
				'probability'      => isset( $_POST['probability'] ) ? array_map( 'sanitize_text_field', $_POST['probability'] ) : array(),
				'bg_color'         => isset( $_POST['bg_color'] ) ? array_map( 'sanitize_text_field', $_POST['bg_color'] ) : array(),
				'slice_text_color' => isset( $_POST['slice_text_color'] ) ? wp_kses_post( stripslashes( $_POST['slice_text_color'] ) ) : "",
				'show_full_wheel'  => isset( $_POST['show_full_wheel'] ) ? sanitize_text_field( $_POST['show_full_wheel'] ) : "",
			),

			'result' => array(
				'auto_close'   => isset( $_POST['result-auto_close'] ) ? sanitize_text_field( $_POST['result-auto_close'] ) : 0,
				'email'        => array(
					'subject' => isset( $_POST['subject'] ) ? stripslashes( sanitize_text_field( $_POST['subject'] ) ) : "",
					'heading' => isset( $_POST['heading'] ) ? stripslashes( sanitize_text_field( $_POST['heading'] ) ) : "",
					'content' => isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : "",
				),
				'notification' => array(
					'win'  => isset( $_POST['result_win'] ) ? wp_kses_post( stripslashes( $_POST['result_win'] ) ) : "",
					'lost' => isset( $_POST['result_lost'] ) ? wp_kses_post( stripslashes( $_POST['result_lost'] ) ) : "",
				)
			),

			'coupon' => array(
				'allow_free_shipping'        => isset( $_POST['wlwl_free_shipping'] ) ? sanitize_text_field( $_POST['wlwl_free_shipping'] ) : 'no',
				'expiry_date'                => isset( $_POST['wlwl_expiry_date'] ) ? sanitize_text_field( $_POST['wlwl_expiry_date'] ) : '',
				'min_spend'                  => isset( $_POST['wlwl_min_spend'] ) ? wc_format_decimal( $_POST['wlwl_min_spend'] ) : "",
				'max_spend'                  => isset( $_POST['wlwl_max_spend'] ) ? wc_format_decimal( $_POST['wlwl_max_spend'] ) : "",
				'individual_use'             => isset( $_POST['wlwl_individual_use'] ) ? sanitize_text_field( $_POST['wlwl_individual_use'] ) : "no",
				'exclude_sale_items'         => isset( $_POST['wlwl_exclude_sale_items'] ) ? sanitize_text_field( $_POST['wlwl_exclude_sale_items'] ) : "no",
				'limit_per_coupon'           => isset( $_POST['wlwl_limit_per_coupon'] ) ? absint( $_POST['wlwl_limit_per_coupon'] ) : "",
				'limit_to_x_items'           => isset( $_POST['wlwl_limit_to_x_items'] ) ? absint( $_POST['wlwl_limit_to_x_items'] ) : "",
				'limit_per_user'             => isset( $_POST['wlwl_limit_per_user'] ) ? absint( $_POST['wlwl_limit_per_user'] ) : "",
				'product_ids'                => isset( $_POST['wlwl_product_ids'] ) ? stripslashes_deep( $_POST['wlwl_product_ids'] ) : array(),
				'exclude_product_ids'        => isset( $_POST['wlwl_exclude_product_ids'] ) ? stripslashes_deep( $_POST['wlwl_exclude_product_ids'] ) : array(),
				'product_categories'         => isset( $_POST['wlwl_product_categories'] ) ? stripslashes_deep( $_POST['wlwl_product_categories'] ) : array(),
				'exclude_product_categories' => isset( $_POST['wlwl_exclude_product_categories'] ) ? stripslashes_deep( $_POST['wlwl_exclude_product_categories'] ) : array(),
				'coupon_code_prefix'         => isset( $_POST['wlwl_coupon_code_prefix'] ) ? sanitize_text_field( $_POST['wlwl_coupon_code_prefix'] ) : ""
			),

			'mailchimp' => array(
				'enable'  => isset( $_POST['mailchimp_enable'] ) ? sanitize_text_field( $_POST['mailchimp_enable'] ) : 'off',
				'api_key' => isset( $_POST['mailchimp_api'] ) ? sanitize_text_field( $_POST['mailchimp_api'] ) : '',
				'lists'   => isset( $_POST['mailchimp_lists'] ) ? sanitize_text_field( $_POST['mailchimp_lists'] ) : '',
			),

			'button_shop_title'               => esc_html__( 'Shop now', 'woo-lucky-wheel' ),
			'button_shop_url'                 => isset( $_POST['wlwl_button_shop_url'] ) ? sanitize_text_field( $_POST['wlwl_button_shop_url'] ) : '',
			'button_shop_color'               => '#ffffff',
			'button_shop_bg_color'            => '#000',
			'button_shop_size'                => 16,
			'ajax_endpoint'                   => isset( $_POST['ajax_endpoint'] ) ? sanitize_text_field( $_POST['ajax_endpoint'] ) : 'ajax',
			'custom_field_name_enable'        => isset( $_POST['custom_field_name_enable'] ) ? sanitize_text_field( $_POST['custom_field_name_enable'] ) : '',
			'custom_field_name_enable_mobile' => isset( $_POST['custom_field_name_enable_mobile'] ) ? sanitize_text_field( $_POST['custom_field_name_enable_mobile'] ) : '',
			'custom_field_name_required'      => isset( $_POST['custom_field_name_required'] ) ? sanitize_text_field( $_POST['custom_field_name_required'] ) : '',
		);
		$this->updated_sucessfully = 1;
		$args =  apply_filters( 'wlwl_update_settings_args',wp_parse_args( $args, get_option( '_wlwl_settings', $woo_lucky_wheel_settings ) ));
		update_option( '_wlwl_settings', $args );
		$woo_lucky_wheel_settings = $args;
		$this->settings           = VI_WOO_LUCKY_WHEEL_DATA::get_instance( true );
	}

	public function report_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$total_spin = $email_subscribe = $coupon_given = 0;

		if ( isset( $_POST['submit'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$start    = sanitize_text_field( $_POST['wlwl_export_start'] );//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$end      = sanitize_text_field( $_POST['wlwl_export_end'] );//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$filename = "lucky_wheel_email";
			if ( ! $start && ! $end ) {
				$args1    = array(
					'post_type'      => 'wlwl_email',
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
				);
				$filename .= gmdate( 'Y-m-d_h-i-s', time() ) . ".csv";
			} elseif ( ! $start ) {
				$args1    = array(
					'post_type'      => 'wlwl_email',
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'date_query'     => array(
						array(
							'before'    => $end,
							'inclusive' => true

						)
					),
				);
				$filename .= 'before_' . $end . ".csv";
			} elseif ( ! $end ) {
				$args1    = array(
					'post_type'      => 'wlwl_email',
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'date_query'     => array(
						array(
							'after'     => $start,
							'inclusive' => true
						)
					),

				);
				$filename .= 'from' . $start . 'to' . gmdate( 'Y-m-d' ) . ".csv";
			} else {
				if ( strtotime( $start ) > strtotime( $end ) ) {
					wp_die( 'Incorrect input date' );
				}
				$args1    = array(
					'post_type'      => 'wlwl_email',
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'date_query'     => array(
						array(
							'before'    => $end,
							'after'     => $start,
							'inclusive' => true

						)
					),
				);
				$filename .= 'from' . $start . 'to' . $end . ".csv";
			}
			$the_query        = new WP_Query( $args1 );
			$csv_source_array = array();
			$names            = array();
			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$csv_source_array[] = get_the_title();
					$names[]            = get_the_content();

				}
				wp_reset_postdata();
				$data_rows  = array();
				$header_row = array(
					'order',
					'email',
					'name',
				);
				$i          = 1;
				foreach ( $csv_source_array as $key => $result ) {
					$row         = array( $i, $result, $names[ $key ] );
					$data_rows[] = $row;
					$i ++;
				}
				ob_end_clean();
				$fh = @fopen( 'php://output', 'w' );
				fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-type: text/csv' );
				header( 'Content-Disposition: attachment; filename=' . $filename );
				header( 'Expires: 0' );
				header( 'Pragma: public' );
				fputcsv( $fh, $header_row );
				foreach ( $data_rows as $data_row ) {
					fputcsv( $fh, $data_row );
				}
				$csvFile = stream_get_contents( $fh );
				fclose( $fh );//phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				die;
			}
		} else {
			$args      = array(
				'post_type'      => 'wlwl_email',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
			);
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				$email_subscribe = $the_query->post_count;
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$id = get_the_ID();
					if ( get_post_meta( $id, 'wlwl_spin_times', true ) ) {
						$total_spin += get_post_meta( $id, 'wlwl_spin_times', true )['spin_num'];
					}
					if ( get_post_meta( $id, 'wlwl_email_coupons', true ) ) {
						$coupon       = get_post_meta( $id, 'wlwl_email_coupons', true );
						$coupon_given += sizeof( $coupon );
					}
				}
				wp_reset_postdata();
			}
		}
		?>
        <div class="wrap">
            <form action="" method="post">
                <h2><?php esc_html_e( 'Lucky Wheel Report', 'woo-lucky-wheel' ) ?></h2>

                <table cellspacing="0" id="status" class="widefat">
                    <tbody>
                    <tr>
                        <th><?php esc_html_e( 'Total Spins', 'woo-lucky-wheel' ) ?></th>
                        <th><?php esc_html_e( 'Emails Subcribed', 'woo-lucky-wheel' ) ?></th>
                        <th><?php esc_html_e( 'Coupon Given', 'woo-lucky-wheel' ) ?></th>
                    </tr>
                    <tr>
                        <td><?php echo esc_html( $total_spin ); ?></td>
                        <td><?php echo esc_html( $email_subscribe ); ?></td>
                        <td><?php echo esc_html( $coupon_given ); ?></td>
                    </tr>
                    </tbody>

                </table>
                <label for="wlwl_export_start"><?php esc_html_e( 'From', 'woo-lucky-wheel' ); ?></label><input
                        type="date" name="wlwl_export_start" id="wlwl_export_start" class="wlwl_export_date">
                <label for="wlwl_export_end"><?php esc_html_e( 'To', 'woo-lucky-wheel' ); ?></label><input
                        type="date" name="wlwl_export_end" id="wlwl_export_end" class="wlwl_export_date">

                <input id="submit"
                       type="submit"
                       class="button-primary"
                       name="submit"
                       value="<?php esc_html_e( 'Export Emails', 'woo-lucky-wheel' ); ?>"/>
            </form>
        </div>
		<?php
	}

	function system_status() {
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'System Status', 'woo-lucky-wheel' ) ?></h2>
            <table cellspacing="0" id="status" class="widefat">
                <tbody>
                <tr>
                    <td data-export-label="file_get_contents"><?php esc_html_e( 'file_get_contents', 'woo-lucky-wheel' ) ?></td>
                    <td>
						<?php
						if ( function_exists( 'file_get_contents' ) ) {
							echo '<span class="wlwl-status-ok">&#10004;</span> ';
						} else {
							echo '<span class="wlwl-status-error">&#10005; </span>';
						}
						?>
                    </td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_html_e( 'Allow URL Open', 'woo-lucky-wheel' ) ?>"><?php esc_html_e( 'Allow URL Open', 'woo-lucky-wheel' ) ?></td>
                    <td>
						<?php
						if ( ini_get( 'allow_url_fopen' ) == 'On' ) {
							echo '<span class="wlwl-status-ok">&#10004;</span> ';
						} else {
							echo '<span class="wlwl-status-error">&#10005;</span>';
						}
						?>
                </tr>
                </tbody>
            </table>
        </div>
		<?php
	}
	public static function auto_color() {
		$color_arr = VI_WOO_LUCKY_WHEEL_DATA::auto_color_arr();
		$palette     = json_decode( $color_arr ,true);
		?>
        <div class="color_palette" data-color_arr="<?php echo esc_attr($color_arr);?>">
			<?php
			foreach ($palette as $k => $v){
				if (empty($v['color']) || !is_array($v['color'])){
					return;
				}
				?>
                <div>
                    <div class="wlwl_color_palette" data-color_code="<?php echo esc_attr($k)?>"
                         style="background:<?php echo esc_attr(!empty($v['palette'])? $v['palette'] : end($v['color']))?>;"></div>
                </div>
				<?php
			}
			?>
        </div>
        <div class="auto_color_ok_cancel">
            <div class="vi-ui buttons">
                <span class="auto_color_ok positive vi-ui button"><?php esc_html_e( 'OK', 'woo-lucky-wheel' ); ?></span>
                <div class="or"></div>
                <span class="auto_color_cancel vi-ui button"><?php esc_html_e( 'Cancel', 'woo-lucky-wheel' ); ?></span>
            </div>
        </div>
		<?php
	}

}
