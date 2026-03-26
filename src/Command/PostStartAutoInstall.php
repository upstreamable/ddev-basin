<?php

namespace Basin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Try to install the site automatically.
 */
#[AsCommand(name: 'post-start:auto-install')]
class PostStartAutoInstall
{
    public function __invoke(OutputInterface $output): int
    {
        if (getenv('DDEV_DATABASE_FAMILY') !== 'mysql') {
            $output->writeln('Database is not mysql. Auto install not supported (yet)');
            return Command::SUCCESS;
        }

        $host = null;
        $db = null;
        $user = null;
        $pass = null;

        $credentialsPath = realpath(getenv('HOME') . '/.my.cnf');
        if (file_exists($credentialsPath)) {
            $config = parse_ini_file($credentialsPath, true);
            $creds = $config['client'] ?? $config['mysql'] ?? [];
            $host = $creds['host'] ?? 'localhost';
            $user = $creds['user'] ?? null;
            $pass = $creds['password'] ?? null;
            $db = $config['mysql']['database'] ?? $creds['database'] ?? null;
        }
        else {
            $output->writeln('Detecting the database credentials failed. Not possible to auto-install');
            return Command::SUCCESS;
        }

        if (!$host || !$db || !$user || !$pass) {
            $output->writeln('Database credentials are not complete for determining if the site is installed');
            return Command::SUCCESS;
        }

        $pdo = new \PDO('mysql:host=' . $host . ';dbname=' . $db, $user, $pass);
        $stmt = $pdo->prepare('SELECT COUNT(*) as tables FROM information_schema.tables WHERE table_schema = ?;');
        $stmt->execute([$db]);
        $result = $stmt->fetch();
        // Site is installed when there are some tables.
        if ($result['tables'] !== 0) {
            return Command::SUCCESS;
        }
        $output->writeln('Empty database detected. Installing the site');

        if (!str_starts_with(getenv('DDEV_PROJECT_TYPE'), 'drupal')) {
            $output->writeln('Auto install is only supported for Drupal projects');
            return Command::SUCCESS;
        }

        $process = new Process(['drush', '-y','site:install', '--existing-config'], getenv('DDEV_APPROOT'));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        // Run a config import to get the configuration from any config_split to be active.
        $process = new Process(['drush', '-y','config:import'], getenv('DDEV_APPROOT'));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        return Command::SUCCESS;
    }
}
