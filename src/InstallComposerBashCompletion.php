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
use UFOMelkor\CliTools\Installers\AliasInstaller;
use UFOMelkor\CliTools\Installers\BashCompletionInstaller;

class InstallComposerBashCompletion extends Command
{
    const SOURCE = 'https://raw.githubusercontent.com/iArren/composer-bash-completion/master/composer';

    /** @var ExecutableFinder */
    private $executables;

    /** @var BashCompletionInstaller */
    private $completions;

    /** @var AliasInstaller */
    private $aliases;

    public function __construct(
        ExecutableFinder $executables,
        BashCompletionInstaller $completions,
        AliasInstaller $aliases
    ) {
        parent::__construct('bash:completion:composer');
        $this->setDescription(
            'Bash completion for Composer using '
            . '<options=underscore>https://github.com/iArren/composer-bash-completion</>'
        );
        $this->setHelp(<<<'HELP'
Using composer from the command line is cool but looking into
<options=underscore>https://packagist.org/</> every time you forgot how a package is spelled is not.

iArren developed a bash completion that will complete both package name and
version.
HELP
        );
        $this->executables = $executables;
        $this->completions = $completions;
        $this->aliases = $aliases;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Composer Bash Completion');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if (! $this->executables->find('composer')) {
            $io->error('You must have a executable named composer in your PATH.');
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        try {
            $script = (string) (new Client())->get(self::SOURCE)->getBody();
        } catch (Exception $exception) {
            $io->error('Could not grab the completion file from ' . self::SOURCE);
            return 1;
        }

        $alias = $this->aliases->detectCurrentAlias($io, 'composer');
        if (! $alias) {
            $alias = $this->aliases->askForAlias($io, 'composer', 'composer', 'c');
            if ($alias
                && ! $this->aliases->alias($io, $alias, 'composer', 'composer', 'composer')
            ) {
                return 1;
            }
        }
        $script = trim($script . "\ncomplete -F _composer $alias\n");

        if (! $this->completions->installBashCompletionScript($io, 'composer_bash_completion', $script)) {
            return 1;
        }

        $io->success('Installed the latest version of iArren\'s composer bash completion.');
        $io->note('Remember to start a new console to activate this changes.');
        return 0;
    }
}
