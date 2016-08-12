<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallAll extends Command
{
    public function __construct()
    {
        parent::__construct('all');
        $this->setDescription('Install all tools');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $commands = [
            'shortcuts:symfony-console',
            'shortcuts:phpspec',
            'bash:completion:composer',
            'bash:completion:symfony-console',
            'git:ignore:global',
            'git:commit-template:global',
            'git:scm-breeze',
            'git:oh-my',
        ];
        $application = $this->getApplication();
        $io->progressStart(count($commands));

        foreach ($commands as $each) {
            $application->find($each)->execute($input, $output);
            $io->progressAdvance();
        }
        $io->progressFinish();
    }
}