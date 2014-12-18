<?php
/**
 * Remote.php
 *
 * PHP Version 5
 *
 * @category davidverholen_git-tools
 * @package  davidverholen_git-tools
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */

namespace DavidVerholen\Git;

/**
 * Class Remote
 *
 * @category davidverholen_git-tools
 * @package  DavidVerholen\Git
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */
class Remote
{
    const DEFAULT_NAME = 'origin';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var int
     */
    protected $port = 80;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var string
     */
    protected $scheme = '';

    private function __construct()
    {

    }

    /**
     * fromUrl
     *
     * @param $name
     * @param $url
     *
     * @return Remote
     */
    public static function createFromUrl($url, $name = self::DEFAULT_NAME)
    {
        return self::createFromUrlParts(parse_url($url), $name);
    }

    /**
     * createFromUrlParts
     *
     * @param array  $urlParts
     * @param string $name
     *
     * @return Remote
     */
    public static function createFromUrlParts(
        array $urlParts,
        $name = self::DEFAULT_NAME
    ) {
        $instance = self::create($name);

        $instance->setScheme($instance->getIfIsset($urlParts, 'scheme'));
        $instance->setUser($instance->getIfIsset($urlParts, 'user'));
        $instance->setPassword($instance->getIfIsset($urlParts, 'pass'));
        $instance->setHost($instance->getIfIsset($urlParts, 'host'));
        $instance->setPort($instance->getIfIsset($urlParts, 'port'));
        $instance->setPath($instance->getIfIsset($urlParts, 'path'));

        return $instance;
    }

    /**
     * fromName
     *
     * @param $name
     *
     * @return Remote
     */
    public static function create($name = self::DEFAULT_NAME)
    {
        $instance = new self();
        $instance->setName($name);

        return $instance;
    }

    /**
     * getIfIsset
     *
     * @param $array
     * @param $key
     *
     * @return string
     */
    protected function getIfIsset($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return http_build_url([
            'scheme' => $this->getScheme(),
            'host'   => $this->getHost(),
            'port'   => $this->getPort(),
            'user'   => $this->getUser(),
            'pass'   => $this->getPassword(),
            'path'   => $this->getPath()
        ]);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
