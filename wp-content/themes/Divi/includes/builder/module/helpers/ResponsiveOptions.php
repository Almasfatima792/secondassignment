<?php if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class ET_Builder_Module_Helper_ResponsiveOptions {

	const DESKTOP = 'desktop';
	const TABLET = 'tablet';
	const PHONE = 'phone';

	public static function instance() {
		static $instance;

		return $instance ? $instance : $instance = new self();
	}

	private function __construct() {
		// Now call me if you can
	}

	/**
	 * Returns responsive modes list from largest to narrow
	 *
	 * @return string[]
	 */
	public function get_modes() {
		return array( self::DESKTOP, self::TABLET, self::PHONE );
	}

	/**
	 * Returns next wider mode then provided
	 *
	 * @param $mode
	 *
	 * @return null|string
	 */
	public function get_wider_mode( $mode ) {
		$modes = $this->get_modes();
		$key   = array_search( $this->validate_mode( $mode ), $modes );

		return false != $key ? et_()->array_get( $modes, '[' . ( -- $key ) . ']', null ) : null;
	}

	/**
	 * Returns next narrower mode then provided
	 *
	 * @param $mode
	 *
	 * @return null|string
	 */
	public function get_narrower_mode( $mode ) {
		$modes = $this->get_modes();
		$key   = array_search( $this->validate_mode( $mode ), $modes );

		return false !== $key && isset( $modes[ $key + 1 ] ) ? $modes[ $key + 1 ] : null;
	}

	/**
	 * Return default responsive mode
	 *
	 * @return string
	 */
	public function get_default_mode() {
		return self::DESKTOP;
	}

	/**
	 * Returns setting field name by responsive mode
	 *
	 * @param $setting
	 * @param $mode
	 *
	 * @return string
	 */
	public function get_field( $setting, $mode ) {
		return $setting . $this->mode_field( (string) $this->validate_mode( $mode ) );
	}

	/**
	 * Returns setting field name of the last edited mode
	 *
	 * @param string $setting
	 *
	 * @return string
	 */
	public function get_last_edited_field( $setting ) {
		return "{$setting}_last_edited";
	}

	/**
	 * Checks if setting responsive mode is enabled
	 *
	 * @param $setting
	 * @param $props
	 *
	 * @return bool
	 */
	public function is_enabled( $setting, $props ) {
		$value = et_builder_module_prop( $this->get_last_edited_field( $setting ), $props, '' );

		return et_pb_get_responsive_status( $value );
	}

	/**
	 * Returns the props value by mode
	 * If no mode provided, the default mode is used
	 *
	 * @param $setting
	 * @param $props
	 * @param null $mode
	 * @param string $default
	 *
	 * @return mixed
	 */
	public function get_value( $setting, $props, $mode = null, $default = '' ) {
		$mode = $this->get_mode_or_default( $mode );

		if ( $this->get_default_mode() != $mode && ! $this->is_enabled( $setting, $props ) ) {
			return $default;
		}

		return et_builder_module_prop( $this->get_field( $setting, $mode ), $props, $default );
	}

	/**
	 * Is the implementation of get_value specifically for desktop mode
	 *
	 * Note: since the desktop mode is the default mode,
	 * this method would similar to get_value without providing mode,
	 * but can be used for a more explicit representation
	 *
	 * @param string $setting
	 * @param array $props
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_desktop_value( $setting, $props, $default = null ) {
		return $this->get_value( $setting, $props, self::DESKTOP, $default );
	}

	/**
	 * Is the implementation of get_value specifically for tablet mode
	 *
	 * @param string $setting
	 * @param array $props
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_tablet_value( $setting, $props, $default = null ) {
		return $this->get_value( $setting, $props, self::TABLET, $default );
	}

	/**
	 * Is the implementation of get_value specifically for phone mode
	 *
	 * @param string $setting
	 * @param array $props
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_phone_value( $setting, $props, $default = null ) {
		return $this->get_value( $setting, $props, self::PHONE, $default );
	}

	/**
	 * Returns the last edited responsive mode of the provided setting
	 * If not valid value is provided, default mode is returned
	 *
	 * @param $setting
	 * @param $props
	 *
	 * @return string
	 */
	public function get_last_edited( $setting, $props ) {
		$value = et_builder_module_prop( $this->get_last_edited_field( $setting ), $props, '' );
		$mode  = et_()->array_get( explode( '|', $value ), '[1]' );

		return $this->validate_mode( $mode ) ? $mode : $this->get_default_mode();
	}

	/**
	 * @param $mode
	 *
	 * @return bool|string
	 */
	protected function validate_mode( $mode ) {
		return in_array( strtolower( $mode ), $this->get_modes() ) ? strtolower( $mode ) : false;
	}

	protected function get_mode_or_default( $mode ) {
		return $this->validate_mode( $mode ) ? strtolower( $mode ) : $this->get_default_mode();
	}

	/**
	 * Returns mode suffix
	 * The default mode suffix is empty
	 *
	 * @param $mode
	 *
	 * @return string
	 */
	protected function mode_field( $mode ) {
		switch ( $mode ) {
			case $this->get_default_mode() :
				return '';
			default:
				return "_$mode";
		}
	}
}

/**
 * @return ET_Builder_Module_Helper_ResponsiveOptions
 */
function et_pb_responsive_options() {
	return ET_Builder_Module_Helper_ResponsiveOptions::instance();
}
