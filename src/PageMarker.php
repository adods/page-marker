<?php

namespace Adods\PageMarker;

class PageMarker
{
    /**
     * Replace current base data with the parameter given
     */
    const OVERRIDE_REPLACE = 0;

    /**
     * Add the given parameter array to the base data
     */
    const OVERRIDE_APPEND = 1;

    /**
     * Marker Identifier/Name
     *
     * @var string
     */
    private $name;

    /**
     * Base URL for redirection
     *
     * @var string
     */
    private $url;

    /**
     * Base array to remember
     *
     * @var array
     */
    private $base;

    /**
     * Base session key name
     *
     * @var string
     */
    protected $sessionKey = 'PageMarker';

    /**
     * Key to check in the base data for forgetting the data
     *
     * @var string
     */
    protected $forgetKey = '__pagemarker_reset';

    /**
     * Check if it's already initialized
     *
     * @var boolean
     */
    private $ready = false;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Get marker Identifier/Name
     *
     * @return  string
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set marker Identifier/Name
     *
     * @param  string  $name  Marker Identifier/Name
     *
     * @return  self
     */ 
    public function setName(String $name)
    {
        $this->name = $this->cleanName($name);

        return $this;
    }

    /**
     * Automatically set marker Identifier/Name from current URL Path
     *
     * @return void
     */
    public function setNameFromUrl()
    {
        // Get Current URL Path
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = strtolower(trim($path, '/'));

        // Remove Extension
        $pathinfo = pathinfo($path);
        $pathNoExt = $pathinfo['dirname'].'/'.$pathinfo['filename'];

        $this->setName($pathNoExt);
    }

    private function cleanName($name)
    {
        // Replace double space with single one
        $name = str_replace('  ', ' ', $name);
        // Replace other character with _
        $name = str_replace(['-', '/', '\\', '.', ' '], '_', $name);
        
        return $name;
    }

    /**
     * Get base URL for redirection
     *
     * @return  string
     */ 
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set base URL for redirection
     *
     * @param  string  $url  Base URL for redirection
     *
     * @return  self
     */ 
    public function setUrl(string $url)
    {
        $this->url = strtok($url, '?');

        return $this;
    }

    /**
     * Automatically set URL from Current $_SERVER Global Variable
     *
     * @return void
     */
    public function autoSetUrl()
    {
        $this->setUrl($this->getCurrentUrl());
    }

    /**
     * Initialize Defaults and Ready for redirection
     *
     * @return void
     */
    public function init($bypassRedirection = false)
    {
        if (empty($this->name)) {
            $this->setNameFromUrl();
        }
        
        if (empty($this->url)) {
            $this->autoSetUrl();
        }

        if (is_null($this->base)) {
            $this->setBase($_GET);
        }

        if (!$bypassRedirection) {
            $this->prepareRedirection();
        }

        $this->ready = true;
    }

    /**
     * Check and Redirect accordingly
     *
     * @return void
     */
    private function prepareRedirection()
    {
        // If forget key detected, then reset
        if (isset($this->base[$this->forgetKey])) {
            $this->forget();
            $this->redirect($this->url);
        }

        // If there's data in the base then nothing happened
        if (count($this->base)) {
            return;
        }

        $session = $this->retrieveSession();

        // If there's no data from session, also nothing happened
        if (empty($session)) {
            return;
        }

        $query = http_build_query($session);
        $url = $this->url.'?'.$query;

        $this->redirect($url);
    }

    /**
     * Redirect to $url
     *
     * @param string $url URL to be redirected to
     * @return void
     */
    private function redirect($url)
    {
        header('HTTP/1.1 307 Temporary Redirect');
        header('Location: '.$url);
        exit(0);
    }

    /**
     * Build Current URL String from $_SERVER Global Variable
     *
     * @return string
     */
    private function getCurrentUrl()
    {
        $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        $lsp = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($lsp, 0, strpos($lsp, '/')) . (($ssl) ? 's' : '');
        $port = $_SERVER['SERVER_PORT'];
        $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':'.$port;
        $host = $_SERVER['SERVER_NAME'].$port;

        return $protocol.'://'.$host.$_SERVER['REQUEST_URI'];
    }

    /**
     * Get base array to remember
     *
     * @return  array
     */ 
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set base array to remember
     *
     * @param  array  $base  Base array to remember
     *
     * @return  self
     */ 
    public function setBase(Array $base)
    {
        $this->base = $base;

        return $this;
    }

    /**
     * Add new or set data to remember
     *
     * @param string|array $key Keyname or Associative array of key and value pair
     * @param mixed $value Value to set if $key is string
     * @return self
     */
    public function add($key, $value = null)
    {
        if (is_string($key)) {
            $this->base[$key] = $value;
        } else if (is_array($key)) {
            array_merge($this->base, $key);
        }

        return $this;
    }

    /**
     * Remove data from base array
     *
     * @param string|array $key String of key or Array of Keys
     * @return self
     */
    public function except($key)
    {
        if (is_string($key) && isset($this->base[$key])) {
            unset($this->base[$key]);
        } else if (is_array($key)) {
            foreach ($key as $k) {
                $this->except($k);            }
        }

        return $this;
    }

    /**
     * Get the reset URL
     *
     * @return string
     */
    public function getResetUrl()
    {
        return $this->url.'?'.$this->forgetKey.'=1';
    }

    /**
     * Remember the base data
     *
     * @param array $vars New set of data to remember, add or replace the current one
     * @param integer $mode
     * @return void
     */
    public function remember(array $vars = [], $mode = 0)
    {
        // Initialize if not ready
        if (!$this->ready) {
            $this->init(false);
        }

        // Check if current data is going to be overrided
        if (!empty($vars)) {
            if ($mode == self::OVERRIDE_APPEND) {
                $this->add($vars);
            } else {
                $this->setBase($vars);
            }
        }

        $this->registerSession();
    }

    /**
     * Forget the data
     *
     * @return void
     */
    public function forget()
    {
        unset($_SESSION[$this->sessionName()]);
    }

    /**
     * Compile the session key
     *
     * @return void
     */
    private function sessionName()
    {
        return $this->sessionKey.'.'.$this->name;
    }

    /**
     * Register the data to session
     *
     * @return void
     */
    private function registerSession()
    {
        $_SESSION[$this->sessionName()] = $this->base;
    }

    /**
     * Retrieve the base data from session
     *
     * @return void
     */
    private function retrieveSession()
    {
        return $_SESSION[$this->sessionName()];
    }
}
