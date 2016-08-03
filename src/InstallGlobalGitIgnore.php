<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use GitWrapper\GitCommand;
use GitWrapper\GitException;
use GitWrapper\GitWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;

class InstallGlobalGitIgnore extends Command
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct('git:ignore:global');
        $this->setDescription('Ignoring files in every Git repository');
        $this->setHelp(<<<HELP
Adding the same files over and over again to your .gitignore can be very
annoying. Therefore Git allows to ignore files globally within a global
.gitignore file.

This installation creates a global .gitignore file for you and configures git
to use it.
HELP
        );
        $this->config = $config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Global .gitignore');
        $io->text(explode("\n", $this->getHelp()));
        $io->newLine(1);

        if ($this->config->isGlobalInstallation($io)) {
            $io->error(
                'The global .gitignore could not be installed for all users. Please try again with user permissions.'
            );
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $gitBinary = $this->config->getGitBinary($io);
        $globalGitignorePath = $this->config->getHomeDirectory($io) . '/.gitignore_global';

        $git = new GitWrapper($gitBinary);
        if (is_int($exitCode = $this->globalGitIgnoreExistsAndShouldNotBeOverridden($git, $io))) {
            return $exitCode;
        }

        $gitIgnoreContent = $this->createGitIgnore($git, $io);
        if ($gitIgnoreContent === null) {
            return 1;
        }

        if (! file_put_contents($globalGitignorePath, $gitIgnoreContent)) {
            $io->error("Could not write to $globalGitignorePath.");
            return 1;
        }

        try {
            $git->run(GitCommand::getInstance('config', '--global', 'core.excludesfile', $globalGitignorePath));
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while configuring the global gitignore file to $globalGitignorePath: "
                . $exception->getMessage()
            );
            return 1;
        }
        $io->text("Configured git to use $globalGitignorePath as global .gitignore");

        $io->success("Installed the global gitignore to $globalGitignorePath.");

        return 0;
    }

    private function createGitIgnore(GitWrapper $git, SymfonyStyle $io)
    {

        $targetDirectory = sys_get_temp_dir() . '/' . uniqid('php_cli_tools_', true);
        try {
            $io->text(
                "Cloning <fg;options=bold>https://github.com/github/gitignore.git</> to "
                . "<fg;options=bold>$targetDirectory</> ..."
            );
            $git->cloneRepository('https://github.com/github/gitignore.git', $targetDirectory);
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while cloning https://github.com/github/gitignore.git to $targetDirectory: "
                . $exception->getMessage()
            );
            return null;
        }
        $io->text("Cloned the .gitignore templates from https://github.com/github/gitignore.git to $targetDirectory");

        $choices = array_map(function (string $choice) use ($targetDirectory) {
            return substr($choice, strlen($targetDirectory . '/Global/'), -10);
        } , glob($targetDirectory . '/Global/*.gitignore'));

        $question = new ChoiceQuestion(
            'Please choose templates to create the .gitignore (divide multiple by comma)',
            $choices
        );
        $question->setMultiselect(true);
        $selection = $io->askQuestion($question);
        $gitignoreContent = implode("\n\n", array_map(function (string $choice) use ($targetDirectory) {
            return str_pad('', strlen($choice) + 4, '#') . "\n"
            . "# $choice #\n"
            . str_pad('', strlen($choice) + 4, '#') . "\n"
            . file_get_contents("$targetDirectory/Global/$choice.gitignore");
        }, $selection));
        return $gitignoreContent;
    }

    private function globalGitIgnoreExistsAndShouldNotBeOverridden(GitWrapper $git, SymfonyStyle $io)
    {
        try {
            $pathToExistingGitignore = trim(
                $git->run(GitCommand::getInstance('config', '--global', 'core.excludesfile'))
            );
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while checking for an existing global gitignore: "
                . $exception->getMessage()
            );
            return 1;
        }
        if (file_exists($pathToExistingGitignore)
            && ! $io->confirm("There is already a global gitignore. Do you want to override it?", false)
        ) {
            return 0;
        }
        return null;
    }
}