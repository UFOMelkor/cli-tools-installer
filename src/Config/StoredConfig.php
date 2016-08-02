<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use Piwik\Ini\IniReader;
use Piwik\Ini\IniReadingException;
use Piwik\Ini\IniWriter;
use UFOMelkor\CliTools\Output;

class StoredConfig implements Config
{
    /** @var array */
    private $config;

    /** @var callable */
    private $writing;

    /** @var Config */
    private $decorated;

    public static function fromFile(string $filePath, string $user, string $homeDirectory)
    {
        $config = [];
        if (file_exists($filePath)) {
            try {
                $config = (new IniReader())->readFile($filePath);
            } catch (IniReadingException $exception) {
            }
        }
        $writing = function (array $config) use ($filePath) {
            (new IniWriter())->writeToFile($filePath, $config);
        };
        return new self($config, $writing, new InteractiveConfig($user, $homeDirectory));
    }

    public function __construct(array $config, callable $writing, Config $decorated)
    {
        $this->config = $config;
        $this->writing = $writing;
        $this->decorated = $decorated;
    }

    private function store()
    {
        call_user_func($this->writing, $this->config);
    }

    public function isForcingAnsi(Output $output): bool
    {
        if (! isset($this->config['global']['ansi'])) {
            $this->config['global']['ansi'] = $this->decorated->isForcingAnsi($output);
            $this->store();
        }
        return (bool) $this->config['global']['ansi'];
    }

    public function getBinDirectory(Output $output): string
    {
        if (! isset($this->config['global']['bin_directory'])) {
            $this->config['global']['bin_directory'] = $this->decorated->getBinDirectory($output);
            $this->store();
        }
        return rtrim($this->config['global']['bin_directory'], '/');
    }

    public function getBashCompletionDirectory(Output $output): string
    {
        if (! isset($this->config['global']['bash_completion_directory'])) {
            $this->config['global']['bash_completion_directory'] = $this->decorated
                ->getBashCompletionDirectory($output);
            $this->store();
        }
        return rtrim($this->config['global']['bash_completion_directory'], '/');
    }
}