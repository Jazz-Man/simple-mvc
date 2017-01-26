<?php
    use Core\App;

    $config                  = new stdClass();
    $config->base_dir        = $_SERVER['DOCUMENT_ROOT'];
    $config->cache_dir       = $config->base_dir . '/cache';
    $config->app_dir         = $config->base_dir . '/app';
    $config->core_dir        = $config->app_dir . '/Core/';
    $config->controllers_dir = $config->app_dir . '/Controllers/';
    $config->views_dir       = $config->app_dir . '/Views/';
    $config->models_dir      = $config->app_dir . '/Models/';
    $config->template_setup  = [
        'cache'         => false,
        'debug'         => true,
        'optimizations' => 1
    ];
    $config->db              = [
        'database' => 'job',
        'username' => 'bn_wordpress',
        'password' => 'c1f2ac36e6',
        'hostname' => 'localhost:3306'
    ];
    $config->pages = [
        'Home' => [
            'url' => '/',
            'title'=>'Головна'
        ],
        'Admin' => [
            'url' => '/admin',
            'title'=>'Адмінпанель'
        ],
    ];
    require $config->base_dir . '/vendor/autoload.php';
    App::start($config);
