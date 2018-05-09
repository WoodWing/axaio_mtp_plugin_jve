<?php
/**
 * @package    Enterprise
 * @subpackage Utils
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Autoload PHP classes for Enterprise Server.
 *
 * Can resolve include PHP module file paths from PHP class names and automatically include those modules.
 * Only class names respecting the following notation: WW_[<Folder>_]*<File>
 * will be recognized and automatically included as:   <BASEDIR>/server/[<folder>/]*<file>.class.php
 * Note that [...]* means that ... is optional or can be repeated for 1-N times.
 *
 * For example, the files to include are resolved from class names as follows:
 *    ---------------------    ---------------------------------------------
 *    class name               file include
 *    ---------------------    ---------------------------------------------
 *    WW_BizClasses_Foo     => <BASEDIR>/server/bizclasses/Foo.class.php
 *    WW_DbClasses_Bar      => <BASEDIR>/server/dbclasses/Foo.class.php
 *    WW_BizClasses_Foo_Bar => <BASEDIR>/server/bizclasses/foo/Bar.class.php
 *
 * Note that the classes are camel-case, the folders are lowercase and the files are camel-case.
 */
class WW_Utils_Autoloader
{
	/**
	 * Register this autoloader. See module header for details.
	 */
	public static function register()
	{
		spl_autoload_register( function( $className ) {
			if( substr( $className, 0, 3 ) === 'WW_' ) {
				$classNameParts = explode( '_', $className );
				array_shift( $classNameParts );
				array_unshift( $classNameParts, 'server' );
				$fileNameBase = array_pop( $classNameParts );
				if( $fileNameBase ) {
					$folders = array_map('strtolower', $classNameParts );
					$folder = implode( '/', $folders );
					$file = BASEDIR.'/'.$folder.'/'.$fileNameBase.'.class.php';
					if( file_exists( $file ) ) {
						require $file;
						return true;
					}
				}
			}
			return false;
		} );
	}
}
