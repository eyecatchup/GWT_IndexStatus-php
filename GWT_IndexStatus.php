<?php
/** GWT_IndexStatus              Request 'Index Status'-data from Webmaster Tools.
 *
 *  What is 'Index Status'?
 *
 *  'Index Status' is a feature in Google's Webmaster Tools.
 *
 *  It shows totals of indexed pages, the cumulative number of pages crawled,
 *  the number of pages that Google knows about which are not crawled because
 *  they are blocked by robots.txt, and also the number of pages that were
 *  not selected for inclusion in Google's search results.
 *
 *  NOTE: The author of the software is not a partner, affiliate, or licensee
 *  of Google Inc. or its employees, nor is the software in any other way
 *  formally associated with or legitimized by Google Inc.. Google is a registered
 *  trademark of Google Inc.. Use of the trademark is subject to Google Permissions.
 *  @see http://www.google.com/permissions/index.html
 *
 *  @category
 *  @package     GWT_IndexStatus
 *  @author      Stephan Schmitz <eyecatchup@gmail.com>
 *  @copyright   2012 Stephan Schmitz
 *  @version     CVS: $Id: GWT_IndexStatus.php,v 1.0.0 Rev 3 2014/05/08 16:51:17 ssc Exp $
 *  @license     http://eyecatchup.mit-license.org
 *  @link        https://github.com/eyecatchup/GWT_IndexStatus-php/
 *
 *  LICENSE: Permission is hereby granted, free of charge, to any person
 *  obtaining a copy of this software and associated documentation files
 *  (the "Software"), to deal in the Software without restriction, including
 *  without limitation the rights to use, copy, modify, merge, publish, distribute,
 *  sublicense, and/or sell copies of the Software, and to permit persons to whom
 *  the Software is furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*- INTERFACE -*/

/**
 *  Set login credentials for a Webmaster Tools account to be used
 *  when not setting explicitly per instance via `GWT_IndexStatus::login()`.
 *  @see GWT_IndexStatus::__construct()
 *  @see GWT_IndexStatus::login()
 */
interface GWT_Client
{
    const Email = '';
    const Passwd = '';
}

/*- CLASS -*/

class GWT_IndexStatus implements GWT_Client
{
    protected $requestParams, $_auth;

    public function __construct ()
    {
        $this->_auth = FALSE;
        $this->_sites = Array();

        $this->requestParams = Array(
            'is-crawl' => 1,     # Ever crawled
            'is-indx' => 1,      # Total indexed
            'is-rm' => 1,        # Removed
            'is-rbt' => 1 );     # Blocked by robots

        if (0 < strlen(GWT_Client::Email) AND 0 < strlen(GWT_Client::Passwd))
            self::login(GWT_Client::Email, GWT_Client::Passwd);
    }

    public function getDataAllDomains ()
    {
        if (FALSE === $this->_auth) :
            throw new Exception('You must login first!');
            exit(0);
        endif;

        $arr = Array();
        foreach ($this->_sites as $domain) {
            $data = json_decode(self::getDataByDomain($domain), TRUE);
            $arr[] = Array(
                'domain' => $domain,
                'indexData' => $data );
        }

        return json_encode($arr);
    }

    public function getDataByDomain ($domain)
    {
        if (FALSE === $this->_auth) :
            throw new Exception('You must login first!');
            exit(0);

        elseif (!in_array($domain, $this->_sites)) :
            throw new Exception('Given domain is not associated ' .
                'with your Google Webmaster Tools account!');
            exit(0);

        else :
            return self::_getIndexData($domain);
        endif;
    }

    public function login ($clientEmail, $clientPasswd)
    {
        // Must be a valid email address.
        if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) :
            throw new Exception('Check your login email!');
            exit(0);

        // Google account passwords must have - at least - six chars.
        elseif (!is_string($clientPasswd) OR 6 > strlen($clientPasswd)) :
            throw new Exception('Check your login password!');
            exit(0);

        else :
            if (TRUE !== self::_login($clientEmail, $clientPasswd) ) :
                throw new Exception('Login failed! Check your login email/password.');
                exit(0);

            else :
                // If login succeeded, load sites feed to get all domains for the GWT account.
                $sitesFeed = self::GWT_Curl('webmasters/tools/feeds/sites/', FALSE, TRUE);
                if (FALSE !== $sitesFeed) :

                    $doc = new DOMDocument();
                    $doc->loadXML($sitesFeed);

                    foreach ($doc->getElementsByTagName('entry') as $node) {
                        $this->_sites[] = $node->getElementsByTagName('title')->item(0)->nodeValue;
                    }

                    return TRUE;

                else :
                    throw new Exception('Login succeeded, but there\'re no websites ' .
                        'associated with your Google Webmaster Tools account?!');
                    exit(0);
                endif;
            endif;
        endif;
    }

    private function _login ($clientEmail, $clientPasswd)
    {
        $postData = Array(
            'accountType' => 'HOSTED_OR_GOOGLE',
            'Email' => $clientEmail,
            'Passwd' => $clientPasswd,
            'service' => 'sitemaps',
            'source' => 'GWT_IndexStatus-0.1-php' );

        // Before PHP version 5.2.0 and when the first char of $pass is an @ symbol, 
        // send data in CURLOPT_POSTFIELDS as urlencoded string.
        if ('@' === (string)$pass[0] || version_compare(PHP_VERSION, '5.2.0') < 0) {
            $postData = http_build_query($postData);
        }

        $response = self::GWT_Curl('accounts/ClientLogin', $postData);
        @preg_match('/Auth=(.*)/', $response, $match);

        if (FALSE !== $response AND isset($match[1])) :
            $this->_auth = $match[1];
            return TRUE;
        else :
            return FALSE;
        endif;
    }

    private function _setParam($k, $v)
    {
        $validValue = (bool) (1 === $v OR 0 === $v);
        if (array_key_exists($k, $this->requestParams) AND TRUE === $validValue) :
            $this->requestParams[$k] = (int)$v;
            return TRUE;
        else :
            return FALSE;
        endif;
    }

    private function _getIndexData ($domain)
    {
        $requestPath = sprintf('webmasters/tools/index-status?siteUrl=%s&is-view=a',
            urlencode($domain) );

        foreach ($this->requestParams as $k => $v) {
            if (1 == $v) $requestPath .= sprintf('&%s=%s', $k, 'true');
        }

        $response = self::GWT_Curl($requestPath, FALSE, TRUE);
        @preg_match('/chart\.setData\((.*?)chart\.render/si', $response, $match);

        return (FALSE !== $response AND isset($match[1])) ?
            substr(trim($match[1]), 0, -2) : FALSE;
    }

    private function GWT_Curl ($requestPath, $postData=FALSE, $authHeader=FALSE)
    {
        $curlUrl = 'https://www.google.com/' . $requestPath;
        $ch = curl_init($curlUrl);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        if (FALSE !== $postData) :
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        endif;

        if (TRUE === $authHeader) :
            $httpHeader = Array(
                'Authorization: GoogleLogin auth=' . $this->_auth,
                'GData-Version: 2' );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        endif;

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return 200 < $info['http_code'] ? FALSE : $response;
    }
}//eof
