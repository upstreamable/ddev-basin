<?php

namespace Basin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Add add-ons to the requirements and install required add-ons.
 *
 * When the minimum version is console <= 8 the extends and the execute can be
 * dropped.
 */
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

                $installedAddons[] = $manifest['name'];
                // Index by name
                $manifestData[$manifest['name']] = $manifest;
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
                    "BASIN_ADDON=" . $basinConfig['addons'][$addonToInstall]['repository'] . "\n" .
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
            // Detect additional config
            foreach ($addonsToAdd as $addonToAdd) {
                if (str_starts_with($manifestData[$addonToAdd]['repository'], '/')) {
                    $output->writeln($addonToAdd . ' add-on comes from a local repository. Not adding to config.basin.yaml');
                    continue;
                }
                $output->writeln($addonToAdd . ' installed. Adding to config.basin.yaml');
                $addonData = [
                    'version' => $manifestData[$addonToAdd]['version'],
                    'repository' => $manifestData[$addonToAdd]['repository'],
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
