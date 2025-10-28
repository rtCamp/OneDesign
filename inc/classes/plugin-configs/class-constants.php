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
	 * Brand site patterns.
	 *
	 * Note: migrated from consumer_site_patterns to onedesign_brand_site_patterns for better clarity.
	 *
	 * @var string
	 */
	public const ONEDESIGN_BRAND_SITE_PATTERNS = 'onedesign_brand_site_patterns';

	/**
	 * Child sites.
	 * Note: in all modules we call it shared sites but to keep backward compatibility we are keeping the same name.
	 *
	 * @var string
	 */
	public const ONEDESIGN_CHILD_SITES = 'onedesign_child_sites';

	/**
	 * Brand site post id's.
	 *
	 * @var string
	 */
	public const ONEDESIGN_BRAND_SITE_POST_IDS = 'onedesign_brand_site_post_ids';

	/**
	 * Shared patterns.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SHARED_PATTERNS = 'onedesign_shared_patterns';

	/**
	 * Shared template parts.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SHARED_TEMPLATE_PARTS = 'onedesign_shared_template_parts';

	/**
	 * Shared synced patterns.
	 *
	 * @var string
	 */
	public const ONEDESIGN_SHARED_SYNCED_PATTERNS = 'onedesign_shared_synced_patterns';

	/**
	 * OneDesign rest namespace.
	 *
	 * @var string
	 */
	public const ONEDESIGN_REST_NAMESPACE = 'onedesign';

	/**
	 * OneDesign rest version.
	 *
	 * @var string
	 */
	public const ONEDESIGN_REST_VERSION = 'v1';

	/**
	 * Multisite governing site.
	 *
	 * @var string
	 */
	public const ONEDESIGN_MULTISITE_GOVERNING_SITE = 'onedesign_multisite_governing_site';

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
