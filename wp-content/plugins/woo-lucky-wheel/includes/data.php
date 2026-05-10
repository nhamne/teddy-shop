<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WOO_LUCKY_WHEEL_DATA {
	private $params;
	private $default;
	protected static $instance = null,$allow_html = null ;

	/**
	 * VI_WOO_LUCKY_WHEEL_DATA constructor.
	 * Init setting
	 */
	public function __construct() {

		global $woo_lucky_wheel_settings;
		if ( ! $woo_lucky_wheel_settings ) {
			$woo_lucky_wheel_settings = get_option( '_wlwl_settings', array() );
		}
		$this->default = array(
			'general'                           => array(
				'enable'     => "on",
				'mobile'     => "on",
				'spin_num'   => 1,
				'delay'      => 24,
				'delay_unit' => 'h'
			),
			'notify'                            => array(
				'position'      => 'bottom-right',
				'size'          => 40,
				'color'         => '',
				'intent'        => 'show_wheel',
				'hide_popup'    => 'off',
				'show_wheel'    => '1,5',//initial time
				'scroll_amount' => '50',

				'show_again'         => 24,
				'show_again_unit'    => 'h',
				'show_only_front'    => 'off',
				'show_only_blog'     => 'off',
				'show_only_shop'     => 'off',
				'conditional_tags'   => '',
				'time_on_close'      => '1',
				'time_on_close_unit' => 'd',
			),
			'wheel_wrap'                        => array(
				'description'            => '<h2><span style="color: #ffffff;">SPIN TO WIN!</span></h2>
<ul>
 	<li><em><span style="color: #dbdbdb;">Try your lucky to get discount coupon</span></em></li>
 	<li><em><span style="color: #dbdbdb;">1 spin per email</span></em></li>
 	<li><em><span style="color: #dbdbdb;">No cheating</span></em></li>
</ul>',
				'bg_image'               => VI_WOO_LUCKY_WHEEL_IMAGES . '2020.png',
				'bg_color'               => '#36622b',
				'text_color'             => '#ffffff',
				'spin_button'            => 'Try Your Lucky',
				'spin_button_color'      => '#000000',
				'spin_button_bg_color'   => '#ffbe10',
				'pointer_position'       => 'center',
				'pointer_color'          => '#000000',
				'wheel_center_image'     => '',
				'wheel_center_color'     => '#ffffff',
				'wheel_border_color'     => '#ffffff',
				'wheel_dot_color'        => '#000000',
				'close_option'           => 'on',
				'font'                   => 'Open+Sans',
				'gdpr'                   => 'off',
				'gdpr_message'           => 'I agree with the <a href="">term and condition</a>',
				'custom_css'             => '',
				'congratulations_effect' => 'firework',
				'background_effect'      => 'snowflakes',
			),
			'wheel'                             => array(
				'label_coupon'      => '{coupon_amount} OFF',
				'spinning_time'     => 8,
				'custom_value'      => array( "", "", "", "", "", "" ),
				'custom_label'      => array(
					"Not Lucky",
					"{coupon_amount} OFF",
					"Not Lucky",
					"{coupon_amount} OFF",
					"Not Lucky",
					"{coupon_amount} OFF"
				),
				'existing_coupon'   => array( "", "", "", "", "", "" ),
				'coupon_type'       => array(
					'non',
					'percent',
					'non',
					'fixed_product',
					'non',
					'fixed_cart'
				),
				'coupon_amount'     => array( '0', '5', '0', '10', '0', '15' ),
				'probability'       => array( '25', '15', '25', '6', '25', '4' ),
				'bg_color'          => ['#c8e6c9', '#81c784', '#43a047', '#1b5e20', '#c8e6c9', '#81c784'],
				'slice_text_color'  => '#fff',//free version
				'slices_text_color' => array(
					'#fff',
					'#fff',
					'#fff',
					'#fff',
					'#fff',
					'#fff',
				),
				'currency'          => 'symbol',
				'wheel_speed'       => 3,
				'show_full_wheel'   => 'on',
				'font_size'         => '100',
				'wheel_size'        => '100',
				'random_color'      => 'off',

			),
			'result'                            => array(
				'auto_close'   => 0,
				'email'        => array(
					'subject' => 'Lucky wheel coupon award.',
					'heading' => 'Congratulations!',
					'content' => "Dear {customer_name},\nYou have won a discount coupon by spinning lucky wheel on my website. Please apply the coupon when shopping with us.\nThank you!\nCoupon code :{coupon_code}\nExpiry date: {date_expires}\nYour Sincerely",
				),
				'notification' => array(
					'win'  => 'Congrats! You have won a {coupon_label} discount coupon. The coupon was sent to the email address that you had entered to spin. {checkout} now!',
					'lost' => 'OOPS! You are not lucky today. Sorry.',
				),
			),
			'coupon'                            => array(
				'allow_free_shipping'        => 'no',
				'expiry_date'                => null,
				'min_spend'                  => '',
				'max_spend'                  => '',
				'individual_use'             => 'no',
				'exclude_sale_items'         => 'no',
				'limit_per_coupon'           => 1,
				'limit_to_x_items'           => 1,
				'limit_per_user'             => 1,
				'product_ids'                => array(),
				'exclude_product_ids'        => array(),
				'product_categories'         => array(),
				'exclude_product_categories' => array(),
				'coupon_code_prefix'         => ''
			),
			'mailchimp'                         => array(
				'enable'  => 'off',
				'api_key' => '',
				'lists'   => ''
			),
			'active_campaign'                   => array(
				'enable' => 'off',
				'key'    => '',
				'url'    => '',
				'list'   => '',
			),
			'key'                               => '',
			'button_shop_title'                 => 'Shop now',
			'button_shop_url'                   => get_bloginfo( 'url' ),
			'button_shop_color'                 => '#fff',
			'button_shop_bg_color'              => '#000',
			'button_shop_size'                  => '20',
			'suggested_products'                => array(),
			'sendgrid'                          => array(
				'enable' => 'off',
				'key'    => '',
				'list'   => 'none',
			),
			'ajax_endpoint'                     => 'ajax',
			'custom_field_mobile_enable'        => 'off',
			'custom_field_mobile_enable_mobile' => 'off',
			'custom_field_mobile_required'      => 'off',
			'custom_field_name_enable'          => 'on',
			'custom_field_name_enable_mobile'   => 'on',
			'custom_field_name_required'        => 'off',
		);

		$this->params = wp_parse_args( $woo_lucky_wheel_settings, $this->default ) ;
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function get_params( $name = "", $name_sub = '' ) {
		if ( ! $name ) {
			return apply_filters( 'woo_lucky_wheel_params',$this->params);
		}
		$name_t = $name ;
		if ($name_sub){
			$name_t = $name.'__'.$name_sub ;
		}
		$name_filter = 'woo_lucky_wheel_params_' . $name_t;
		if (!isset($result)){
			if ($name_sub){
				$result = isset($this->params[ $name ])?($this->params[ $name ][$name_sub] ?? $this->get_default($name,$name_sub)) : false;
			}else {
				$result = $this->params[ $name] ?? false;
			}
		}
		return $name_filter ? apply_filters( $name_filter, $result) : $result;
	}

	public function get_default( $name = "", $name_sub = '' ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			if ( $name_sub ) {
				if ( isset( $this->default[ $name ][ $name_sub ] ) ) {
					return apply_filters( 'woo_lucky_wheel_params_default_' . $name . '__' . $name_sub, $this->default[ $name ] [ $name_sub ] );
				} else {
					return false;
				}
			} else {
				return apply_filters( 'woo_lucky_wheel_params_default_' . $name, $this->default[ $name ] );
			}
		} else {
			return false;
		}
	}
	/**
	 * @param $tags
	 *
	 * @return array
	 */
	/**
	 * @param $tags
	 *
	 * @return array
	 */
	public static function filter_allowed_html( $tags = [] ) {
		if ( self::$allow_html && empty( $tags ) ) {
			return self::$allow_html;
		}
		if (empty($tags)) {
			$tags = wp_parse_args( array(
				'input'  => array(
					'type'         => 1,
					'id'           => 1,
					'name'         => 1,
					'class'        => 1,
					'placeholder'  => 1,
					'autocomplete' => 1,
					'style'        => 1,
					'value'        => 1,
					'size'         => 1,
					'checked'      => 1,
					'disabled'     => 1,
					'readonly'     => 1,
					'data-*'       => 1,
				),
				'form'   => array(
					'method' => 1,
					'action' => 1,
				),
				'select' => array(
					'name'     => 1,
					'multiple' => 1,
				),
				'option' => array(
					'value' => 1,
				),
				'style'  => array(
					'id'    => 1,
					'class' => 1,
					'type'  => 1,
				),
				'source' => array(
					'type' => 1,
					'src'  => 1
				),
				'video'  => array(
					'width'  => 1,
					'height' => 1,
					'src'    => 1
				),
				'iframe' => array(
					'width'           => 1,
					'height'          => 1,
					'allowfullscreen' => 1,
					'allow'           => 1,
					'src'             => 1
				),
			),wp_kses_allowed_html( 'post' ) );
		}
		$tmp = $tags;
		foreach ( $tmp as $key => $value ) {
			if ( in_array( $key, array( 'div', 'span', 'a', 'form', 'select', 'option', 'table', 'tr', 'th', 'td' ) ) ) {
				$tags[ $key ] = wp_parse_args( [
					'width'  => 1,
					'height' => 1,
					'class'  => 1,
					'id'     => 1,
					'type'   => 1,
					'style'  => 1,
					'data-*' => 1,
					'selected' => 1,
					'disabled' => 1,
				],$value);
			}elseif ($key === 'input'){
				$tags[ $key ] = wp_parse_args( [
					'type'         => 1,
					'id'           => 1,
					'name'         => 1,
					'class'        => 1,
					'placeholder'  => 1,
					'autocomplete' => 1,
					'style'        => 1,
					'value'        => 1,
					'size'         => 1,
					'checked'      => 1,
					'disabled'     => 1,
					'readonly'     => 1,
					'data-*'       => 1,
				],$value);
			}
		}
		self::$allow_html = $tags;

		return self::$allow_html;
	}
	public static function implode_html_attributes( $raw_attributes ) {
		$attributes = array();
		foreach ( $raw_attributes as $name => $value ) {
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
		return implode( ' ', $attributes );
	}

	public static function villatheme_render_field( $name, $field ) {
		if ( ! $name ) {
			return;
		}
		if ( ! empty( $field['html'] ) ) {
			echo $field['html'];

//			echo wp_kses($field['html'], self::filter_allowed_html());
			return;
		}
		$type  = $field['type'] ?? '';
		$value = $field['value'] ?? '';
		if ( ! empty( $field['prefix'] ) ) {
			$id = "wlwl-{$field['prefix']}-{$name}";
		} else {
			$id = "wlwl-{$name}";
		}
		$class             = $field['class'] ?? $id;
		$custom_attributes = array_merge( [
			'type'  => $type,
			'name'  => $name,
			'id'    => $id,
			'value' => $value,
			'class' => $class,
		], (array) ( $field['custom_attributes'] ?? [] ) );
		if ( ! empty( $field['input_label'] ) ) {
			$input_label_type = $field['input_label']['type'] ?? 'left';
			echo wp_kses( sprintf( '<div class="vi-ui %s labeled input">', ( ! empty( $field['input_label']['fluid'] ) ? 'fluid ' : '' ) . $input_label_type ), self::filter_allowed_html() );
			if ( $input_label_type === 'left' ) {
				echo wp_kses( sprintf( '<div class="%s">%s</div>', $field['input_label']['label_class'] ?? 'vi-ui label', $field['input_label']['label'] ?? '' ), self::filter_allowed_html() );
			}
		}
		switch ( $type ) {
			case 'checkbox':
				unset( $custom_attributes['type'] );
				echo wp_kses( sprintf( '
					<div class="vi-ui toggle checkbox">
						<input type="hidden" %s>
						<input type="checkbox" id="%s-checkbox" %s ><label></label>
					</div>', self::implode_html_attributes( $custom_attributes ), $id, $value ? 'checked' : ''
				), self::filter_allowed_html() );
				break;
			case 'select':
				$select_options = $field['options'] ?? '';
				$multiple       = $field['multiple'] ?? '';
				unset( $custom_attributes['type'] );
				unset( $custom_attributes['value'] );
				$custom_attributes['class'] = "vi-ui fluid dropdown {$class}";
				if ( $multiple ) {
					$value                         = (array) $value;
					$custom_attributes['name']     = $name . '[]';
					$custom_attributes['multiple'] = "multiple";
				}
				echo wp_kses( sprintf( '<select %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
				if ( is_array( $select_options ) && count( $select_options ) ) {
					foreach ( $select_options as $k => $v ) {
						$selected = $multiple ? in_array( $k, $value ) : ( $k == $value );
						echo wp_kses( sprintf( '<option value="%s" %s>%s</option>',
							$k, $selected ? 'selected' : '', $v ), self::filter_allowed_html() );
					}
				}
				printf( '</select>' );
				break;
			case 'textarea':
				unset( $custom_attributes['type'] );
				unset( $custom_attributes['value'] );
				echo wp_kses( sprintf( '<textarea %s>%s</textarea>', self::implode_html_attributes( $custom_attributes ), $value ), self::filter_allowed_html() );
				break;
			default:
				if ( $type ) {
					echo wp_kses( sprintf( '<input %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
				}
		}
		if ( ! empty( $field['input_label'] ) ) {
			if ( ! empty( $input_label_type ) && $input_label_type === 'right' ) {
				printf( '<div class="%s">%s</div>', esc_attr( $field['input_label']['label_class'] ?? 'vi-ui label' ), wp_kses_post( $field['input_label']['label'] ?? '' ) );
			}
			printf( '</div>' );
		}
	}

	public static function villatheme_render_table_field( $options ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return;
		}
		if ( ! empty( $options['html'] ) ) {
			echo wp_kses( $options['html'], self::filter_allowed_html() );

			return;
		}
		if ( isset( $options['section_start'] ) ) {
			if ( ! empty( $options['section_start']['accordion'] ) ) {
				echo wp_kses( sprintf( '<div class="vi-ui styled fluid accordion%s">
                                            <div class="title%s">
                                                <i class="dropdown icon"> </i>
                                                %s
                                            </div>
                                        <div class="content%s">',
					! empty( $options['section_start']['class'] ) ? " {$options['section_start']['class']}" : '',
					! empty( $options['section_start']['active'] ) ? " active" : '',
					$options['section_start']['title'] ?? '',
					! empty( $options['section_start']['active'] ) ? " active" : ''
				),
					self::filter_allowed_html() );
			}
			if ( empty( $options['fields_html'] ) ) {
				echo wp_kses_post( '<table class="form-table">' );
			}
		}
		if ( ! empty( $options['fields_html'] ) ) {
			echo  $options['fields_html'];
//			echo wp_kses( $options['fields_html'], self::filter_allowed_html() );
		} else {
			$fields = $options['fields'] ?? '';
			if ( is_array( $fields ) && count( $fields ) ) {
				foreach ( $fields as $key => $param ) {
					$type = $param['type'] ?? '';
					$name = $param['name'] ?? $key;
					if ( ! $name ) {
						continue;
					}
					if ( ! empty( $param['prefix'] ) ) {
						$id = "wlwl-{$param['prefix']}-{$name}";
					} else {
						$id = "wlwl-{$name}";
					}
					if ( empty( $param['not_wrap_html'] ) ) {
						if ( ! empty( $param['wrap_class'] ) ) {
							printf( '<tr class="%s"><th><label for="%s">%s</label></th><td>',
								esc_attr( $param['wrap_class'] ), esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
						} else {
							printf( '<tr><th><label for="%s">%s</label></th><td>', esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
						}
					}
					do_action( 'wlwl_before_option_field', $name, $param );
					self::villatheme_render_field( $name, $param );
					if ( ! empty( $param['custom_desc'] ) ) {
						echo wp_kses_post( $param['custom_desc'] );
					}
					if ( ! empty( $param['desc'] ) ) {
						printf( '<p class="description">%s</p>', wp_kses_post( $param['desc'] ) );
					}
					do_action( 'wlwl_after_option_field', $name, $param );
					if ( empty( $param['not_wrap_html'] ) ) {
						echo wp_kses_post( '</td></tr>' );
					}
				}
			}
		}
		if ( isset( $options['section_end'] ) ) {
			if ( empty( $options['fields_html'] ) ) {
				echo wp_kses_post( '</table>' );
			}
			if ( ! empty( $options['section_end']['accordion'] ) ) {
				echo wp_kses_post( '</div></div>' );
			}
		}
	}

	public static function auto_color_arr() {
		return '{"#CF77CC":{"color":["#FD9FFF","#CB34C5","#E36BE1","#B735AA"],"pointer":"#F70707","palette":"#BA55D3"},"#F46E56":{"color":["#F9AA9B","#D83518","#FF927E","#B62E15"],"pointer":"#000000","palette":"#FF6347"},"#E5C516":{"color":["#FFF2A9","#D4B408","#FFEB80","#B69900"],"pointer":"#F70707","palette":"#F2CD04"},"#00907D":{"color":["#39CCB9","#0A7D6E","#1BAC99","#0A695D"],"pointer":"#F70707","palette":"#00907D"},"#5D9AD4":{"color":["#89C5FF","#0F6AC2","#52AAFF","#01509D"],"pointer":"#F70707","palette":"#1E90FF"},"#8E82DA":{"color":["#B6AAFF","#5E4AD9","#9C8BFF","#412EB4"],"pointer":"#F70707","palette":"#7B68EE"},"#E779B0":{"color":["#FFB8DB","#E42786","#FF85C2","#C5186E"],"pointer":"#F70707","palette":"#FF69B4"},"#FF3D00":{"color":["#FF9670","#D73B02","#FF6D36","#BC3503"],"pointer":"#000000","palette":"#FF4500"},"#F09E39":{"color":["#FFC073","#C76D00","#FFA231","#A65B00"],"pointer":"#F70707","palette":"#FF8C00"},"#5FB05F":{"color":["#75F875","#49B517","#57E757","#3E9912"],"pointer":"#F70707","palette":"#22B522"},"#4682B4":{"color":["#8BBFEB","#28679C","#5E9BCE","#1D5482"],"pointer":"#F70707","palette":"#4682B4"},"#FF8C00":{"color":["#F43415","#FC5508","#F19A01","#FEBD01","#FDE503","#CCEC21","#52CD4E","#22A8EB","#5476DA","#5F20B9","#9C28AC","#D02962"],"pointer":"#000000","palette":"linear-gradient(180deg, #5F20B9 0%, #D02962 22%, #FC5508 43.5%, #FDE503 61.5%, #52CD4E 81%, #22A8EB 100%)"},"#e23e57":{"color":["#ffcdd2","#e57373","#e53935","#b71c1c"]},"#8c82fc":{"color":["#e1bee7","#ba68c8","#8e24aa","#4a148c"]},"#521262":{"color":["#d1c4e9","#9575cd","#5e35b1","#311b92"]},"#3490de":{"color":["#bbdefb","#64b5f6","#1e88e5","#0d47a1"]},"#086972":{"color":["#b2dfdb","#4db6ac","#00897b","#004d40"]},"#36622b":{"color":["#c8e6c9","#81c784","#43a047","#1b5e20"]},"#729d39":{"color":["#f0f4c3","#dce775","#c0ca33","#827717"]},"#ffb400":{"color":["#fff9c4","#fff176","#fdd835","#f57f17"]},"#f08a5d":{"color":["#ffe0b2","#ffb74d","#fb8c00","#e65100"]},"#393232":{"color":["#d7ccc8","#a1887f","#6d4c41","#3e2723"]},"#52616b":{"color":["#cfd8dc","#90a4ae","#546e7a","#263238"]},"#f67280":{"color":["#e6194b","#3cb44b","#ffe119","#0082c8","#f58231","#911eb4","#46f0f0","#f032e6","#d2f53c","#fabebe","#008080","#e6beff","#aa6e28","#fffac8","#800000","#aaffc3","#808000","#ffd8b1","#000080","#808080","#FFFFFF","#000000"]}}';
	}
	public static function remove_other_script() {
		global $wp_scripts;
		$scripts         = $wp_scripts->registered;
		$exclude_dequeue = apply_filters( 'viwlwl_exclude_dequeue_scripts', array(
			'dokan-vue-bootstrap',
			'query-monitor',
			'uip-app',
			'uip-vue',
			'uip-toolbar-app'
		) );
		foreach ( $scripts as $script ) {
			if ( in_array( $script->handle, $exclude_dequeue ) ) {
				continue;
			}
			preg_match( '/\/wp-/i', $script->src, $result );
			if ( count( array_filter( $result ) ) ) {
				preg_match( '/(\/wp-content\/plugins|\/wp-content\/themes)/i', $script->src, $result1 );
				if ( count( array_filter( $result1 ) ) ) {
					wp_dequeue_script( $script->handle );
				}
			} else {
				wp_dequeue_script( $script->handle );
			}
		}
		wp_dequeue_script( 'select-js' );//Causes select2 error, from ThemeHunk MegaMenu Plus plugin
		wp_dequeue_style( 'eopa-admin-css' );
	}

	public static function enqueue_style( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue' ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, VI_WOO_LUCKY_WHEEL_CSS . $srcs[ $i ] . $suffix_t . '.css', ! empty( $des[ $i ] ) ? $des[ $i ] : array(), VI_WOO_LUCKY_WHEEL_VERSION );
		}
	}

	public static function enqueue_script( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue', $in_footer = false ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'register' ? 'wp_register_script' : 'wp_enqueue_script';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, VI_WOO_LUCKY_WHEEL_JS . $srcs[ $i ] . $suffix_t . '.js', ! empty( $des[ $i ] ) ? $des[ $i ] : array( 'jquery' ),
				VI_WOO_LUCKY_WHEEL_VERSION, $in_footer );
		}
	}

}