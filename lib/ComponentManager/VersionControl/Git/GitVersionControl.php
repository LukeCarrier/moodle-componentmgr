<?php

/**
 * Moodle component manager.
 *
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2016 Luke Carrier
 * @license GPL-3.0+
 */

namespace ComponentManager\VersionControl\Git;

use ComponentManager\Exception\VersionControlException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Git version control.
 */
class GitVersionControl {
    /**
     * Recurse into submodules: no.
     *
     * @var integer
     */
    const RECURSE_NO = 0;

    /**
     * Recurse into submodules: yes.
     *
     * @var integer
     */
    const RECURSE_YES = 1;

    /**
     * Recurse into submodules: on-demand.
     *
     * @var integer
     */
    const RECURSE_ON_DEMAND = 2;

    /**
     * The repository's on-disk location.
     *
     * @var string
     */
    protected $directory;

    /**
     * Git executable.
     *
     * @var string
     */
    protected $gitExecutable;

    /**
     * Remotes.
     *
     * @var \ComponentManager\VersionControl\Git\GitRemote[]
     */
    protected $remotes;

    /**
     * Initialiser.
     *
     * @param string $gitExecutable
     * @param string $directory
     */
    public function __construct($gitExecutable, $directory) {
        $this->gitExecutable = $gitExecutable;

        $this->directory = $directory;
        $this->remotes   = [];
    }

    /**
     * Add the specified remote to the repository.
     *
     * @param \ComponentManager\VersionControl\Git\GitRemote $remote
     */
    public function addRemote(GitRemote $remote) {
        $name = $remote->getName();
        $uri  = $remote->getUri();

        $this->remotes[$name] = $remote;

        $process = $this->getProcess(['remote', 'add', $name, $uri]);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_REMOTE_ADD_FAILED);
    }

    /**
     * Checkout the specified reference.
     *
     * @param string $ref
     *
     * @return void
     */
    public function checkout($ref) {
        $process = $this->getProcess(['checkout', $ref]);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_CHECKOUT_FAILED);
    }

    /**
     * Checkout all files in the index to the specified directory.
     *
     * @param string $prefix
     *
     * @throws \ComponentManager\Exception\PlatformException
     */
    public function checkoutIndex($prefix) {
        $process = $this->getProcess(
                ['checkout-index', '--all', "--prefix={$prefix}"]);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_CHECKOUT_INDEX_FAILED);
    }

    /**
     * Fetch references from the specified remote.
     *
     * @param string  $remote
     * @param integer $recurseSubmodules
     * @param boolean $withTags
     *
     * @return void
     */
    public function fetch($remote, $recurseSubmodules=null, $withTags=null) {
        if ($recurseSubmodules === null) {
            $recurseSubmodules = static::RECURSE_NO;
        }
        if ($withTags === null) {
            $withTags = true;
        }

        $process = $this->getProcess([
            'fetch',
            $remote,
            $this->getRecurseSubmodulesParameter($recurseSubmodules),
        ]);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_FETCH_FAILED);

        if ($withTags) {
            $process = $this->getProcess(['fetch', '--tags', $remote]);
            $process->run();

            $this->ensureSuccess(
                    $process, VersionControlException::CODE_FETCH_FAILED);
        }
    }

    /**
     * Update submodules.
     *
     * @param boolean $withInit Initialise from .gitmodules (--init).
     *
     * @return void
     *
     * @throws \ComponentManager\Exception\VersionControlException
     */
    public function submoduleUpdate($withInit=null) {
        if ($withInit === null) {
            $withInit = false;
        }

        $arguments = [
            'submodule',
            'update',
        ];
        if ($withInit) {
            $arguments[] = '--init';
        }

        $process = $this->getProcess($arguments);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_FETCH_FAILED);
    }

    /**
     * Ensure the specified command executed successfully.
     *
     * @param \Symfony\Component\Process\Process $process
     * @param integer                            $code
     *
     * @throws \ComponentManager\Exception\VersionControlException
     */
    protected function ensureSuccess(Process $process, $code) {
        if (!$process->isSuccessful()) {
            throw new VersionControlException(
                    $process->getCommandLine(), $code);
        }
    }

    /**
     * Get a ready-to-run Process instance.
     *
     * @param  mixed[] $arguments Arguments to pass to the Git binary.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess($arguments) {
        array_unshift($arguments, $this->gitExecutable);

        $builder = new ProcessBuilder($arguments);
        $builder->setWorkingDirectory($this->directory);

        return $builder->getProcess();
    }

    /**
     * Initialise Git repository.
     *
     * @throws \ComponentManager\Exception\VersionControlException
     * @throws \ComponentManager\Exception\PlatformException
     */
    public function init() {
        $process = $this->getProcess(['init']);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_INIT_FAILED);
    }

    /**
     * Get the commit hash for the specified ref.
     *
     * @param string $ref
     *
     * @return string
     *
     * @throws \ComponentManager\Exception\VersionControlException
     */
    public function parseRevision($ref) {
        $process = $this->getProcess(['rev-parse', $ref]);
        $process->run();

        $this->ensureSuccess(
                $process, VersionControlException::CODE_REV_PARSE_FAILED);

        return trim($process->getOutput());
    }

    /**
     * Get recurse parameter.
     *
     * @param integer $recurse One of the submodule recursion values declared
     *                         in the static::RECURSE_* constants.
     *
     * @return string
     *
     * @throws \ComponentManager\Exception\VersionControlException
     */
    protected function getRecurseSubmodulesParameter($recurse) {
        switch ($recurse) {
            case static::RECURSE_NO:
                $result = 'no';
                break;

            case static::RECURSE_YES:
                $result = 'yes';
                break;

            case static::RECURSE_ON_DEMAND:
                $result = 'on-demand';
                break;

            default:
                throw new VersionControlException(sprintf(
                        'Invalid $recurse value %s', $recurse),
                        VersionControlException::CODE_INIT_FAILED);
        }

        return sprintf('--recurse-submodules=%s', $result);
    }
}
