<?php
/**
 * RepositoryTest.php
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

use DavidVerholen\Git\Remote;
use DavidVerholen\Git\Repository;

/**
 * Class RepositoryTest
 *
 * @category davidverholen_git-tools
 * @package  ${NAMESPACE}
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */
class RepositoryTest extends AbstractTest
{
    /**
     * @var Repository
     */
    protected $object;

    protected function setUp()
    {
        parent::setUp();
        $this->object = new Repository($this->fsUrl(['repository']));
    }

    public function testInit()
    {
        $this->getObject()->init();
        $this->assertFileExists($this->fsUrl(['repository', '.git', 'config']));
    }

    public function testInitTwoTimes()
    {
        $this->assertTrue($this->getObject()->init());
        $this->getObject()->addRemote(Remote::createFromUrl('https://github.com/symfony/symfony.git'));
        $this->assertTrue($this->getObject()->init());
        $this->assertFileExists($this->fsUrl(['repository', '.git', 'config']));
        $this->assertCount(1, $this->getObject()->getRemotes());
    }

    public function testIsGitRepository()
    {
        $this->getObject()->init();
        $this->assertTrue($this->getObject()->isGitRepository());
    }

    public function testIsNotGitRepository()
    {
        $this->assertFalse($this->getObject()->isGitRepository());
    }

    public function testAddRemote()
    {
        $this->getObject()->init();
        $this->getObject()->setRemote(Remote::createFromUrl('https://github.com/symfony/symfony.git'));
        $this->assertCount(1, $this->getObject()->getRemotes());
    }

    public function testGetRemotes()
    {
        $remote = Remote::createFromUrl('https://github.com/symfony/symfony.git', 'origin');
        $this->getObject()->init();
        $this->getObject()->addRemote($remote);
        $remotes = $this->getObject()->getRemotes();
        $this->assertEquals($remote, $remotes['origin']);
    }

    public function testRepositoryNotFound()
    {
        $this->getObject()->init();
        $this->assertNull($this->getObject()->findRemoteByUrl('https://github.com/symfony/symfony.git'));
    }

    public function testChangeRemoteUrl()
    {
        $originalUrl = 'https://github.com/symfony/symfony.git';
        $updatedUrl = 'https://github.com/symfony/filesystem.git';
        $this->getObject()->init();
        $this->getObject()->addRemote(Remote::createFromUrl($originalUrl));
        $this->getObject()->changeRemoteUrl($originalUrl, $updatedUrl);
        $this->assertEquals(
            Remote::createFromUrl($updatedUrl)->getUrl(),
            $this->getObject()->getRemote()->getUrl()
        );
    }

    public function testChangeNonExistantRemoteUrl()
    {
        $originalUrl = 'https://github.com/symfony/symfony.git';
        $updatedUrl = 'https://github.com/symfony/filesystem.git';
        $this->getObject()->init();
        $this->assertFalse($this->getObject()->changeRemoteUrl($originalUrl, $updatedUrl));
    }

    public function testSetRemoteUrl()
    {
        $originalUrl = 'https://github.com/symfony/symfony.git';
        $updatedUrl = 'https://github.com/symfony/filesystem.git';
        $this->getObject()->init();
        $this->getObject()->addRemote(Remote::createFromUrl($originalUrl));
        $this->getObject()->setRemoteUrl($updatedUrl);
        $this->assertEquals(
            Remote::createFromUrl($updatedUrl)->getUrl(),
            $this->getObject()->getRemote()->getUrl()
        );
    }

    public function testRenameRemote()
    {
        $newName = 'remote';
        $this->getObject()->init();
        $this->getObject()->setRemote(Remote::createFromUrl('https://github.com/symfony/symfony.git'));
        $this->getObject()->renameRemote('origin', $newName);
        $this->assertNull($this->getObject()->getRemote());
        $this->assertNotNull($this->getObject()->getRemote('remote'));
    }

    public function testSetRemoteCredentials()
    {
        $remote = Remote::createFromUrl('https://github.com/symfony/symfony.git');
        $user = 'user';
        $password = 'password';
        $this->getObject()->init();
        $this->getObject()->addRemote(Remote::createFromUrl('https://github.com/symfony/symfony.git'));
        $this->getObject()->setRemoteCredentials($user, $password);
        $remote->setUser($user);
        $remote->setPassword($password);
        $this->assertEquals($remote, $this->getObject()->getRemote());
    }

    /**
     * getObject
     *
     * @return Repository
     */
    protected function getObject()
    {
        return $this->object;
    }
}
