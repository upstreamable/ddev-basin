<?php

namespace Basin\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'post-start:composer')]
class PostStartComposer
{
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
