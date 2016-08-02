<?php
declare(strict_types = 1);
namespace UFOMelkor\CliTools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallAll extends Command
{
    public function __construct()
    {
        parent::__construct('all');
        $this->setDescription('Install all tools');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->find('symfony:console:shortcuts')->execute($input, $output);
        $this->getApplication()->find('composer:completion:bash')->execute($input, $output);
    }
}