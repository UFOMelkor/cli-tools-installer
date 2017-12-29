<?php
declare(strict_types=1);

namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;
use UFOMelkor\CliTools\Installers\BashCompletionInstaller;

class InstallSymfonyBashCompletion extends Command
{
    /** @var Config */
    private $config;

    /** @var ExecutableFinder */
    private $executables;

    /** @var BashCompletionInstaller */
    private $completions;

    public function __construct(
        Config $config,
        ExecutableFinder $executables,
        BashCompletionInstaller $completionInstaller
    ) {
        parent::__construct('bash:completion:symfony-console');
        $this->setDescription(
            'Bash completion for tools based on Symfony Console using '
            . '<options=underscore>https://github.com/bamarni/symfony-console-autocomplete</>'
        );
        $this->setHelp(
            <<<HELP
Many command line tools in PHP (like Behat, php-cs-fixer, phpmetrics, PHPSpec
and every Symfony Application) are based on the awesome Symfony Console.
Therefore it is really useful to have autocompletion for the Symfony Console.

Fortunately Bilal Amarni developed a tool that provides a basic completion for
tools based on Symfony Console. If you want to know more have a look at
<options=underscore>https://github.com/bamarni/symfony-console-autocomplete</>

Although composer is also based on the Symfony Console, I do not recommend to
enable this tool for composer, because there is another tool especially
developed for composer that provides a better completion for composer.
You can install it using <options=underscore>composer:completion:bash</>
HELP
        );
        $this->config = $config;
        $this->executables = $executables;
        $this->completions = $completionInstaller;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Symfony Console autocomplete');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if ($this->config->isGlobalInstallation($io)) {
            $io->error(
                'Symfony console autocomplete does not support an installation as root user. '
                . 'Please try again with user permissions.'
            );
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        if (! $composer = $this->executables->find('composer')) {
            $io->error('You must have a executable named composer in your PATH.');
            return 1;
        }

        $tmpFile = sys_get_temp_dir() . '/' . uniqid('php_cli_tools_', true);
        @exec("$composer global config bin-dir --absolute &> $tmpFile", $execOutput, $execReturnVar);
        if ($execReturnVar) {
            $io->error("An error occurred while running 'composer global config bin-dir --absolute'");
            return 1;
        }
        $composerBinDir = trim(strrchr(trim(file_get_contents($tmpFile)), "\n"));
        unlink($tmpFile);

        $command = "$composer global require bamarni/symfony-console-autocomplete -n  &> /dev/null";
        $text = 'Installing bamarni/symfony-console-autocomplete ...';
        if (file_exists("$composerBinDir/symfony-autocomplete")) {
            $text = 'Updating bamarni/symfony-console-autocomplete ...';
            $command = "$composer global update bamarni/symfony-console-autocomplete -n &> /dev/null";
        }
        $io->text($text);
        @exec($command, $execOutput, $execReturnVar);
        if ($execReturnVar) {
            $io->error("An error occurred while running '$command'");
            return 1;
        }

        $tools = array_map('trim', explode(',', $io->ask(
            'Which tools based on Symfony Console should be completed? Do not forget possible aliases!',
            'console, php-cs-fixer, phpspec, behat, phpmetrics, couscous, dev, prod'
        )));

        $aliases = implode(' ', array_map(function (string $tool) {
            return "--aliases=$tool";
        }, $tools));
        $script = "$composerBinDir/symfony-autocomplete --disable-default-tools $aliases";

        if (! $this->completions->installBashCompletionFromEval($io, 'symfony_bash_completion', $script)) {
            return 1;
        }
        $io->success('Installed the latest version of symfony console bash completion.');
        $io->note('Remember to start a new console to activate this changes.');
        return 0;
    }
}
