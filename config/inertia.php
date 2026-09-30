<?php

return [
    'ssr' => ['enabled' => false],
    'page_paths' => [resource_path('js/pages')],
    'page_extensions' => ['tsx', 'ts', 'jsx', 'js'],
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [resource_path('js/pages')],
        'page_extensions' => ['tsx', 'ts', 'jsx', 'js'],
    ],
    'history' => ['encrypt' => false],
];
