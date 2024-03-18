<?php

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->usePutenv()->bootEnv(dirname(DRUPAL_ROOT) . '/.env');
