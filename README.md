[![add-on registry](https://img.shields.io/badge/DDEV-Add--on_Registry-blue)](https://addons.ddev.com)
[![tests](https://github.com/upstreamable/ddev-basin/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/upstreamable/ddev-basin/actions/workflows/tests.yml?query=branch%3Amain)
[![last commit](https://img.shields.io/github/last-commit/upstreamable/ddev-basin)](https://github.com/upstreamable/ddev-basin/commits)
[![release](https://img.shields.io/github/v/release/upstreamable/ddev-basin)](https://github.com/upstreamable/ddev-basin/releases/latest)

# DDEV Basin

## Overview

This add-on integrates Basin into your [DDEV](https://ddev.com/) project.

## Installation

```bash
ddev add-on get upstreamable/ddev-basin
ddev restart
```

After installation, make sure to commit the `.ddev` directory to version control.

## Usage

| Command | Description |
| ------- | ----------- |
| `ddev describe` | View service status and used ports for Basin |
| `ddev logs -s basin` | Check Basin logs |

## Advanced Customization

To change the Docker image:

```bash
ddev dotenv set .ddev/.env.basin --basin-docker-image="ddev/ddev-utilities:latest"
ddev add-on get upstreamable/ddev-basin
ddev restart
```

Make sure to commit the `.ddev/.env.basin` file to version control.

All customization options (use with caution):

| Variable | Flag | Default |
| -------- | ---- | ------- |
| `BASIN_DOCKER_IMAGE` | `--basin-docker-image` | `ddev/ddev-utilities:latest` |

## Credits

**Contributed and maintained by [@upstreamable](https://github.com/upstreamable)**
