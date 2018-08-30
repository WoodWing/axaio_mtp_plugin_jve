<?php
/**
 * Plugin Name: WoodWing Enterprise
 * Plugin URI: http://woodwing.com
 * Description: This plugin extends some functionality from WordPress and is used by Enterprise Server.
 * Version: 10.5.0 Build 251
 * Author: WoodWing Software
 */

include_once ABSPATH . 'wp-admin/includes/plugin.php' ;
require_once ABSPATH . 'wp-content/plugins/ww_enterprise/ww_public_post_preview.php';

/**
 * Check user rights
 *
 * Check if the user has administrator rights because this is needed to do the import of users in Enterprise.
 *
 * @param $user
 * @return bool
 */
function woodwing_CheckUserRights( $user )
{
	foreach( $user->roles as $role ){
		if( $role == 'administrator' ){
			return true;
		}
	}

	return false;
}

/**
 * Get the required blog version
 *
 * This function gets the current WordPress version.
 *
 * @return mixed
 */
function woodwing_GetBlogVersion()
{
	$blogInfo = get_bloginfo( 'version' );
	return $blogInfo;
}

/**
 * This function can check the plugin version with the required version.
 * If the version is not the same it will return the plugin version and the required version.
 *
 * @param string $requiredVersion
 * @return mixed
 */
function woodwing_CheckPluginVersion( $requiredVersion )
{
	$correctVersion = true;
	$pluginData = get_plugin_data(__FILE__);

	if( $pluginData['Version'] != $requiredVersion ){
		$correctVersion = false;
		$retVal['pluginVersion'] = $pluginData['Version'];
		$retVal['requiredVersion'] = $requiredVersion;
	}

	$retVal['correctVersion'] = $correctVersion;

	return $retVal;
}

/**
 * WordPress configuration test
 *
 * Tests if everything is configured the right way. If there is a problem this function will return it.
 * Also checks the version of the plugin.
 *
 * @param array $args
 * @return bool
 */
function woodwing_PluginTest( $args )
{
	$username = $args[0];
	$password = $args[1];
	$requiredVersion = $args[2];

	$user = wp_authenticate($username, $password);
	$retVal['successful'] = true;
	$retVal['wordpressVersion'] = woodwing_GetBlogVersion();

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

	$pluginVersionCheck = woodwing_CheckPluginVersion( $requiredVersion );

	if( !$pluginVersionCheck['correctVersion'] ){
		$retVal['successful'] = false;
		$retVal['errors'] = $pluginVersionCheck;
		return $retVal;
	}

	return $retVal;
}

/**
 * Create public preview.
 *
 * This function creates and returns a public preview url.
 *
 * @param $args
 * @return boolean|IXR_Error
 */
function woodwing_CheckNextGenEnabled( $args )
{
	$username = $args[0];
	$password = $args[1];

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

	return is_plugin_active( 'nextgen-gallery/nggallery.php' ) ;
}

/**
 * Create public preview.
 *
 * This function creates and returns a public preview url.
 *
 * @param $args
 * @return string|IXR_Error
 */
function woodwing_GetPreviewUrl( $args )
{
	require_once ABSPATH . 'wp-includes/class-wp-xmlrpc-server.php';
	$username = $args[1];
	$password = $args[2];
	$wpServer = new wp_xmlrpc_server();
	$PostPreview = new WW_Public_Post_Preview();

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

	try{
		$externalId = $wpServer->mw_newPost( $args );
	}catch( Exception $e ){
		return new IXR_Error($e, $e);
	}

	$publishedPost = get_post( $externalId );
	$PostPreview->woodwing_RegisterPublicPreview( $externalId, $publishedPost );
	$previewUrl = $PostPreview->woodwing_GetPreviewLink( $externalId );
	$PostPreview->woodwing_AddPublishedPreview( $externalId );

	return $previewUrl;
}

/**
 * Delete previews.
 *
 * Delete all the preview data from WordPress.
 *
 * @param $args
 * @return bool|IXR_Error
 */
function woodwing_DeleteOldPreviews( $args )
{
	global $wpdb;
	$username = $args[0];
	$password = $args[1];
	$postPreview = new WW_Public_Post_Preview();

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

	$expiredPreviews = $postPreview->woodwing_GetAllExpiredPreviews();

	if( $expiredPreviews ){
		foreach ( $expiredPreviews as $preview ){
            woodwing_DeleteInlineAndFeaturedImages( null, $preview->post_id );
			$encodedGalleries = woodwing_GetGalleries( null, $preview->post_id );

			if($encodedGalleries['galleries']){
				$encodedGalleries = reset($encodedGalleries['galleries']);
				$galleries = json_decode( $encodedGalleries );

				foreach( $galleries as $galleryId ){
					woodwing_DeleteGallery( $galleryId );
				}
			}
			wp_delete_post( $preview->post_id );
			$wpdb->delete( $postPreview->woodwing_GetPreviewTableName(), array( 'preview_id' => $preview->preview_id ), array('%d'));
		}
	}
	return true;
}

/**
 * Delete post and content.
 *
 * Delete all the content from a post and the post itself.
 *
 * @param $args
 * @return IXR_Error|Bool
 */
function woodwing_DeletePostAndContent( $args )
{
	$username = $args[0];
	$password = $args[1];
	$postId = $args[2];

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

    //woodwing_DeleteInlineAndFeaturedImages( null, $postId );
	$encodedGalleries = woodwing_GetGalleries( null, $postId );

	if($encodedGalleries['galleries']){
		$encodedGalleries = reset($encodedGalleries['galleries']);
		$galleries = json_decode( $encodedGalleries );

		foreach( $galleries as $galleryId ){
			woodwing_DeleteGallery( $galleryId );
		}
	}
	return wp_delete_post( $postId );
}

/**
 * Delete inline and featured images by post id.
 *
 * This function will get the custom field for inline images and featured images.
 * Then it will delete the uploaded images.
 *
 * @param array $args
 * @param int $internalPostId used for when there is no authentication needed
 *
 * @return bool|int|IXR_Error
 */
function woodwing_DeleteInlineAndFeaturedImages( $args, $internalPostId = null )
{
    $retVal = array();

	if( !$internalPostId ){
		$username = $args[0];
		$password = $args[1];
		$postId = $args[2];

		$user = wp_authenticate($username, $password);

		if (is_a(( $user ), 'WP_Error' )){
			return new IXR_Error( 403, 'Invalid username or password' );
		}

		$correctRights = woodwing_CheckUserRights( $user );

		if( !$correctRights ){
			return new IXR_Error( 403, 'User does not have the correct permission' );
		}
	} else{
		$postId = $internalPostId;
	}

    $inlineCustomFieldId = woodwing_GetMetaDataIdByKey( $postId, 'Enterprise-inline-images' );
    $featuredCustomFieldId = woodwing_GetMetaDataIdByKey( $postId, 'Enterprise-featured-images' );

	if( $inlineCustomFieldId ){
		$postMeta = get_post_meta( $postId, 'Enterprise-inline-images' );

		$inlineCustomValue = reset( $postMeta );

		$inlineIds = json_decode( $inlineCustomValue );

		if( $inlineIds ){
			foreach( $inlineIds as $inlineImage ){
				woodwing_DeleteImage( $inlineImage );
			}
		}

        $retVal['inline-custom-field'] = $inlineCustomFieldId;
	}

    if( $featuredCustomFieldId ){
        $postMeta = get_post_meta( $postId, 'Enterprise-featured-images' );

        $featuredCustomValue = reset( $postMeta );

        $featuredId = json_decode( $featuredCustomValue );

        if( $featuredId ){
            woodwing_DeleteImage( $featuredId );
        }

        $retVal['featured-custom-field'] = $featuredCustomFieldId;
    }

	return $retVal;
}

/**
 * Get metadata id
 *
 * Get the id for a metadata field by its key
 *
 * @param int $post_id
 * @param string $meta_key
 * @return bool|int
 */
function woodwing_GetMetaDataIdByKey( $post_id, $meta_key )
{
	global $wpdb;

	$mid = $wpdb->get_var( $wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key) );

	if( $mid ){
		return (int)$mid;
	}

	return false;
}

/**
 * Get galleries
 *
 * Get the galleries and the custom field id from WordPress.
 *
 * @param array $args
 * @param $internalPostID used for when there is no authentication needed
 *
 * @return array|IXR_Error|bool
 */
function woodwing_GetGalleries( $args, $internalPostID = null )
{
	if( !$internalPostID ){
		$username = $args[0];
		$password = $args[1];
		$postId = $args[2];

		$user = wp_authenticate($username, $password);

		if (is_a(( $user ), 'WP_Error' )){
			return new IXR_Error( 403, 'Invalid username or password' );
		}

		$correctRights = woodwing_CheckUserRights( $user );

		if( !$correctRights ){
			return new IXR_Error( 403, 'User does not have the correct permission' );
		}
	}else{
		$postId = $internalPostID;
	}

	$customFieldId = woodwing_GetMetaDataIdByKey( $postId, 'Enterprise-nextgen-galleries' );

	if( $customFieldId ){
		$postMeta = get_post_meta( $postId, 'Enterprise-nextgen-galleries' );
		return array( 'galleries' => $postMeta, 'customFieldId' => $customFieldId );
	}else{
		return false;
	}
}

/**
 * Delete A NextGen Gallery with all the images
 *
 * This is needed because the NextGen plugin is not doing this correctly.
 *
 * @param int $galleryId used for when there is no authentication needed
 *
 * @return bool
 */
function woodwing_DeleteGallery( $galleryId )
{
	global $wpdb;

	if($galleryId){
		// get the path to the gallery
		$gallery = nggdb::find_gallery($galleryId);
		if ($gallery){
			//TODO:Remove also Tag reference, look here for ids instead filename
			$imageList = $wpdb->get_col("SELECT filename FROM $wpdb->nggpictures WHERE galleryid = '$gallery->gid' ");

			$galleryPath = null;

			if( defined('NGG_PLUGIN_VERSION') ){
				if( version_compare(NGG_PLUGIN_VERSION, '2.0.40', '>') ){
					$galleryPath = $gallery->abspath;
				} else {
					$galleryPath = $gallery->path;
				}
			} else {return false;} // do not continue NGG is not installed.

			if (is_array($imageList)) {
				foreach ($imageList as $filename) {
					$thumbFile = $galleryPath . '/thumbs/thumbs_' . $filename;
					$orgFile = $galleryPath .'/'. $filename;
					$backUpFile = $galleryPath.'/'. $filename . '_backup';

					if( file_exists( $thumbFile )){
						unlink( $thumbFile );
					}

					if( file_exists( $orgFile )){
						unlink( $orgFile );
					}

					if( file_exists( $backUpFile )){
						unlink( $backUpFile );
					}
				}
			}

			// delete folders
			if( file_exists( $galleryPath . '/thumbs' )){
				rmdir( $galleryPath . '/thumbs' );
			}

			if( file_exists( $galleryPath )){
				rmdir( $galleryPath );
			}
		}
		do_action('ngg_delete_gallery', $galleryId);
		$deleted = nggdb::delete_gallery( $galleryId );

	}

	if( !$deleted ){
		return false;
	}

	return true;
}

/**
 *
 * Delete A NextGen image
 *
 * This is needed because the NextGen plugin is not doing this correctly.
 *
 * @param array $args
 * @return bool|IXR_Error
 */
function woodwing_DeleteNextgenImage( $args )
{
	$username = $args[0];
	$password = $args[1];
	$imageId = $args[2];

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have the correct permission' );
	}

	global $nggdb;
	$delete_pic = false;
	$image = $nggdb->find_image( $imageId );

	if ($image) {
		@unlink($image->imagePath);
		@unlink($image->thumbPath);
		@unlink($image->imagePath."_backup");

		do_action('ngg_delete_picture', $image->pid);
		$delete_pic = nggdb::delete_image( $image->pid );
	}

	if( !$delete_pic ){
		return false;
	}

	return true;
}

/**
 * Delete WordPress Image
 *
 * Delete a image from the WordPress media library.
 * Only used internally so there is no user checking in this function.
 *
 * @param array $externalId
 * @return bool|IXR_Error
 */
function woodwing_DeleteImage( $externalId )
{
	if(!wp_delete_post( $externalId )){
		return false;
	}

	return true;
}

/**
 * Update WordPress image metadata
 *
 * This function updates the metadata of an image, this is something the standard xml-rpc does not support.
 *
 * @param array $args
 * @return bool|IXR_Error
 */
function woodwing_UpdateImageMetaData( $args )
{
	require_once ABSPATH . 'wp-includes/class-wp-xmlrpc-server.php';
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape( $args );

	$username = $args[0];
	$password = $args[1];
	$externalId = $args[2];
	$name = $args[3];
	$description = $args[4];
	$alternativeText = $args[5];
	$caption = $args[6];

	$user = wp_authenticate($username, $password);

	if (is_a(( $user ), 'WP_Error' )){
		return new IXR_Error( 403, 'Invalid username or password' );
	}

	$correctRights = woodwing_CheckUserRights( $user );

	if( !$correctRights ){
		return new IXR_Error( 403, 'User does not have corrects Permissions' );
	}

	$updatePost = array(
		'ID' => $externalId,
		'post_title' => $name,
		'post_excerpt' => $caption,
		'post_content' => $description
	);

	update_metadata( 'post', $externalId, '_wp_attachment_image_alt',  $alternativeText ); // returns false if not updated or if is the same

	if( wp_update_post( $updatePost ) != $externalId ){
		return new IXR_Error( 0, 'Updating metdata failed' );
	}

	return true;
}

/**
 * Create new XML-RPC methods
 *
 * Extend WordPress with extra XML-RPC functions that are needed for Enterprise server.
 *
 * @param array $methods
 * @return mixed
 */
function woodwing_NewXmlRpcMethods( $methods )
{
	$methods['woodwing.CheckNextGenEnabled'] = 'woodwing_CheckNextGenEnabled';
	$methods['woodwing.GetPreviewUrl'] = 'woodwing_GetPreviewUrl';
	$methods['woodwing.DeleteOldPreviews'] = 'woodwing_DeleteOldPreviews';
	$methods['woodwing.PluginTest'] = 'woodwing_PluginTest';
	$methods['woodwing.DeleteImage'] = 'woodwing_DeleteImage';
	$methods['woodwing.DeleteInlineAndFeaturedImages'] = 'woodwing_DeleteInlineAndFeaturedImages';
	$methods['woodwing.GetGalleries'] = 'woodwing_GetGalleries';
	$methods['woodwing.DeleteNextgenImage'] = 'woodwing_DeleteNextgenImage';
	$methods['woodwing.DeletePostAndContent'] = 'woodwing_DeletePostAndContent';
	$methods['woodwing.UpdateImageMetaData'] = 'woodwing_UpdateImageMetaData';

	return $methods;
}

/**
 * Set link lifetime.
 *
 * The lifetime of a link can be set in this function.
 *
 * @return int returns the link valid time
 */
function woodwing_LinkNonceLife()
{
	return 60 * 60; // 1 hour
}

add_filter( 'woodwing_nonce_life', 'woodwing_LinkNonceLife' );
add_filter( 'xmlrpc_methods', 'woodwing_NewXmlRpcMethods');
add_action( 'plugins_loaded', array( 'WW_Public_Post_Preview', 'woodwing_Init' ) );
register_activation_hook( __FILE__, array( 'WW_Public_Post_Preview','woodwing_InstallPreviewTable' ));

register_uninstall_hook( __FILE__, array( 'WW_Public_Post_Preview', 'woodwing_Uninstall' ));