<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools\Config;

use Piwik\Ini\IniReader;
use Piwik\Ini\IniReadingException;
use Piwik\Ini\IniWriter;
use Symfony\Component\Console\Style\StyleInterface;

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

    public function isGlobalInstallation(StyleInterface $io): bool
    {
        return $this->decorated->isGlobalInstallation($io);
    }

    public function getHomeDirectory(StyleInterface $io): string
    {
        return $this->decorated->getHomeDirectory($io);
    }

    public function isForcingAnsi(StyleInterface $io): bool
    {
        if (! isset($this->config['global']['ansi'])) {
            $this->config['global']['ansi'] = $this->decorated->isForcingAnsi($io);
            $this->store();
        }
        return (bool) $this->config['global']['ansi'];
    }

    public function getBinDirectory(StyleInterface $io): string
    {
        if (! isset($this->config['global']['bin_directory'])) {
            $this->config['global']['bin_directory'] = $this->decorated->getBinDirectory($io);
            $this->store();
        }
        return rtrim($this->config['global']['bin_directory'], '/');
    }

    public function getBashCompletionPath(StyleInterface $io): string
    {
        if (! isset($this->config['global']['bash_completion_path'])) {
            $this->config['global']['bash_completion_path'] = $this->decorated->getBashCompletionPath($io);
            $this->store();
        }
        return rtrim($this->config['global']['bash_completion_path'], '/');
    }

    public function getBashConfigurationPath(StyleInterface $io): string
    {
        if (! isset($this->config['global']['bash_configuration_path'])) {
            $this->config['global']['bash_configuration_path'] = $this->decorated->getBashConfigurationPath($io);
            $this->store();
        }
        return rtrim($this->config['global']['bash_configuration_path'], '/');
    }

    public function getFontDirectory(StyleInterface $io): string
    {
        if (! isset($this->config['global']['font_directory'])) {
            $this->config['global']['font_directory'] = $this->decorated->getFontDirectory($io);
            $this->store();
        }
        return rtrim($this->config['global']['font_directory'], '/');
    }
}
