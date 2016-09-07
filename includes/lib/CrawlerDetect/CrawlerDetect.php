<?php

/*
 * This file is part of Crawler Detect - the web crawler detection library.
 *
 * (c) Mark Beech <m@rkbee.ch>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Code has been modified to be backwards compatible with PHP versions prior to 5.3
 */

require_once( 'Fixtures/AbstractProvider.php' );
require_once( 'Fixtures/Crawlers.php' );
require_once( 'Fixtures/Exclusions.php' );
require_once( 'Fixtures/Headers.php' );

class CrawlerDetect
{
    /**
     * The user agent.
     *
     * @var null
     */
    protected $userAgent = null;

    /**
     * Headers that contain a user agent.
     *
     * @var array
     */
    protected $httpHeaders = array();

    /**
     * Store regex matches.
     *
     * @var array
     */
    protected $matches = array();

    /**
     * Crawlers object.
     *
     * @var \Jaybizzle\CrawlerDetect\Fixtures\Crawlers
     */
    protected $crawlers;

    /**
     * Exclusions object.
     *
     * @var \Jaybizzle\CrawlerDetect\Fixtures\Exclusions
     */
    protected $exclusions;

    /**
     * Headers object.
     *
     * @var \Jaybizzle\CrawlerDetect\Fixtures\Headers
     */
    protected $uaHttpHeaders;

    /**
     * Class constructor.
     */
    public function __construct(array $headers = null, $userAgent = null)
    {
        $this->crawlers = new Crawlers();
        $this->exclusions = new Exclusions();
        $this->uaHttpHeaders = new Headers();

        $this->setHttpHeaders($headers);
        $this->setUserAgent($userAgent);
    }

    /**
     * Set HTTP headers.
     *
     * @param array $httpHeaders
     */
    public function setHttpHeaders($httpHeaders = null)
    {
        // use global _SERVER if $httpHeaders aren't defined
        if (!is_array($httpHeaders) || !count($httpHeaders)) {
            $httpHeaders = $_SERVER;
        }
        // clear existing headers
        $this->httpHeaders = array();
        // Only save HTTP headers. In PHP land, that means only _SERVER vars that
        // start with HTTP_.
        foreach ($httpHeaders as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $this->httpHeaders[$key] = $value;
            }
        }
    }

    /**
     * Return user agent headers.
     *
     * @return array
     */
    public function getUaHttpHeaders()
    {
        return $this->uaHttpHeaders->getAll();
    }

    /**
     * Set the user agent.
     *
     * @param string $userAgent
     */
    public function setUserAgent($userAgent = null)
    {
        if (false === empty($userAgent)) {
            return $this->userAgent = $userAgent;
        } else {
            $this->userAgent = null;
            foreach ($this->getUaHttpHeaders() as $altHeader) {
                if (false === empty($this->httpHeaders[$altHeader])) { // @todo: should use getHttpHeader(), but it would be slow.
                    $this->userAgent .= $this->httpHeaders[$altHeader].' ';
                }
            }

            return $this->userAgent = (!empty($this->userAgent) ? trim($this->userAgent) : null);
        }
    }

    /**
     * Build the user agent regex.
     *
     * @return string
     */
    public function getRegex()
    {
        return '('.implode('|', $this->crawlers->getAll()).')';
    }

    /**
     * Build the replacement regex.
     *
     * @return string
     */
    public function getExclusions()
    {
        return '('.implode('|', $this->exclusions->getAll()).')';
    }

    /**
     * Check user agent string against the regex.
     *
     * @param string $userAgent
     *
     * @return bool
     */
    public function isCrawler($userAgent = null)
    {
        $agent = is_null($userAgent) ? $this->userAgent : $userAgent;

        $agent = preg_replace('/'.$this->getExclusions().'/i', '', $agent);

        if (strlen(trim($agent)) == 0) {
            return false;
        } else {
            $result = preg_match('/'.$this->getRegex().'/i', trim($agent), $matches);
        }

        if ($matches) {
            $this->matches = $matches;
        }

        return (bool) $result;
    }

    /**
     * Return the matches.
     *
     * @return string
     */
    public function getMatches()
    {
        return isset($this->matches[0]) ? $this->matches[0] : null;
    }
}
