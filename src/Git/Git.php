<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools\Git;

use GitWrapper\GitCommand;
use GitWrapper\GitException;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;

class Git
{
    /** @var GitWrapper */
    private $git;

    public function __construct(string $gitBinary)
    {
        $this->git = new GitWrapper($gitBinary);
    }

    public function cloneTemporary(string $url): GitRepository
    {
        return $this->clonePermanently($url, sys_get_temp_dir() . '/' . uniqid('php_cli_tools_', true));
    }

    public function clonePermanently(string $url, string $targetDirectory): GitRepository
    {
        $workingCopy = $this->git->cloneRepository($url, $targetDirectory);
        return new GitRepository($targetDirectory, $workingCopy);
    }

    public function isCloned(string $directory): bool
    {
        return (new GitWorkingCopy($this->git, $directory))->isCloned();
    }

    public function open(string $directory): GitRepository
    {
        if (! $this->isCloned($directory)) {
            throw new GitException("$directory is no cloned git repository");
        }
        return new GitRepository($directory, new GitWorkingCopy($this->git, $directory));
    }

    /**
     * @param string $config
     * @return string|null
     */
    public function getGlobalConfig(string $config)
    {
        try {
            return trim($this->git->run(GitCommand::getInstance('config', '--global', $config)));
        } catch (GitException $exception) {
            return null; // Property not set
        }
    }

    public function setGlobalConfig(string $config, string $value)
    {
        $this->git->run(GitCommand::getInstance('config', '--global', $config, $value));
    }
}
