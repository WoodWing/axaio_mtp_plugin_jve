<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Autoload PHP classes for Enterprise Server.
 *
 * Can resolve include PHP module file paths from PHP class names and automatically include those modules.
 *
 * - - - - - - - - - - - CORE SERVER CLASSES - - - - - - - - - - -
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
 *
 * - - - - - - - - - - - CUSTOM PLUGIN CLASSES  - - - - - - - - - - -
 * Only class names respecting the following notation: <Plugin>_[<Folder>_]*<File>
 * will be recognized and automatically included as:   <BASEDIR>/config/plugins/<Plugin>/[<folder>/]*<file>.class.php
 * Note that [...]* means that ... is optional or can be repeated for 1-N times.
 *
 * For example, the files to include are resolved from class names as follows:
 *    ---------------------       ---------------------------------------------
 *    class name                  file include
 *    ---------------------       ---------------------------------------------
 *    Hello_BizClasses_Foo     => <BASEDIR>/config/plugins/Hello/bizclasses/Foo.class.php
 *    Hello_DbClasses_Bar      => <BASEDIR>/config/plugins/Hello/dbclasses/Foo.class.php
 *    Hello_BizClasses_Foo_Bar => <BASEDIR>/config/plugins/Hello/bizclasses/foo/Bar.class.php
 *
 * Note that the classes are camel-case, the folders are lowercase and the files are camel-case.
 */
class WW_Utils_Autoloader
{
	/** @var array */
	private static $serverPlugins = array();

	/**
	 * Register this autoloader. See module header for details.
	 */
	public static function register()
	{
		spl_autoload_register( function( $className ) {
			if( substr( $className, 0, 3 ) === 'WW_' ) { // core server classes
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
			} elseif( strpos( $className, '_' ) !== false ) { // plugin classes
				$classNameParts = explode( '_', $className ); // e.g. Hello_BizClasses_Foo_Bar => Hello,BizClasses,Foo,Bar
				$pluginName = array_shift( $classNameParts ); // e.g. Hello,BizClasses,Foo,Bar => BizClasses,Foo,Bar
				if( $pluginName && array_key_exists( $pluginName, self::$serverPlugins ) ) { // plugin registered?
					$fileNameBase = array_pop( $classNameParts ); // e.g. BizClasses,Foo,Bar => BizClasses,Foo
					if( $fileNameBase ) {
						$folders = array_map( 'strtolower', $classNameParts ); // e.g. BizClasses,Foo => bizclasses,foo
						$folder = implode( '/', $folders ); // e.g. bizclasses,foo => bizclasses/foo
						$file = BASEDIR.'/config/plugins/'.$pluginName.'/'.$folder.'/'.$fileNameBase.'.class.php';
								// L> e.g. <BASEDIR>/config/plugins/Hello/bizclasses/foo/Bar.class.php
						if( file_exists( $file ) ) {
							require $file;
							return true;
						}
						$file = BASEDIR.'/server/plugins/'.$pluginName.'/'.$folder.'/'.$fileNameBase.'.class.php';
						if( file_exists( $file ) ) {
							require $file;
							return true;
						}
					}
				}
			}
			return false;
		} );
	}

	/**
	 * Enable autoload of PHP classes defined by a given server plugin.
	 *
	 * @param string $pluginName
	 */
	public static function registerServerPlugin( string $pluginName )
	{
		self::$serverPlugins[ $pluginName ] = true;
	}
}
