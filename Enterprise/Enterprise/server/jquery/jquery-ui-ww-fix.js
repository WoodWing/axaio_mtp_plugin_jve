/**
 * @since Enterprise 10.2.1 This module has been introduced.
 *
 * This should be seen as temporary solution to the incompatible problem between the
 * jQuery-ui 1.8.24 and jQuery 1.10.
 *
 * All the workaround codes can be placed here.
 * Note that once jQuery-ui is upgraded, this module can be taken out, EN-90154.
 *
 * This file should be included whenever the file jquery-ui-1.8.24.custom.min.js is called/included.
 */

/**
 * Temporary solution for the deprecated jQuery.browser.
 *
 * Related Jira: EN-88726, EN-90153.
 *
 * @since 10.2.0 This method was first introduced in dbadmin.htm in 10.2.0 and now it is shifted to this file.
 * Running on jQuery-ui 1.8.24 and jQuery 1.10,
 * using jQuery.browser method ( deprecated and removed since jQuery 1.9 )
 * will give the following error:
 *   "Uncaught TypeError: Cannot read property 'msie' of undefined"
 *  As a work-around the following function is added:
 */
jQuery.browser = {};
(function () {
    jQuery.browser.msie = false;
    jQuery.browser.version = 0;
    if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
        jQuery.browser.msie = true;
        jQuery.browser.version = RegExp.$1;
    }
})();