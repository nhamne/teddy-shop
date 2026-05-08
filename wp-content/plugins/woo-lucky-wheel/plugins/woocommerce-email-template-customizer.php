<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'VI_WOO_LUCKY_WHEEL_Plugins_WooCommerce_Email_Template_Customizer' ) ) {
	class VI_WOO_LUCKY_WHEEL_Plugins_WooCommerce_Email_Template_Customizer {
		public static $email_templates, $is_active;
		public $settings, $wheel_email_templates;

		public function __construct() {
			if ( ! class_exists( 'WooCommerce_Email_Template_Customizer' ) && !class_exists('Woo_Email_Template_Customizer')) {
				return;
			}
			self::$is_active = true;
			add_action( 'wlwl_wheel_settings_slices_column', [ $this, 'wlwl_wheel_settings_slices_column' ] );
			add_action( 'wlwl_wheel_settings_slices_column_content', [ $this, 'wlwl_wheel_settings_slices_column_content' ], 10, 1 );
			add_filter( 'viwec_register_email_type', array( $this, 'register_email_type' ) );
			add_filter( 'viwec_sample_subjects', array( $this, 'register_email_sample_subject' ) );
			add_filter( 'viwec_sample_templates', array( $this, 'register_email_sample_template' ) );
			add_filter( 'viwec_live_edit_shortcodes', array( $this, 'register_render_preview_shortcode' ) );
			add_filter( 'viwec_register_preview_shortcode', array( $this, 'register_render_preview_shortcode' ) );
		}

		public function wlwl_wheel_settings_slices_column_content( $index ) {
			if ( ! $this->settings ) {
				$this->settings = VI_WOO_LUCKY_WHEEL_DATA::get_instance( true );
			}
			$all_email_templates  = self::get_email_templates();
			$wheel_email_template = $this->wheel_email_templates[ $index ] ?? '';
			echo ' <td class="wheel_email_template">';
			ob_start();
			?>
			<select class="vi-ui dropdown fluid" name="email_templates[]">
				<option value="0"><?php esc_html_e( 'None', 'woocommerce-lucky-wheel' ) ?></option>
				<?php
				if ( ! empty( $all_email_templates ) ) {
					foreach ( $all_email_templates as $all_email_templates_v ) {
						?>
						<option value="<?php echo esc_attr( $all_email_templates_v->ID ); ?>"<?php selected( $all_email_templates_v->ID, $wheel_email_template ); ?>>
							<?php echo esc_html( "(#{$all_email_templates_v->ID}){$all_email_templates_v->post_title}" ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
			<?php
			$wheel_email_template_html = ob_get_clean();
			$fields                    = [
				'fields' => [
					'email_templates' => [
						'not_wrap_html'         => 1,
						'wheel_slide_index'     => $index,
						'wheel_email_templates' => $all_email_templates,
						'html'                  => $wheel_email_template_html,
					]
				],
			];
			$this->settings::villatheme_render_table_field( $fields );
			echo '</td>';
		}

		public function wlwl_wheel_settings_slices_column() {
			$this->settings              = VI_WOO_LUCKY_WHEEL_DATA::get_instance( true );
			$this->wheel_email_templates = $this->settings->get_params( 'wheel', 'email_templates' );
			if ( ! is_array( $this->wheel_email_templates ) ) {
				$this->wheel_email_templates = [];
			}
			?>
			<td rowspan="2"><?php esc_html_e( 'Email Template', 'woocommerce-lucky-wheel' ) ?></td>
			<?php
		}
		public static function get_email_templates( $type = 'wlwl_coupon_email' ) {
			if ( ! self::$email_templates ) {
				self::$email_templates = viwec_get_emails_list( $type );
			}
			return self::$email_templates;
		}

		public function register_email_type( $emails ) {
			$emails['wlwl_coupon_email'] = array(
				'name'          => esc_html__( 'WooCommerce Lucky Wheel - Coupon Email', 'woocommerce-lucky-wheel' ),
				'hide_rules'    => array( 'priority','country', 'category','products', 'min_order', 'max_order','price_type_order', 'payment_methods' ),
				'hide_elements' => array(
					'html/order_detail',
					'html/order_subtotal',
					'html/order_total',
					'html/shipping_method',
					'html/payment_method',
					'html/order_note',
					'html/billing_address',
					'html/shipping_address',
					'html/wc_hook',
				),
			);

			return $emails;
		}

		public function register_email_sample_subject( $subjects ) {
			$subjects['wlwl_coupon_email'] = 'Congratulation from {site_title}';

			return $subjects;
		}

		public function register_email_sample_template( $samples ) {
			$samples['wlwl_coupon_email'] = [
				'basic' => [
					'name' => esc_html__( 'Basic', 'woocommerce-lucky-wheel' ),
					'data' => '{"style_container":{"background-color":"transparent","background-image":"none"},"rows":{"0":{"props":{"style_outer":{"padding":"15px 35px","background-image":"none","background-color":"#162447","border-color":"transparent","border-style":"solid","border-width":"0px","border-radius":"0px","width":"600px"},"type":"layout/grid1cols","dataCols":"1"},"cols":{"0":{"props":{"style":{"padding":"0px","background-image":"none","background-color":"transparent","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"530px"}},"elements":{"0":{"type":"html/text","style":{"width":"530px","line-height":"30px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p style=\"text-align: center;\"><span style=\"color: #ffffff;\">{site_title}</span></p>"},"attrs":{},"childStyle":{}}}}}},"1":{"props":{"style_outer":{"padding":"25px","background-image":"none","background-color":"#f9f9f9","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"600px"},"type":"layout/grid1cols","dataCols":"1"},"cols":{"0":{"props":{"style":{"padding":"0px","background-image":"none","background-color":"transparent","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"550px"}},"elements":{"0":{"type":"html/text","style":{"width":"550px","line-height":"28px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p style=\"text-align: center;\"><span style=\"font-size: 24px; color: #444444;\">You have won a lucky coupon!</span></p>"},"attrs":{},"childStyle":{}}}}}},"2":{"props":{"style_outer":{"padding":"10px 35px","background-image":"none","background-color":"#ffffff","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"600px"},"type":"layout/grid1cols","dataCols":"1"},"cols":{"0":{"props":{"style":{"padding":"0px","background-image":"none","background-color":"transparent","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"530px"}},"elements":{"0":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p>Dear {wlwl_customer_name},</p>"},"attrs":{},"childStyle":{}},"1":{"type":"html/spacer","style":{"width":"530px"},"content":{},"attrs":{},"childStyle":{".viwec-spacer":{"padding":"10px 0px 0px"}}},"2":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p>You have won a discount coupon by spinning lucky wheel on my website. Please apply the coupon when shopping with us.</p>"},"attrs":{},"childStyle":{}},"3":{"type":"html/button","style":{"width":"530px","font-size":"15px","font-weight":"400","color":"#1de712","line-height":"22px","text-align":"center","padding":"20px 0px 20px 1px"},"content":{"text":"{wlwl_coupon_code}"},"attrs":{"href":"{shop_url}"},"childStyle":{"a":{"border-width":"2px","border-radius":"0px","border-color":"#162447","border-style":"dashed","background-color":"#ffffff","width":"200px","padding":"10px 20px"}}},"4":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p>This coupon will expire on {wlwl_date_expires}.</p>"},"attrs":{},"childStyle":{}},"5":{"type":"html/spacer","style":{"width":"530px"},"content":{},"attrs":{},"childStyle":{".viwec-spacer":{"padding":"10px 0px 0px"}}},"6":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p>Yours sincerely!</p>\n<p>{site_title}</p>"},"attrs":{},"childStyle":{}}}}}},"3":{"props":{"style_outer":{"padding":"25px 35px","background-image":"none","background-color":"#162447","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"600px"},"type":"layout/grid1cols","dataCols":"1"},"cols":{"0":{"props":{"style":{"padding":"0px","background-image":"none","background-color":"transparent","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px","width":"530px"}},"elements":{"0":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p style=\"text-align: center;\"><span style=\"color: #f5f5f5; font-size: 20px;\">Get in Touch</span></p>"},"attrs":{},"childStyle":{}},"1":{"type":"html/social","style":{"width":"530px","text-align":"center","padding":"20px 0px 0px","background-image":"none","background-color":"transparent"},"content":{},"attrs":{"facebook":"' . VIWEC_IMAGES . 'fb-blue-white.png","facebook_url":"#","twitter":"' . VIWEC_IMAGES . 'twi-cyan-white.png","twitter_url":"#","instagram":"' . VIWEC_IMAGES . 'ins-white-color.png","instagram_url":"#","direction":""},"childStyle":{}},"2":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"20px 0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p style=\"text-align: center;\"><span style=\"color: #f5f5f5; font-size: 12px;\">This email was sent by : <span style=\"color: #ffffff;\"><a style=\"color: #ffffff;\" href=\"{admin_email}\">{admin_email}</a></span></span></p>\n<p style=\"text-align: center;\"><span style=\"color: #f5f5f5; font-size: 12px;\">For any questions please send an email to <span style=\"color: #ffffff;\"><a style=\"color: #ffffff;\" href=\"{admin_email}\">{admin_email}</a></span></span></p>"},"attrs":{},"childStyle":{}},"3":{"type":"html/text","style":{"width":"530px","line-height":"22px","background-image":"none","background-color":"transparent","padding":"0px","border-color":"#444444","border-style":"solid","border-width":"0px","border-radius":"0px"},"content":{"text":"<p style=\"text-align: center;\"><span style=\"color: #f5f5f5;\"><span style=\"color: #f5f5f5;\"><span style=\"font-size: 12px;\"><a style=\"color: #f5f5f5;\" href=\"#\">Privacy Policy</a>&nbsp; |&nbsp; <a style=\"color: #f5f5f5;\" href=\"#\">Help Center</a></span></span></span></p>"},"attrs":{},"childStyle":{}}}}}}}}',
				],
			];

			return $samples;
		}

		public function register_render_preview_shortcode( $sc ) {
			$date_format = get_option( 'date_format', 'F d, Y' );
			if ( ! $date_format ) {
				$date_format = 'F d, Y';
			}
			$sc['wlwl_coupon_email'] = array(
				'{wlwl_coupon_label}'    => '10% OFF',
				'{wlwl_coupon_code}'     => 'LUCKY_WHEEL',
				'{wlwl_customer_name}'   => 'John',
				'{wlwl_date_expires}'    => date_i18n( $date_format, ( current_time( 'timestamp' ) - 3 * DAY_IN_SECONDS ) ),
			);

			return $sc;
		}
	}
}
