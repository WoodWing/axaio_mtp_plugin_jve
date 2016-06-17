<?php
	//Status: Bitflags!!
	define ('WW_LICENSE_GET',                '1');
	define ('WW_LICENSE_LOGON',              '2');
	define ('WW_LICENSE_ADDAPP',             '4');
	define ('WW_LICENSE_SUPPORT',            '8');
	define ('WW_LICENSE_TICKETS',           '16');
	define ('WW_LICENSE_REMOVE',            '32');

	//Error codes that are used by this module. Will be returned by the getLicenseStatus() function.
	define ('WW_LICENSE_OK',				'2000');
	define ('WW_LICENSE_OK_TMPCONFIG',		'2001');
	define ('WW_LICENSE_OK_WARNING',		'2002');
	define ('WW_LICENSE_OK_USERLIMIT',		'2003');
	define ('WW_LICENSE_OK_REMOVED',		'2004');
	define ('WW_LICENSE_OK_INTERNAL',		'2005');
	define ('WW_LICENSE_OK_MAX', 			'2009'); //max value of a status that is OK

	define ('WW_LICENSE_ERR_NOLICENSE1',	'2010');
	define ('WW_LICENSE_ERR_NOLICENSE2',	'2011');
	define ('WW_LICENSE_ERR_NOLICENSE3',	'2012');
	define ('WW_LICENSE_ERR_NOLICENSE4',	'2013');
	define ('WW_LICENSE_ERR_NOLICENSE5',	'2014');
	define ('WW_LICENSE_ERR_NOLICENSE6',	'2015');
	define ('WW_LICENSE_ERR_STARTTIME', 	'2021');
	define ('WW_LICENSE_ERR_EXPIREDTIME',	'2022');
	define ('WW_LICENSE_ERR_RENEWTIME',		'2023');
	define ('WW_LICENSE_ERR_EXPIREDMAXOBJ',	'2024');
	define ('WW_LICENSE_ERR_PRODKEY',		'2025');
	define ('WW_LICENSE_ERR_SERIAL',		'2026');
	define ('WW_LICENSE_ERR_SERIAL2',		'2027');
	define ('WW_LICENSE_ERR_UPDATE',		'2028');
	define ('WW_LICENSE_ERR_ENCRYPT',		'2029');
	define ('WW_LICENSE_ERR_SYSTEM',		'2030');
	define ('WW_LICENSE_ERR_SYSTIME',		'2031');
	define ('WW_LICENSE_ERR_UNKNOWNPRODUCT','2032');
	define ('WW_LICENSE_ERR_MAKEKEY',		'2033');
	define ('WW_LICENSE_ERR_OLDLICENSE',    '2034');
	define ('WW_LICENSE_ERR_INVALID_DATA',	'2035');

	//Error codes that are used by this module. Can be returned in error messages.
	define ('WWL_ERR_DB',                   '2051');
	define ('WWL_ERR_FILESTORE_SYSDIR',     '2052');
	define ('WWL_ERR_FILESTORE_FILE',       '2053');
	define ('WWL_ERR_FILESTORE_DB_MISMATCH','2054');
	define ('WWL_ERR_TIME',                 '2055');
	define ('WWL_ERR_TIME_DB1',             '2056');
	define ('WWL_ERR_TIME_DB2',             '2057');
	define ('WWL_ERR_TIME_DB3',             '2058');
	define ('WWL_ERR_TIME_DB4',             '2059');
	define ('WWL_ERR_KEY1_MISMATCH1',       '2060');
	define ('WWL_ERR_KEY1_MISMATCH2',       '2061');
	define ('WWL_ERR_EXPIRED',              '2062');
	define ('WWL_ERR_OLDLICENSE',           '2063');
	define ('WWL_ERR_DB_FIELDNOTEXIST',		'2064');
	define ('WWL_ERR_KEY1_DB',              '2065');
	define ('WWL_ERR_TIME_CONVERSION',      '2066');
	define ('WWL_ERR_DSTIME_DB',     		'2067');
	define ('WWL_ERR_DSTIME_CONVERT', 		'2068');
	define ('WWL_ERR_ASDS_TIMEDIFF', 		'2069');
	define ('WWL_ERR_SET_TICKET',	 		'2070');

	//Warnings:
	define ('WWL_WARNING_EXPIRE',           '2080');
	define ('WWL_WARNING_RENEW',            '2081');
	
	
	//Bitflags for the mTestFlags member variable
	define ('WWL_TEST_DBFS_COPY_FLAG',			'1'); //Bit 0, value = 1; enter demo mode when the filestore and/or database has been copied/recreated?
	define ('WWL_TEST_SYSTIME_ERRORTIME_FLAG',	'2'); //Bit 1, value = 2; stop when the system time has been set before the error date (e.g. before the expire date)?
	define ('WWL_TEST_SYSTIME_OBJECTS_FLAG',	'4'); //Bit 2, value = 4; stop when the system time has been set before the 'last valid created object'
?>