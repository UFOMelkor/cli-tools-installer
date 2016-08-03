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

        try {
            $script = (string) (new Client())->get(self::SOURCE)->getBody();
        } catch (Exception $exception) {
            $io->error('Could not grab the completion file from ' . self::SOURCE);
            return 1;
        }

        $target = $this->config->getBashCompletionPath($io);
        $fileName = 'composer';
        if (! is_dir($target)) {
            $completionLoadingFilePath = $target;
            $target = dirname($target);
            $fileName = '.php_cli_tools_composer_bash_completion';
        }
        $target = "$target/$fileName";

        if (file_exists($target)) {
            $currentContent = file_get_contents($target);
            if ($currentContent !== $script) {
                if (! @file_put_contents($target, $script)) {
                    $io->error("Could not write to $target.");
                    return 1;
                }
                $io->text("Updated the bash completion in $target.");
            } else {
                $io->text("The latest version is already installed in $target.");
            }
        } else {
            if (! @file_put_contents($target, $script)) {
                $io->error("Could not write to $target.");
                return 1;
            }
            $io->text("Installed the bash completion in $target.");
        }

        if (isset($completionLoadingFilePath)
            && ! $this->createCompletionFile($io, $completionLoadingFilePath, $target)
        ) {
            return 1;
        }

        $io->success('Installed the latest version of iArren\'s composer bash completion.');
        return 0;
    }

    private function createCompletionFile(
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
