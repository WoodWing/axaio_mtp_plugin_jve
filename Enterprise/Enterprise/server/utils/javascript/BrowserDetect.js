/**
 * This is a JavaScript utils class to manage Web Browsers detection.
 *
 * @since 10.5.0
 */
class BrowserDetect
{
    /**
     * Return true if the browser detected is an InternetExplorer or Edge, false otherwise.
     *
     * @since 10.5.0
     * @returns {boolean}
     */
    static isInternetExplorerOrEdge()
    {
        var ua = window.navigator.userAgent;
        // 'ua' values:
        // IE 10:
        // ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

        // IE 11:
        // ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';

        // Edge 12 (Spartan):
        // ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';

        // Edge 13:
        // ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

        return ua.indexOf('MSIE ') > 0 // IE 10 or older
            || ua.indexOf('Trident/') > 0 // IE 11
            || ua.indexOf('Edge/') > 0; // Edge(IE 12+)
    }
}