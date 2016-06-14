<?php

// Settings used by the "Sips Preview" server plug-in.

// SIPS_RGB_PROFILE
//    File path to the RGB profile to be used by Sips.
//    Default: '/System/Library/ColorSync/Profiles/Generic RGB Profile.icc'
//
if( !defined('SIPS_RGB_PROFILE') ) {
	define( 'SIPS_RGB_PROFILE', '/System/Library/ColorSync/Profiles/Generic RGB Profile.icc' );
}

// SIPS_COMMAND
//    Path of the Sips command, which is used to generate preview/thumb files.
//    Default: '/usr/bin/sips'
//
if( !defined('SIPS_COMMAND') ) {
	define( 'SIPS_COMMAND', '/usr/bin/sips' );
}

// PS2PDF_COMMAND
//   Path of the pstopdf command, which is used to convert a PostScript file to PDF as first
//   step before Sips is called to generate preview/thumb from that.
//   Default: '/usr/bin/pstopdf'
//
if( !defined('PS2PDF_COMMAND') ) {
	define( 'PS2PDF_COMMAND', '/usr/bin/pstopdf' );
}