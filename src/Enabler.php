<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 03.04.18
 * Time: 14:18
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache;

use Alpipego\AWP\Cache\Exceptions\InvalidOptionTypeException;
use Alpipego\AWP\Cache\Exceptions\NotCacheableRequestException;
use voku\helper\HtmlMin;

class Enabler
{
    private $cloudflare = false;
    private $cache = true;
    private $loggedin = false;
    private $msg = '';
    private $url;
    private $path = '';
    private $doc = '';

    public function __construct(array $options = [])
    {
        foreach ($options as $option => $value) {
            if (gettype($value) !== gettype($this->$option)) {
                throw new InvalidOptionTypeException($option, gettype($this->$option), gettype($value));
            }

            if (property_exists($this, $option)) {
                $this->$option = $value;
            }
        }

        $this->setUrl();
    }

    private function setUrl()
    {
        $path       = '/' . trim($_SERVER['REQUEST_URI'], '/');
        $this->url  = preg_replace_callback('/^(.+?)([?&])purge(?:=[^\/&]+)?&?(.*?)$/', function (array $matches) {
            $str = $matches[1] . (empty($matches[3]) ? '' : $matches[2]) . $matches[3];
            if (substr($str, -1) !== '/') {
                $str .= '/';
            }

            return $str;
        }, $path);
        $path       = array_filter(explode('/', $this->url));
        $this->doc  = array_pop($path) ?: '_';
        $path       = implode('_', $path);
        $path       = preg_replace('/[{}()\/\\@:]/', '_', $path);
        $this->path = $path . (substr($path, -1) === '_' ? '' : '_');
    }

    public function cacheable(): bool
    {
        if (!$this->decide()) {
            throw new NotCacheableRequestException(sprintf('Request cannot be cached: %s', $this->msg));
        }

        return $this->cache;
    }

    private function decide(): bool
    {
        // don't cache if cloudflare is enabled and request from cloudflare
        if ($this->cloudflare && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $this->cache = false;
            $this->addMessage('request from cloudflare ');
        }
        // don't cache if user is logged in
        if (strpos('test ' . implode(' ', array_keys($_COOKIE)), 'wordpress_logged_in')) {
            $this->cache    = false;
            $this->loggedin = true;
            $this->addMessage('loggedin user ');
        }
        // don't cache post requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->cache = false;
            $this->addMessage('post request ');
        }
        // don't cache requests to wordpress php files
        if (preg_match('%(/wp-admin|/xmlrpc.php|/wp-(app|cron|login|register|mail).php|wp-.*.php|/feed/|index.php|wp-comments-popup.php|wp-links-opml.php|wp-locations.php|sitemap(_index)?.xml|[a-z0-9_-]+-sitemap([0-9]+)?.xml)%',
            $this->path)) {
            $this->cache = false;
            $this->addMessage('wordpress file ');
        }

        // don't cache if url has a query string
        // TODO verify that this is reasonable
        if (parse_url($this->url, PHP_URL_QUERY)) {
            $this->cache = false;
            $this->addMessage('query string ');
        }

        return $this->cache;
    }

    public function addMessage(string $msg): self
    {
        $this->msg .= $msg . "\n";

        return $this;
    }

    public function inBlacklist(): bool
    {
        $blacklist = array_map(function ($value) {
            $req  = parse_url($value);
            $path = trim($req['path'], '/');

            return '/' . $path . '/';
        }, apply_filters('alpipego/awp/cache/blacklist', []));

        return (is_404() || is_search() || is_feed() || in_array($this->url, $blacklist));
    }

    public function getMessage(): string
    {
        return $this->msg;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDoc(): string
    {
        return $this->doc;
    }

    public function time(float $start, float $end): float
    {
        return round(($end - $start), 5);
    }

    public function isPurge(): bool
    {
        if (defined('CACHE_DEBUG') && CACHE_DEBUG) {
            return isset($_GET['purge']);
        }

        return $this->loggedin && isset($_GET['purge']);
    }

    public function buffer(string $wpBlogHeader): string
    {
        ob_start();

        if (!defined('CACHE_DEBUG') || !CACHE_DEBUG) {
            define('WP_DEBUG_DISPLAY', false);
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        require_once $wpBlogHeader;

        return (new HtmlMin())->minify(ob_get_clean());
    }
}
