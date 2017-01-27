<?php
    use Core\App;

    $config                 = new stdClass();
    $config->base_dir       = $_SERVER['DOCUMENT_ROOT'];
    $config->cache_dir      = $config->base_dir . '/cache';
    $config->app_dir        = $config->base_dir . '/app';
    $config->template_dir   = $config->base_dir . '/public/';
    $config->uploads_dir    = $config->base_dir . '/uploads/';
    $config->template_setup = [
        'charset' => 'utf-8',
        //        'cache'         => $config->cache_dir,
        'cache'   => false,
        'debug'   => true,
        //        'optimizations' => 1
    ];
    $config->db             = [
        'database' => 'job',
        'username' => 'bn_wordpress',
        'password' => 'c1f2ac36e6',
        'hostname' => 'localhost:3306'
    ];
    $config->pages          = [
        'Home'   => [
            'url'   => '/',
            'title' => 'Головна'
        ],
        'Admin'  => [
            'url'   => '/admin',
            'title' => 'Адмінпанель'
        ],
        'Login'  => [
            'url'   => '/login',
            'title' => 'Login'
        ],
        'Logout' => [
            'url'   => '/logout',
            'title' => 'Logout'
        ],
    ];
    require $config->base_dir . '/vendor/autoload.php';
    App::start($config);
