<?php
/**
 * Class Constants -- this is to define plugin constants.
 *
 * @package OneDesign
 */

namespace OneDesign\Plugin_Configs;

use OneDesign\Traits\Singleton;

/**
 * Class Constants
 */
class Constants {

	/**
	 * Plugin constant variables.
	 *
	 * @var array $constants
	 */
	public static $constants;

	/**
	 * Child site api key.
	 *
	 * @var string
	 */
	public const ONEDESIGN_API_KEY = 'onedesign_child_site_api_key';

	/**
	 * Shared sites.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SHARED_SITES = 'onedesign_shared_sites';

	/**
	 * Site type.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SITE_TYPE = 'onedesign_site_type';

	/**
	 * Site type transient.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SITE_TYPE_TRANSIENT = 'onedesign_site_type_transient';

	/**
	 * Governing site request origin url.
	 *
	 * @var string
	 */
	public const ONEDESIGN_GOVERNING_SITE_URL = 'onedesign_governing_site_url';

	/**
	 * Shared templates.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SHARED_TEMPLATES = 'onedesign_shared_templates';

	/**
	 * Consumer sites pattern.
	 * Note: in all previous versions this was not prefixed with onedesign_ that's why we are keeping it same for backward compatibility.
	 *
	 * @var string
	 */
	public const CONSUMER_SITE_PATTERNS = 'consumer_site_patterns';

	/**
	 * Child sites.
	 * Note: in all modules we call it shared sites but to keep backward compatibility we are keeping the same name.
	 *
	 * @var string
	 */
	public const ONEDESIGN_CHILD_SITES = 'onedesign_child_sites';

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * Protected class constructor
	 */
	protected function __construct() {
		$this->define_constants();
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants(): void {
		// future constants can be defined here.
	}
}
