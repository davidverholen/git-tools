<?php
/**
 * AbstractTest.php
 *
 * PHP Version 5
 *
 * @category davidverholen_git-tools
 * @package  davidverholen_git-tools
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */

namespace DavidVerholen\Test\Git;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AbstractTest
 *
 * @category davidverholen_git-tools
 * @package  DavidVerholen\Test\Git
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */
abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * fsUrl
     *
     * @param array $urlParts
     *
     * @return string
     */
    protected function fsUrl(array $urlParts)
    {
        array_map([$this, 'trimUrlPart'], $urlParts);
        array_unshift($urlParts, TEST_BUILD_DIR);
        return implode(
            DIRECTORY_SEPARATOR,
            $urlParts
        );
    }

    /**
     * getFilesystem
     *
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    /**
     * trimUrlPart
     *
     * @param $url
     *
     * @return string
     */
    public function trimUrlPart($url)
    {
        return trim(trim($url), '/\\');
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    protected function setUp()
    {
        parent::setUp();
        $this->getFilesystem()->remove(TEST_BUILD_DIR);
        $this->getFilesystem()->mkdir(TEST_BUILD_DIR);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->getFilesystem()->remove(TEST_BUILD_DIR);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }
}
