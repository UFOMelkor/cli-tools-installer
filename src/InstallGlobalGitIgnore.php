<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use GitWrapper\GitException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use UFOMelkor\CliTools\Config\Config;
use UFOMelkor\CliTools\Git\Git;

class InstallGlobalGitIgnore extends Command
{
    /** @var Config */
    private $config;

    /** @var ExecutableFinder */
    private $executables;

    public function __construct(Config $config, ExecutableFinder $executables)
    {
        parent::__construct('git:ignore:global');
        $this->setDescription('Ignoring files in every Git repository');
        $this->setHelp(<<<HELP
Adding the same files over and over again to your .gitignore can be very
annoying. Therefore Git allows to ignore files globally within a global
.gitignore file.

This installation let you assemble a global .gitignore file using the templates
from <options=underscore>https://github.com/github/gitignore</> and configures git to use it.
HELP
        );
        $this->config = $config;
        $this->executables = $executables;
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

        $gitBinary = $this->executables->find('git');
        if (! $gitBinary) {
            $io->error('Could not find git in your PATH.');
            return 1;
        }

        if (! $io->confirm('Do you want to install this tool?')) {
            return 0;
        }

        $globalGitignorePath = $this->config->getHomeDirectory($io) . '/.gitignore_global';

        $git = new Git($gitBinary);
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
            $git->setGlobalConfig('core.excludesfile', $globalGitignorePath);
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

    private function createGitIgnore(Git $git, SymfonyStyle $io)
    {
        $repositoryUrl = 'https://github.com/github/gitignore.git';
        try {
            $io->text(
                "Cloning <options=underscore>$repositoryUrl</> ..."
            );
            $repository = $git->cloneTemporary($repositoryUrl);
        } catch (GitException $exception) {
            $io->error(
                "An error occurred while cloning <options=underscore>$repositoryUrl</>: "
                . $exception->getMessage()
            );
            return null;
        }
        $io->text(
            "Cloned the .gitignore templates from <options=underscore>$repositoryUrl</> to "
            . $repository->localPath()
        );

        $choices = $repository->fileNamesWithoutPathAndExtension('/Global/*.gitignore');

        $question = new ChoiceQuestion(
            'Please choose templates to create the .gitignore (divide multiple by comma)',
            $choices
        );
        $question->setMultiselect(true);
        $selection = $io->askQuestion($question);
        $gitignoreContent = implode("\n\n", array_map(function (string $choice) use ($repository) {
            return str_pad('', strlen($choice) + 4, '#') . "\n"
            . "# $choice #\n"
            . str_pad('', strlen($choice) + 4, '#') . "\n"
            . file_get_contents($repository->localPath() . "/Global/$choice.gitignore");
        }, $selection));
        $repository->remove();
        return $gitignoreContent;
    }

    private function globalGitIgnoreExistsAndShouldNotBeOverridden(Git $git, SymfonyStyle $io)
    {
        $pathToExistingGitignore = $git->getGlobalConfig('core.excludesfile');
        if ($pathToExistingGitignore !== null
            && (file_exists($pathToExistingGitignore)
                && ! $io->confirm('There is already a global gitignore. Do you want to override it?', false)
            )
        ) {
            return 0;
        }
        return null;
    }
}
