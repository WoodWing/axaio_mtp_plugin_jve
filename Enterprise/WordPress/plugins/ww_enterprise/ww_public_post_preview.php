<?php

/**
 * Don't call this file directly.
 */
if ( ! class_exists( 'WP' ) ) {
	die();
}

class WW_Public_Post_Preview {

	/**
	 * Init public preview functions.
	 *
	 * This function is needed for the public preview links.
	 */
	public static function woodwing_Init()
	{
		if ( ! is_admin() ) {
			add_filter( 'pre_get_posts', array( __CLASS__, 'woodwing_ShowPublicPreview' ) );

			add_filter( 'query_vars', array( __CLASS__, 'woodwing_AddQueryVar' ) );

			// Add the query var to WordPress SEO by Yoast whitelist.
			add_filter( 'wpseo_whitelist_permalink_vars', array( __CLASS__, 'woodwing_AddQueryVar' ) );
		}
	}

	/**
	 * Returns the public preview link.
	 *
	 * The link is the permalink with 2 added parameters.
	 *
	 * @param  int $post_id
	 * @return string
	 */
	public static function woodwing_GetPreviewLink( $post_id )
	{
		$link = add_query_arg(
			array(
				'preview' => true,
				'_woodwing'    => self::woodwing_CreateNonce( 'woodwing_public_post_preview_' . $post_id ),
			),
			get_permalink( $post_id )
		);

		return apply_filters( 'woodwing_preview_link', $link, $post_id );
	}

	/**
	 * (Un-)Register a public preview link.
	 *
	 * Registers or un-registers a post for a public preview link.
	 *
	 * @param  int $post_id
	 * @param  object $post
	 * @return bool
	 */
	public static function woodwing_RegisterPublicPreview( $post_id, $post )
	{
		if ( wp_is_post_revision( $post_id ) )
			return false;

		$preview_post_ids = self::woodwing_GetPreviewPostIds();
		$preview_post_id  = $post->ID;

		if ( empty( $_POST['woodwing_public_post_preview'] ) && in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
		elseif (
			! empty( $_POST['woodwing_public_post_preview'] ) &&
			! empty( $_POST['original_post_status'] ) &&
			'publish' != $_POST['original_post_status'] &&
			'publish' == $post->post_status &&
			in_array( $preview_post_id, $preview_post_ids )
		)
			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
		elseif ( ! in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_merge( $preview_post_ids, (array) $preview_post_id );
		else
			return false; // Nothing changed.

		return self::woodwing_SetPreviewPostIds( $preview_post_ids );
	}

	/**
	 * Add query variable
	 *
	 * Registers the new query var `_woodwing`.
	 *
	 * @param array $qv
	 * @return array
	 */
	public static function woodwing_AddQueryVar( $qv )
	{
		$qv[] = '_woodwing';

		return $qv;
	}

	/**
	 * Register filter to handle a public preview link.
	 *
	 * Filter will be set if it's the main query, a preview, a singular page
	 * and the query var `_woodwing` exists.
	 *
	 * @param  object $query
	 * @return object
	 */
	public static function woodwing_ShowPublicPreview( $query )
	{
		if (
			$query->is_main_query() &&
			$query->is_preview() &&
			$query->is_singular() &&
			$query->get( '_woodwing' )
		)
			add_filter( 'posts_results', array( __CLASS__, 'woodwing_SetPostToPublish' ), 10, 2 );

		return $query;
	}

	/**
	 * Check availability of public preview.
	 *
	 * Checks if a public preview is available and allowed.
	 * Verifies the nonce and if the post id is registered for a public preview.
	 *
	 * @param  int $post_id
	 * @return bool
	 */
	private static function woodwing_PublicPreviewAvailable( $post_id )
	{
		if ( empty( $post_id ) )
			return false;

		if ( ! self::woodwing_VerifyNonce( get_query_var( '_woodwing' ), 'woodwing_public_post_preview_' . $post_id ) )
			wp_die( __( 'The link has been expired!', 'ww-public-post-preview' ) );

		if ( ! in_array( $post_id, self::woodwing_GetPreviewPostIds() ) )
			wp_die( __( 'No Public Preview available!', 'ww-public-post-preview' ) );

		return true;
	}

	/**
	 * Set the post to publish
	 *
	 * Sets the post status of the first post to publish, so we don't have to do anything
	 *
	 * @param array $posts
	 * @return array|null
	 */

	public static function woodwing_SetPostToPublish( $posts )
	{
		// Remove the filter again, otherwise it will be applied to other queries too.
		remove_filter( 'posts_results', array( __CLASS__, 'woodwing_SetPostToPublish' ), 10, 2 );

		if ( empty( $posts ) )
			return null;

		$post_id = $posts[0]->ID;

		// If the post has gone live, redirect to it's proper permalink
		self::woodwing_MaybeRedirectToPublishedPost( $post_id );

		if ( self::woodwing_PublicPreviewAvailable( $post_id ) )
			$posts[0]->post_status = 'publish';

		return $posts;
	}

	/**
	 * Redirect to post
	 *
	 * Redirect to the post
	 *
	 * @param  int $post_id
	 * @return boolean
	 */
	private static function woodwing_MaybeRedirectToPublishedPost( $post_id )
	{
		if ( 'publish' != get_post_status( $post_id ) )
			return false;

		wp_redirect( get_permalink( $post_id ), 301 );
		exit;
	}

	/**
	 * Get nonce life
	 *
	 * Get the time-dependent variable for nonce creation.
	 *
	 * @return int
	 */
	private static function woodwing_NonceTick()
	{
		$nonce_life = apply_filters( 'woodwing_nonce_life', 60 * 60 * 48 ); // 48 hours

		return ceil( time() / ( $nonce_life / 2 ) );
	}

	/**
	 * Create nonce
	 *
	 * Creates a random, one time use token.
	 *
	 * @param  string|int $action
	 * @return string
	 */
	private static function woodwing_CreateNonce( $action = -1 )
	{
		$i = self::woodwing_NonceTick();

		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	/**
	 * Verify nonce
	 *
	 * Verifies that correct nonce was used with time limit.
	 *
	 * @param  string $nonce
	 * @param  string|int $action
	 * @return bool
	 */
	private static function woodwing_VerifyNonce( $nonce, $action = -1 )
	{
		$i = self::woodwing_NonceTick();

		// Nonce generated 0-12 hours ago
		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce )
			return 1;

		// Nonce generated 12-24 hours ago
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce )
			return 2;

		// Invalid nonce
		return false;
	}

	/**
	 * Get preview post ids
	 *
	 * Returns the post ids which are registered for a public preview link.
	 *
	 * @return array
	 */
	private static function woodwing_GetPreviewPostIds()
	{
		return get_option( 'woodwing_public_post_preview', array() );
	}

	/**
	 * Set preview post ids
	 *
	 * Saves the post ids which are registered for a public preview link.
	 *
	 * @param array $post_ids
	 * @return array
	 */
	private static function woodwing_SetPreviewPostIds( $post_ids = array( ))
	{
		return update_option( 'woodwing_public_post_preview', $post_ids );
	}

	/**
	 * Uninstall option
	 *
	 * Deletes the option 'woodwing_public_post_preview' if the plugin will be uninstalled.
	 */
	public static function woodwing_Uninstall()
	{
		delete_option( 'woodwing_public_post_preview' );
	}

	/**
	 * Get all of the expired
	 *
	 * This function gets all the expired previews from the database.
	 * At the time of writing all previews of 1 hour and older will be removed
	 *
	 * @return array
	 */
	public static function woodwing_GetAllExpiredPreviews()
	{
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare('SELECT * FROM '. self::woodwing_GetPreviewTableName() . ' WHERE `time_created` <= (NOW()-60*60)'));

		return $results;
	}

	/**
	 * Install Preview table.
	 *
	 * This function installs the needed preview table when it not exists,
	 * this table is needed for deleting old previews.
	 */
	public static function woodwing_InstallPreviewTable ()
	{
		$table_name = self::woodwing_GetPreviewTableName();
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
				`preview_id` bigint(20) NOT NULL AUTO_INCREMENT,
  				`post_id` bigint(20) NOT NULL,
  				`time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  				PRIMARY KEY (`preview_id`)
			)' ;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Get preview table name
	 *
	 * this function returns the table name of the Preview table.
	 *
	 * @return string
	 */
	public static function woodwing_GetPreviewTableName()
	{
		global $wpdb;
		return $wpdb->prefix . 'ww_previews';
	}

	/**
	 * Add preview to db.
	 *
	 * Add a published preview to the database. This will be used for cleaning the previews.
	 * If the table does not exist this will also call the function to create the table
	 *
	 * @param $postId
	 */
	public function woodwing_AddPublishedPreview( $postId )
	{
		global $wpdb;

		WW_Public_Post_Preview::woodwing_InstallPreviewTable();

		$wpdb->insert(
			self::woodwing_GetPreviewTableName(),
			array(
				'post_id' => $postId,
			),
			array(
				'%d'
			)
		);
	}
}
