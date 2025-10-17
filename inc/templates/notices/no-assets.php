<?php
/**
 * When running from GitHub repository, show admin notice to build assets and install composer dependencies.
 *
 * @package OneDesign
 */

?>

<div class="notice notice-error">
	<p>
		<?php
		printf(
			/* translators: %s is the plugin name. */
			esc_html__( 'You are running the %s plugin from the GitHub repository. Please build the assets and install composer dependencies to use the plugin.', 'onedesign' ),
			'<strong>' . esc_html__( 'OneDesign', 'onedesign' ) . '</strong>'
		);
		?>
	</p>
	<p>
		<?php
		printf(
			/* translators: %s is the command to run. */
			esc_html__( 'Run the following commands in the plugin directory: %s', 'onedesign' ),
			'<code>composer install && npm install && npm run build:prod</code>'
		);
		?>
	<p>
		<?php
		printf(
			/* translators: %s is the plugin name. */
			esc_html__( 'Please refer to the %s for more information.', 'onedesign' ),
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( 'https://github.com/rtCamp/OneDesign' ),
				esc_html__( 'OneDesign GitHub repository', 'onedesign' )
			)
		);
		?>
	</p>
</div>