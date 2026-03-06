<?php

namespace Basin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'add-on:sync')]
class SyncAddons
{
    public function __invoke(OutputInterface $output): int
    {
        $output->writeln('Whoa!');
        return Command::SUCCESS;
    }
}
