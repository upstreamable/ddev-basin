# Basin host commands

There is no `basin` container for commands to be placed in
this directory. This directory is used to place FILE.env files to be picked
by the post-start hook `basin-host-command` and run commands on the host.

The format is:
```
COMMAND=add-on-get
ADDON=ddev/ddev-redis
VERSION=v2.2.0
```
