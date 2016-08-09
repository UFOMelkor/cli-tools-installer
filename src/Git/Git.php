<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools\Git;

use GitWrapper\GitCommand;
use GitWrapper\GitException;
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
        $this->git->cloneRepository($url, $targetDirectory);
        return new GitRepository($targetDirectory);
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
