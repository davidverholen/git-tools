<?php
/**
 * Repository.php
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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class Repository
 *
 * @category davidverholen_git-tools
 * @package  DavidVerholen\Git
 * @author   David Verholen <david@verholen.com>
 * @license  http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link     http://github.com/davidverholen
 */
class Repository
{
    const GIT_EXECUTABLE = 'git';
    const GIT_DIRECTORY = '.git';
    const GIT_CONFIG = 'config';

    /**
     * @var ProcessBuilder
     */
    protected $processBuilder;

    /**
     * @var string
     */
    protected $repositoryLocation;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param $repositoryLocation
     */
    public function __construct($repositoryLocation)
    {
        $this->repositoryLocation = $repositoryLocation;
    }

    /**
     * isGitRepository
     *
     * @return bool
     */
    public function isGitRepository()
    {
        if (!$this->getFilesystem()->exists($this->getRepositoryLocation())) {
            return false;
        }

        $process = $this->runGit(['rev-parse', '--git-dir']);

        return $process->isSuccessful();
    }

    /**
     * addRemote
     *
     * @param Remote $remote
     *
     * @return bool
     */
    public function addRemote(Remote $remote)
    {
        return $this->runGit([
            'remote',
            'add',
            $remote->getName(),
            $remote->getUrl()
        ])->isSuccessful();
    }

    /**
     * setRemote
     *
     * @param Remote $changedRemote
     * @param        $name
     *
     * @return bool
     */
    public function setRemote(
        Remote $changedRemote,
        $name = Remote::DEFAULT_NAME
    ) {
        if (!$this->hasRemote($name)) {
            $this->addRemote($changedRemote);
            return true;
        }

        $newRemote = $this->mergeRemotes(
            $this->getRemote($name),
            $changedRemote
        );

        $success = true;

        if ($name !== $changedRemote->getName()) {
            $success &= $this->runGit([
                'remote',
                'rename',
                $name,
                $changedRemote->getName()
            ])->isSuccessful();
        }

        $success &= $this->runGit([
            'remote',
            'set-url',
            $newRemote->getName(),
            $newRemote->getUrl()
        ])->isSuccessful();

        return $success;
    }

    /**
     * mergeRemotes
     *
     * @param $original
     * @param $update
     *
     * @return mixed
     */
    public function mergeRemotes($original, $update)
    {
        $fieldsToUpdate = [
            'name',
            'scheme',
            'user',
            'password',
            'host',
            'port',
            'path'
        ];

        foreach ($fieldsToUpdate as $field) {
            $original = $this->updateIfChanged(
                $original,
                $update,
                'get' . ucfirst(strtolower($field)),
                'set' . ucfirst(strtolower($field))
            );
        }

        return $original;
    }

    /**
     * updateIfChanged
     *
     * @param $old
     * @param $new
     * @param $getter
     * @param $setter
     *
     * @return mixed
     */
    protected function updateIfChanged($old, $new, $getter, $setter)
    {
        $old->$setter(
            ($old->$getter() !== $new->$getter() && $new->$getter() !== null)
                ? $new->$getter()
                : $old->$getter()
        );

        return $old;
    }

    /**
     * setRemoteUrl
     *
     * @param $remoteName
     * @param $url
     *
     * @return bool
     */
    public function setRemoteUrl($url, $remoteName = Remote::DEFAULT_NAME)
    {
        return $this->setRemote(
            Remote::createFromUrl($url, $remoteName),
            $remoteName
        );
    }

    /**
     * changeRemoteUrl
     *
     * @param $originalUrl
     * @param $updatedUrl
     *
     * @return bool
     */
    public function changeRemoteUrl($originalUrl, $updatedUrl)
    {
        if (null === ($remote = $this->findRemoteByUrl($originalUrl))) {
            return false;
        }

        return $this->setRemote(
            Remote::createFromUrl($updatedUrl, $remote->getName()),
            $remote->getName()
        );
    }

    /**
     * renameRemote
     *
     * @param $currentName
     * @param $newName
     *
     * @return bool
     */
    public function renameRemote($currentName, $newName)
    {
        return $this->setRemote(Remote::create($newName), $currentName);
    }

    /**
     * setRemoteCredentials
     *
     * @param        $user
     * @param        $password
     * @param string $remoteName
     *
     * @return bool
     */
    public function setRemoteCredentials(
        $user,
        $password,
        $remoteName = Remote::DEFAULT_NAME
    ) {
        $remote = $this->getRemote($remoteName);
        $remote->setUser($user);
        $remote->setPassword($password);

        return $this->setRemote($remote, $remoteName);
    }

    /**
     * getRemotes
     *
     * @return Remote[]
     */
    public function getRemotes()
    {
        $output = $this->runGit(['remote', '-v'])->getOutput();

        /** @var Remote[] $remotes */
        $remotes = [];
        foreach (explode("\n", $output) as $line) {
            $parts = preg_split("/(\\t|\\s)/", $line);
            if (array_key_exists(0, $parts) && array_key_exists(1, $parts)) {
                $remotes[$parts[0]] = Remote::createFromUrl(
                    $parts[1],
                    $parts[0]
                );
            }
        }

        return $remotes;
    }

    /**
     * getRemote
     *
     * @param $name
     *
     * @return Remote|null
     */
    public function getRemote($name = Remote::DEFAULT_NAME)
    {
        if (!$this->hasRemote($name)) {
            return null;
        }

        return $this->getRemotes()[$name];
    }

    /**
     * findRemoteByUrl
     *
     * @param $url
     *
     * @return Remote|null
     */
    public function findRemoteByUrl($url)
    {
        $reference = Remote::createFromUrl($url);
        foreach ($this->getRemotes() as $remote) {
            $same
                = $reference->getHost() === $remote->getHost()
                && $reference->getPath() === $remote->getPath()
                && $reference->getScheme() === $remote->getScheme();

            if (true === $same) {
                return $remote;
            }
        }

        return null;
    }

    /**
     * hasRemote
     *
     * @param $name
     *
     * @return bool
     */
    public function hasRemote($name)
    {
        return isset($this->getRemotes()[$name]);
    }

    /**
     * init
     *
     * @return bool
     *
     * @throws RuntimeException       if sigchild compatibility mode is not enabled
     */
    public function init()
    {
        if (!$this->getFilesystem()->exists($this->getRepositoryLocation())) {
            $this->getFilesystem()->mkdir($this->getRepositoryLocation());
        }

        return $this->runGit(['init'])->isSuccessful();
    }

    /**
     * run
     *
     * @param       $prefix
     * @param array $arguments
     *
     * @return Process
     *
     * @throws RuntimeException       if sigchild compatibility mode is not enabled
     * @throws ProcessFailedException if the process didn't terminate successfully
     */
    protected function run($prefix, array $arguments)
    {
        return $this->getProcessBuilder()
            ->setPrefix($prefix)
            ->setArguments($arguments)
            ->setWorkingDirectory($this->getRepositoryLocation())
            ->getProcess()
            ->mustRun();
    }

    /**
     * getRepositoryLocation
     *
     * @return string
     */
    public function getRepositoryLocation()
    {
        return $this->repositoryLocation;
    }

    /**
     * runGit
     *
     * @param $arguments
     *
     * @return Process
     *
     * @throws RuntimeException       if sigchild compatibility mode is not enabled
     * @throws ProcessFailedException if the process didn't terminate successfully
     */
    protected function runGit($arguments)
    {
        return $this->run(self::GIT_EXECUTABLE, $arguments);
    }

    /**
     * getProcessBuilder
     *
     * @return ProcessBuilder
     */
    protected function getProcessBuilder()
    {
        if (null === $this->processBuilder) {
            $this->processBuilder = new ProcessBuilder();
        }

        return $this->processBuilder;
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
}
