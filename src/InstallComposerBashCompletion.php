<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;

class InstallComposerBashCompletion extends Command
{
    const SOURCE = 'https://raw.githubusercontent.com/iArren/composer-bash-completion/master/composer';

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct('composer:completion:bash');
        $this->setDescription('Bash completion for Composer using https://github.com/iArren/composer-bash-completion');
        $this->setHelp(<<<'HELP'
Using composer from the command line is cool but looking into
https://packagist.org/ every time you forgot how a package is spelled is not.

iArren developed a bash completion that will complete both package name and
version.
HELP
        );
        $this->config = $config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Composer Bash Completion');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $target = $this->config->getBashCompletionPath($io);
        $fileName = 'composer';
        if (! is_dir($target)) {
            $completionLoadingFilePath = $target;
            $target = dirname($target);
            $fileName = '.php_cli_tools_composer_bash_completion';
        }
        $target = "$target/$fileName";

        if (! $this->createOrUpdateCompletionFile($io, $target)) {
            return 1;
        }

        if (isset($completionLoadingFilePath)
            && ! $this->createCompletionLoadingFile($io, $completionLoadingFilePath, $target)
        ) {
            return 1;
        }

        $io->success('Installed the latest version of iArren\'s composer bash completion.');
        $io->note('Remember to start a new console to activate this changes');
        return 0;
    }

    private function createOrUpdateCompletionFile(StyleInterface $io, string $filePath): bool
    {
        try {
            $script = (string) (new Client())->get(self::SOURCE)->getBody();
        } catch (Exception $exception) {
            $io->error('Could not grab the completion file from ' . self::SOURCE);
            return false;
        }

        $currentAliases = '';
        $aliasFile = $this->config->getHomeDirectory($io) . '/.alias';
        if (! $this->config->isGlobalInstallation($io)) {
            $currentAliases = file_exists($aliasFile) ? file_get_contents($aliasFile) : '';
            preg_match('/alias ([^=]*)=composer/', $currentAliases, $matches);
            if (count($matches) > 1) {
                $alias = $matches[1];
            }
        }

        $hasAnAlias = isset($alias) || $io->confirm('Do you have an alias for composer?', false);
        if (! $hasAnAlias
            && ! $this->config->isGlobalInstallation($io)
            && $io->confirm('Do you want to setup an alias?')
        ) {
            $alias = $io->ask('Which alias should be setup?', 'c');
            $currentAliases = trim($currentAliases . "\nalias $alias=composer");

            if (! file_put_contents($aliasFile, $currentAliases)) {
                $io->error("Could not write to $aliasFile");
                return false;
            }
            $hasAnAlias = true;
        }
        if ($hasAnAlias) {
            if (! isset($alias)) {
                $alias = $io->ask('What is your composer alias?');
            }
            $script = trim($script) . "\ncomplete -F _composer $alias\n";
        } else {
            $io->askHidden('Why not?');
        }

        if (file_exists($filePath)) {
            $currentContent = file_get_contents($filePath);
            if ($currentContent !== $script) {
                if (! @file_put_contents($filePath, $script)) {
                    $io->error("Could not write to $filePath.");
                    return false;
                }
                $io->text("Updated the bash completion in $filePath.");
            } else {
                $io->text("The latest version is already installed in $filePath.");
            }
        } else {
            if (! @file_put_contents($filePath, $script)) {
                $io->error("Could not write to $filePath.");
                return false;
            }
            $io->text("Installed the bash completion in $filePath.");
        }
        return true;
    }

    private function createCompletionLoadingFile(
        StyleInterface $io,
        string $completionLoadingFilePath,
        string $completionFilePath
    ): bool {
        $currentLoading = file_exists($completionLoadingFilePath) ? file_get_contents($completionLoadingFilePath) : '';
        if (strpos($currentLoading, "source $completionFilePath") !== false) {
            return true;
        }
        if (@file_put_contents($completionLoadingFilePath, "source $completionFilePath\n$currentLoading") === false) {
            $io->error("Could not write to $completionLoadingFilePath.");
            return false;
        }
        $io->text("$completionFilePath will be loaded by $completionLoadingFilePath.");
        return true;
    }
}
