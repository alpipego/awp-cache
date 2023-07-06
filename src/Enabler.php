<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Cache;

use Alpipego\AWP\Cache\Exceptions\InvalidOptionTypeException;
use Alpipego\AWP\Cache\Exceptions\NotCacheableRequestException;
use voku\helper\HtmlMin;

class Enabler
{
    private bool $cloudflare = false;
    private bool $cache      = true;
    private bool $loggedin   = false;
    /**
     * @var string[]
     */
    private array  $msg  = [];
    private string $url;
    private string $path = '';
    private string $doc  = '';

    public function __construct(array $options = [])
    {
        $this->addMessage('Starting Cache-Enabler');
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
        $path      = '/' . trim($_SERVER['REQUEST_URI'], '/');
        $this->url = preg_replace_callback('/^(.+?)([?&])purge(?:=[^\/&]+)?&?(.*?)$/', static function (array $matches) {
            $str = $matches[1] . (empty($matches[3]) ? '' : $matches[2]) . $matches[3];
            if (!str_ends_with($str, '/')) {
                $str .= '/';
            }

            return $str;
        }, $path);

        $path       = array_filter(explode('/', $this->url));
        $doc        = array_pop($path) ?: '_';
        $path       = preg_replace('/[{}()\/\\@:]/', '_', implode('_', $path));
        $this->path = $path . (str_ends_with($path, '_') ? '' : '_');
        $this->doc  = ($path === '' ? '' : $this->path) . $doc;
    }

    public function cacheable(): bool
    {
        if (!$this->decide()) {
            throw new NotCacheableRequestException(sprintf('Request cannot be cached: %s', $this->getMessage()));
        }

        return $this->cache;
    }

    private function decide(): bool
    {
        // don't cache if cloudflare is enabled and request from cloudflare
        if ($this->cloudflare && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $this->cache = false;
            $this->addMessage('request from cloudflare');
        }
        // don't cache if user is logged in
        if (strpos('test ' . implode(' ', array_keys($_COOKIE)), 'wordpress_logged_in')) {
            $this->cache    = false;
            $this->loggedin = true;
            $this->addMessage('Loggedin user');
        }
        // don't cache post requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->cache = false;
            $this->addMessage('Post request');
        }
        // don't cache requests to wordpress php files
        if (preg_match('%(/wp-admin|/xmlrpc.php|/wp-(app|cron|login|register|mail).php|wp-.*.php|/feed/|index.php|wp-comments-popup.php|wp-links-opml.php|wp-locations.php|sitemap(_index)?.xml|[a-z0-9_-]+-sitemap([0-9]+)?.xml)%',
            $this->path)) {
            $this->cache = false;
            $this->addMessage('This is a WordPress file ');
        }

        // don't cache if url has a query string
        // TODO verify that this is reasonable
        if (parse_url($this->url, PHP_URL_QUERY)) {
            $this->cache = false;
            $this->addMessage('Not caching query strings');
        }

        return $this->cache;
    }

    public function addMessage(string $msg): self
    {
        $this->msg[] = $msg;

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
        return implode('&rarr;', $this->msg);
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
            if (!defined('WP_DEBUG_DISPLAY')) {
                define('WP_DEBUG_DISPLAY', false);
            }
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        require_once $wpBlogHeader;

        return (new HtmlMin())->minify(ob_get_clean());
    }
}
