#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Installer\InstallCommand;

$token = $argv[1] ?? null;
$package = $argv[2] ?? null;

if (!$token || !$package) {
    echo "Usage: install <TOKEN> <PACKAGE>\n";
    exit(1);
}

(new InstallCommand())->handle($token, $package);