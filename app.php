<?php

// Main

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application("Videodrome", "1.6");

// register commands
$application->add(new App\Command\SiretRequest());
$application->add(new \App\Command\SiretGoogle());

$application->run();
