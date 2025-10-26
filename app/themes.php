<?php

function get_available_themes(): array
{
    return [
        'horizon' => [
            'label' => 'Horizon Nightfall',
            'stylesheet' => 'theme-horizon.css',
            'body_class' => 'theme-horizon',
        ],
        'serpent' => [
            'label' => 'Serpent Flux',
            'stylesheet' => 'theme-serpent.css',
            'body_class' => 'theme-serpent',
        ],
        'nebula' => [
            'label' => 'Nebula Prism',
            'stylesheet' => 'theme-nebula.css',
            'body_class' => 'theme-nebula',
        ],
    ];
}

function get_theme_config(string $key): array
{
    $themes = get_available_themes();
    if (isset($themes[$key])) {
        return $themes[$key];
    }

    return $themes['horizon'];
}

