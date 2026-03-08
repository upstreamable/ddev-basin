<?php

namespace Basin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'add-on:sync')]
class SyncAddons
{
    public function __invoke(OutputInterface $output): int
    {
        $basinConfigPath = getenv('DDEV_APPROOT') . '/.ddev/config.basin.yaml';
        $basinConfig = Yaml::parseFile($basinConfigPath);
        $addons = $basinConfig['addons'] ?? [];
        $expectedAddons = array_keys($addons);

        $installedAddons = [];

        $finder = new Finder();
        $finder->in(getenv('DDEV_APPROOT') . '/.ddev/addon-metadata')->files()->name('manifest.yaml');
        $manifestData = [];
        if ($finder->hasResults()) {
            foreach ($finder as $yaml) {
                $manifest = Yaml::parseFile($yaml->getRealPath());

                // Avoid basin itself as an installed add-on since it should be
                // installed separately in every project
                if ($manifest['name'] === 'basin') {
                    continue;
                }

                $installedAddons[] = $manifest['repository'];
                // Index by repository
                $manifestData[$manifest['repository']] = $manifest;
            }
        }

        $addonsToInstall = array_diff($expectedAddons, $installedAddons);
        if ($addonsToInstall) {
            $output->writeln('Installing: ' . implode(', ', $addonsToInstall));
            foreach ($addonsToInstall as $addonToInstall) {
                // To be executed by the post-start hook `basin-host-commands`
                file_put_contents(
                    filename: getenv('DDEV_APPROOT') . '/.ddev/commands/basin/01-' . hash('sha256', $addonToInstall) . '.env',
                    data: "BASIN_COMMAND=add-on-get\n" .
                    "BASIN_ADDON=" . $addonToInstall . "\n" .
                    "BASIN_ADDON_VERSION=" . $basinConfig['addons'][$addonToInstall]['version'],
                );
                // Set env vars config.
                if (isset($basinConfig['addons'][$addonToInstall]['env'])) {
                    file_put_contents(
                        filename: getenv('DDEV_APPROOT') . '/.ddev/.env.' . $basinConfig['addons'][$addonToInstall]['name'],
                        data: $basinConfig['addons'][$addonToInstall]['env']
                    );
                }
            }
            // Restart as a post-start hook `basin-host-commands`
            file_put_contents(
                filename: getenv('DDEV_APPROOT') . '/.ddev/commands/basin/99-restart.env',
                data: "BASIN_COMMAND=ddev-restart"
            );
        }
        $addonsToAdd = array_diff($installedAddons, $expectedAddons);
        if ($addonsToAdd) {
            $output->writeln(implode(', ', $addonsToAdd) . ' installed. Adding to config.basin.yaml');
            // Detect additional config
            foreach ($addonsToAdd as $addonToAdd) {
                $addonData = [
                    'version' => $manifestData[$addonToAdd]['version'],
                ];

                $dotEnvPath = getenv('DDEV_APPROOT') . '/.ddev/.env.' . $manifestData[$addonToAdd]['name'];
                if (file_exists($dotEnvPath)) {
                    $addonData['name'] = $manifestData[$addonToAdd]['name'];
                    $addonData['env'] = file_get_contents($dotEnvPath);
                }
                $basinConfig['addons'][$addonToAdd] = $addonData;
            }
            file_put_contents($basinConfigPath, Yaml::dump(
                input: $basinConfig,
                inline: 4,
                flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
            ));
        }

        return Command::SUCCESS;
    }
}
