<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VI_WOO_LUCKY_WHEEL_Plugins_Curcy{
	public static $settings, $is_pro, $enable, $cache;
	public function __construct() {
		if (!class_exists('WOOMULTI_CURRENCY') && !class_exists('WOOMULTI_CURRENCY_F')){
			return;
		}
		add_filter('wlwl_get_default_params',[$this,'wlwl_get_default_params']);
		add_filter('wlwl_get_price',[$this,'wlwl_get_price']);
		add_filter('wlwl_get_price_format',[$this,'wlwl_get_price_format']);
		add_filter('wlwl_woocommerce_price_num_decimals',[$this,'wlwl_woocommerce_price_num_decimals']);
		add_filter('wlwl_woocommerce_currency',[$this,'get_current_currency']);
	}
	public function wlwl_get_default_params($arg) {
		if (!$this->get_enable()){
			return $arg;
		}
		if (!is_array($arg)){
			$arg = [];
		}
		$arg['current_currency'] = $this->get_current_currency();
		return $arg;
	}
	public function wlwl_woocommerce_price_num_decimals($result){
		if (!$this->get_enable()){
			return $result;
		}
		$currencies      = $this->get_list_currencies();
		$currency = $this->get_current_currency();
		if (isset($currencies[ $currency ]['decimals'])){
			$result = (int)$currencies[ $currency ]['decimals'];
		}
		return $result;
	}
	public function wlwl_get_price_format($result){
		if (!$this->get_enable()){
			return $result;
		}
		$currencies      = $this->get_list_currencies();
		$currency = $this->get_current_currency();
		if (isset($currencies[ $currency ]['pos'])){
			$result = $currencies[ $currency ]['pos'];
		}
		return $result;
	}
	public function get_current_currency($currency =''){
		if (!$this->get_enable()){
			return $currency;
		}
		if (isset(self::$cache['current_currency'])){
			return self::$cache['current_currency'];
		}
		if ( isset($_REQUEST['_woocommerce_lucky_wheel_nonce']) &&
		     wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_woocommerce_lucky_wheel_nonce'])), 'woocommerce_lucky_wheel_nonce_action') ) {
			$current_currency       = isset( $_POST['current_currency'] ) ? wc_clean( wp_unslash( $_POST['current_currency'] ) ) : '';
			if ($current_currency) {
				self::$settings->set_current_currency( $current_currency );
				$currency = $current_currency;
			}
		}
		if (empty($current_currency)){
			$currency = self::$settings->get_current_currency();
		}
		self::$cache['current_currency'] = $currency;
		return self::$cache['current_currency'];
	}
	public function get_list_currencies(){
		if (!$this->get_enable()){
			return [];
		}
		if (isset(self::$cache['currencies'])){
			return self::$cache['currencies'];
		}
		add_filter( 'wmc_get_list_currencies', array( __CLASS__, 'wmc_get_list_currencies' ), 10, 1 );
		self::$cache['currencies'] = self::$settings->get_list_currencies();
		return self::$cache['currencies'];
	}
	public static function get_woocommerce_currency_symbol( $currency, $override = true ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}
		if ( ! isset( self::$cache['symbols'] ) ) {
			self::$cache['symbols'] = get_woocommerce_currency_symbols();
		}
		$currency_symbol = self::$cache['symbols'][ $currency ] ?? '';

		return $override ? apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency ) : $currency_symbol;
	}
	public static function wmc_get_list_currencies( $args ) {
		$result = $include = [];
		if ( self::$is_pro ) {
			$include = self::$settings->get_checkout_currency_args();
		}
		foreach ( $args as $currency => $param ) {
			if ( ! empty( $include ) && ! in_array( $currency, $include ) ) {
				continue;
			}
			$result[ $currency ]           = $param;
			$result[ $currency ]['symbol'] = ! empty( $param['custom'] ) ? $param['custom'] : self::get_woocommerce_currency_symbol( $currency, false );
			switch ( $param['pos'] ?? '' ) {
				case 'right':
					$result[ $currency ]['pos'] = '%2$s%1$s';
					break;
				case 'left_space':
					$result[ $currency ]['pos'] = '%1$s&nbsp;%2$s';
					break;
				case 'right_space':
					$result[ $currency ]['pos'] = '%2$s&nbsp;%1$s';
					break;
				default:
					$result[ $currency ]['pos'] = '%1$s%2$s';
					break;
			}
		}
		remove_filter( 'wmc_get_list_currencies', array( __CLASS__, 'wmc_get_list_currencies' ) );
		return $result;
	}
	public function wlwl_get_price($price){
		if (!$price || !$this->get_enable()){
			return $price;
		}
		return apply_filters('wmc_change_raw_price', $price);
	}
	public function get_enable() {
		if ( self::$enable !== null ) {
			return self::$enable;
		}
		$settings = $this->get_settings();
		if ( $settings ) {
			self::$enable = $settings->get_enable() && $settings->get_enable_multi_payment();
		}
		self::$enable = apply_filters( 'wlwl_curcy_enable', self::$enable );

		return self::$enable;
	}

	public function get_settings() {
		if ( self::$settings !== null ) {
			return self::$settings;
		}
		if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			self::$settings = WOOMULTI_CURRENCY_Data::get_ins( true );
			self::$is_pro   = true;
		} elseif ( class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
			self::$settings = WOOMULTI_CURRENCY_F_Data::get_ins();
		}

		return self::$settings;
	}
}
