<?php

return [

    /**
     * Global configuration for all contexts.
     * See examples of context config for more information.
     * 
     * Properties defined here will be used by default.
     * But you may override any of these properties for each type and each context individually.
     */
    'global' => [

        // example (default max size is 10 mebibytes):
        //'validate' => [
        //    'size' => '10M',
        //],

    ],

    /**
     * Type that used when context has not 'type' property.
     */
    'default_type' => 'default',

    /**
     * You may separate your contexts on types, e.g. 'default', 'image', 'pdf'.
     */
    'types' => [

        /**
         * Configuration of default contexts.
         * You may override any of these properties for each context individually.
         * 
         * See examples of context config for more information.
         */
        'default' => [
        ],

        /**
         * Default configuration for contexts that store images.
         * You may override any of these properties for each context individually.
         * 
         * See examples of context config for more information.
         */
        'image' => [
            'validate' => [
                'types' => 'image/*',
                'extensions' => implode(', ', [ // imploding for useful merging if user wants to override this config
                    'jpg',
                    'jpeg',
                    'png',
                ]),
            ],
        ],

        // You may define any own type of contexts here.
        // 'pdf' => [
        //  ...
        // ],
    ],

    /**
     * These parsers will work when any format name was not found in context.
     * By default it configured so e.g.:
     * 
     * - '200': it is analog of using 'image/resize' formatter and width = 200px
     * - '200x300': it is analog of using 'image/thumb' formatter with (width & height) = (200px & 300px)
     */
    //'format_parsers' => [
    //    'num' => \Bicycle\FilesManager\Formatters\Parsers\NumParser::class,
    //    'num_x_num' => \Bicycle\FilesManager\Formatters\Parsers\NumXNumParser::class,
    //],

    /**
     * Temp directory is used for storing temporary files when formatted versions 
     * of files are generating.
     * By default `sys_get_temp_dir()` will be used.
     */
    //'temp_directory' => '/path/to/custom/temp/directory',
];
