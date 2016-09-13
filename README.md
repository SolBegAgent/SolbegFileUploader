*The plugin and documentation under development!*

INSTALLATION
============

Schema:

- composer install
- use service provider
- publish filemanager config
- configure contexts & models (where docs about generating context config)


Configure file contexts:

// app/config/filecontexts.php

```php
return [
    'product-logo' => [
        'disk' => 'public', // using laravel's filesystem, here should be the name of disk defined in app/config/filesystems.php
        'mimeTypes' => 'image/*', // or ['image/jpg', 'image/png'] - internal validation
        'maxSize' => 5 * 1024 * 1024, // 5 megabytes
        'defaultUrl' => '/public/path/to/no-photo.jpg',
        'formatters' => [ // examples
            'small' => ['image/thumb', 'width' => 200, 'height' => 200],
            'thumbnail' => ['from', 'small', 'formatter' => ['image/thumb', 'width' => 100]],
            'watermarked' => ['image/watermark', 'filename' => '/path/to/watermark', 'x' => 'right', 'y' => 'bottom'],
            'grayscaled' => ['image/grayscale'],

            'some_format_1' => function ($readFilePath) {
                // return path to formatted file
            }
            'some_format_2' => CUSTOM\FORMATTER\CLASS\NAME::class,
            'some_format_3' => [
                'class' => CUSTOM\FORMATTER\CLASS\NAME::class,
                'param1' => 'value1',
                'params2' => 'value2',
            ],
            'some_format_4' => ['chain', 'formatters' => [
                ['image/thumb', ...], // config for formatter 1
                ['image/thumb', ...], // config for formatter 2
                ...
            ]],
        ],
    ],
];
```

// configure Eloquent model

```php
class Product extends ...\Eloquent\Model
{
    use FilesTrait;

    protected $fillable = [..., 'logo_photo', ...];

    protected function filesAttributes()
    {
        return [
            // attribute name => the name of context from filecontexts.php
            'logo_photo' => 'product-logo',
        ];
    }
}
```

// Usage
```php
    $product = new Product;
    $product->fill($request->all();

    // File from input (if passed) will be saved automatically in `public/uploads/product-logo/hashed-subfolder-123/some-hashed-name.jpg`
    // Formatted 'thumbnail' version (for example) will be generated on fly and saved in `public/uploads/product-logo/hashed-subfolder-123/formats/some-hashed-name.jpg/thumbnail.jpg`
    // String 'hashed-subfolder-123/some-hashed-name.jpg' will be saved in database in `logo_photo` column.
    $product->save();

    $product = Product::find(1);
    $product->logo_photo = null; // or ''
    $product->save(); // file will be removed from database, but not from disk

    $product = Product::find(1);
    $product->logo_photo = 'some-exists/file-in-disk.jpg'; // only in current product-logo context
    $product->save();
```

```twig
    // in blade
    // output <img ... /> tag
    <img src="{{ $product->logo_photo }}" />
    <img src="{{ $product->logo_photo->asThumbnail }}" />
    <img src="{{ $product->logo_photo->url() }}" />
    <img src="{{ $product->logo_photo->url('thumbnail') }}" />
    {!! $product->logo_photo->img() !!}
    @img($product->logo_photo)
    @img($product->logo_photo, 'thumbnail')

    // check if file is not empty
    @if($product->logo_photo->exists())
    // ...
    @endif
    @if($product->logo_photo->isEmpty())
    // ...
    @endif

    // output other characteristics
    Size: {{ $product->logo_photo->size() }}
    Mime Type: {{ $product->logo_photo->mimeType() }}
    ...
```

```php
    // in JSON API
    return json_encode($product);
    //  returns [
    //      ...
    //      'logo_photo' => [
    //          'url' => 'http://domain/path/to/original.jpg',
    //          'size' => 12312312,
    //          'mimeType' => 'image/jpeg',
    //          'formats' => [
    //              'small' => 'http://domain/path/to/small.jpg',
    //              'thumbnail' => 'http://domain/path/to/thumbnail.jpg',
    //          ],
    //      ],
    //      ...
    //  ];
```
