<?php
declare(strict_types=1);
namespace UFOMelkor\CliTools;

use Alchemy\Zippy\Zippy;
use function array_column;
use function array_filter;
use function array_reduce;
use function array_search;
use function file_get_contents;
use function file_put_contents;
use function fputs;
use function fwrite;
use Github\Client;
use function is_array;
use function mkdir;
use const PHP_OS;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function tmpfile;
use UFOMelkor\CliTools\Archive\ReleaseFetcher;
use UFOMelkor\CliTools\Config\Config;
use UFOMelkor\CliTools\Installers\AliasInstaller;
use UFOMelkor\CliTools\Installers\BashCompletionInstaller;
use function var_dump;

class InstallGithubHub extends Command
{
    /** @var Config */
    private $config;

    /** @var ReleaseFetcher */
    private $releases;

    /** @var BashCompletionInstaller */
    private $completions;

    /** @var AliasInstaller */
    private $aliases;

    public function __construct(
        Config $config,
        ReleaseFetcher $releases,
        BashCompletionInstaller $completions,
        AliasInstaller $aliases
    ) {
        parent::__construct('git:hub:hub');
        $this->setDescription('');
        $this->setHelp(<<<'HELP'
hub is a command line tool that wraps git in order to extend it with extra features
and commands that make working with GitHub easier.

The installation installs the last binary, alias it to git and also installs bash autocompletion.
HELP
        );
        $this->config = $config;
        $this->releases = $releases;
        $this->completions = $completions;
        $this->aliases = $aliases;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GitHub Hub');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $io->text('Fetching latest release from <options=underscore>https://github.com/github/hub.git</> ...');
        $latest = $this->releases->fetchLatestRelease('github', 'hub');
        $assets = $latest->assetLabels();
        $default = array_reduce($assets, function ($prev, $curr) {
            return strpos($curr, ' ' . PHP_OS . ' 64-bit') !== false ? $curr : $prev;
        });

        $question = new ChoiceQuestion(
            'Please choose which binary you need',
            $assets,
            $default ? array_search($default, $assets, false) : null
        );
        $question->setMultiselect(false);
        $selection = $io->askQuestion($question);
        try {
            $io->note('Downloading the current release from GitHub ...');
            $asset = $latest->fetchAsset($selection);
        } catch (Throwable $exception) {
            $io->error("Invalid choice: {$selection}");
            return 1;
        }


        try {
            $io->note('Installing hub to ' . $this->config->getBinDirectory($io) . '/hub');
            $asset->copy('bin/hub', $this->config->getBinDirectory($io) . '/hub');
            $io->note('Successfully installed hub');
            $io->note('Installing bash completion for hub');
            if (! $this->completions->installBashCompletionScript(
                $io,
                'hub',
                $asset->get('etc/hub.bash_completion.sh')
            )) {
                $asset->unlink();
                return 1;
            }
            $io->note('Successfully installed bash completion for hub');
            $io->note('Installing git as alias for hub');
            $currentAlias = $this->aliases->detectCurrentAlias($io, 'hub');
            if (! $currentAlias
                && ! $this->aliases->alias($io, 'git', 'hub', 'hub', 'hub')
            ) {
                return 1;
            }
            $io->note($currentAlias ? "$currentAlias is already an alias for hub" : 'git is now an alias for hub');
        } catch (Throwable $exception) {
            $io->error('An error occurred');
            return 1;
        } finally {
            $asset->unlink();
        }
        $io->success('Successfully installed hub');
        return 0;
    }
}
