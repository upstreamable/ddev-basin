<?php

namespace Basin\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Run all tasks needed after a ddev project start.
 */
#[AsCommand(name: 'post-start:hook')]
class PostStartHook
{
    public function __invoke(OutputInterface $output, Application $application): int
    {
        $addonSyncInput = new ArrayInput([
            'command' => 'add-on:sync',
        ]);
        $returnCode = $application->doRun($addonSyncInput, $output);
        if ($returnCode !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        $input = new ArrayInput([
            'command' => 'post-start:composer',
        ]);
        $returnCode = $application->doRun($input, $output);
        if ($returnCode !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        $input = new ArrayInput([
            'command' => 'post-start:auto-install',
        ]);
        $returnCode = $application->doRun($input, $output);
        if ($returnCode !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
