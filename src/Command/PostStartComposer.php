<?php

namespace Basin\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'post-start:composer')]
class PostStartComposer extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->__invoke($output);
    }
    public function __invoke(OutputInterface $output): int
    {
        if (file_exists(getenv('DDEV_APPROOT') . '/vendor')) {
            return Command::SUCCESS;
        }
        $output->writeln('No vendor folder found, running composer install');
        $process = new Process(['composer', 'install', '--no-interaction'], getenv('DDEV_APPROOT'));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        return Command::SUCCESS;
    }
}
