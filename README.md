Description
===========

Files manager for laravel 5.2/5.3.
It is helpful to simply upload and use in future files,
and work with them like with another simple attributes.

It flexible so you may customize your validation, formatted versions of files (e.g. thumbnails),
default urls, storages as you want.
You may separate your files by contexts, and contexts by types.
Also you may configure global setting that will be used for all contexts.

This plugin also allows to save file in temporary directory between requests.
So if validation of request's data was failed user must not upload a file again.

Solbeg/laravel-files-manager uses standard [laravel's filesystem](https://laravel.com/docs/5.3/filesystem) to storing files,
so you may use it with any storage driver like local, amazon S3, rackspace or others.

For manipulating with images this package uses [Intervention/image plugin](http://image.intervention.io/).

Requirements
============

- PHP >= 5.5.9
- Fileinfo Extension
- Laravel Framework 5.2.* or 5.3.*
- [Intervention/image plugin](http://image.intervention.io/) (only for working with images)

Installation
============

The best way to install this plugin is using [Composer](http://getcomposer.org/).

To install the most recent version, run the following command:

```
$ php composer.phar require solbeg/laravel-files-manager
```

After you have installed this vendor, add the following lines.

In your Laravel config file `config/app.php` in the `$providers` array
add the service provider for this package.

```php
    // ...
    Solbeg\FilesManager\FilesManagerServiceProvider::class,
    // ...
```

In your application `Kernel.php` class in the `web` middleware group
add middleware for this package.

```php
    // ...
    \Solbeg\FilesManager\StoreUploadedFilesMiddleware::class,
    // ...
```

If you will use standard laravel's public path,
then you need to create symbolic link from `public/storage` to `storage/app/public` directory.
You may read about this [here](https://laravel.com/docs/5.3/filesystem).

If you are not create it yet, you may do it quickly through artisan command:

```
$ php artisan filecontext:create-symlink
```

Installation of intervention/image
==================================

If you want to work with images, you likely want to install `intervention/image` plugin.
Because all formatters of this files manager that work with images require it.

For installing `intervention/image` plugin follow the next instructions
or read official documentation (Composer Installation and Integration in Laravel):
[here](http://image.intervention.io/getting_started/installation).

Install it via composer:

```
$ php composer.phar require intervention/image
```

After you have installed Intervention Image, open your Laravel config file `config/app.php` and add the following lines.

In the `$providers` array add the service providers for this package.

```php
    // ...
    Intervention\Image\ImageServiceProvider::class,
    // ...
```

Add the facade of this package to the `$aliases` array.

```php
    // ...
    'Image' => Intervention\Image\Facades\Image::class,
    // ...
```

Configure
=========

At first we suggest publish `config/filemanager.php` config file,
so you may quickly configure the plugin as you want.

You may do it with artisan console command.

```
$ php ./artisan vendor:publish --provider="Solbeg\FilesManager\FilesManagerServiceProvider" --tag=config
```

All files in this plugin are separated by contexts.
For example: you probably want to have 'product-logo' and 'user-avatar' contexts,
so each of them have own custom config (validation, formatters and others).

So you likely want to create your the first context.
You may do it in two ways: add config in your config folder or define it inline in your model.
We recommend the first way because it keeps your models cleaner.

For quickly creating context you may use artisan console command:

```
$ php ./artisan make:filecontext {context-name}
```

Where `context-name` you should replace with your name of a new context, e.g. `product-logo`.

Then see file `config/filecontexts/{context-name}.php` for more info about available settings.

Configure model
===============

You likely want to work with files, names of which stored in database.
So you should configure your Eloquent model.

For it you need include `Solbeg\FilesManager\ModelFilesTrait` in you Eloquent model
and add `filesAttributes()` method.

Example:


```php

use Solbeg\FilesManager\ModelFilesTrait;

class Product extends ...\Eloquent\Model
{
    use ModelFilesTrait;

    protected $fillable = [..., 'logo_photo', ...];

    protected function filesAttributes()
    {
        return [
            // attribute name => the name of context
            'logo_photo' => 'product-logo',

            // or context config right here
            //'logo_photo' => [
            //    'formats' => [
            //        'thumbnail' => 'image/thumb: width = 200, height = 300',
            //    ],
            //
            //    'validate' => [
            //        'types' => 'image/jpeg, image/png',
            //        'extensions' => ['jpg', 'jpeg', 'png'],
            //        // ...
            //    ],
            //],
        ];
    }
}
```

Usage
=====

In your controller:

```php
    $product = new Product;
    $product->fill($request->all());

    // File from input (if passed) will be saved automatically in `public/uploads/product-logo/hashed-subfolder-123/some-hashed-name.jpg`
    // Formatted 'thumbnail' version (for example) will be generated on fly and saved in `public/uploads/product-logo/hashed-subfolder-123/formats/some-hashed-name.jpg/thumbnail.jpg`
    // String 'hashed-subfolder-123/some-hashed-name.jpg' will be saved in database in `logo_photo` column.
    $product->save();

    $product = Product::find(1);
    $product->logo_photo = null; // or ''
    $product->save(); // file will be removed from database and from disk

    // returning url to origin file
    $product->logo_photo->url();
    $product->logo_photo->url;
    $product->logo_photo->href;
    (string) $product->logo_photo;

    // returning url of formatted as `thumbnail` versions of file
    $product->logo_photo->url('thumbnail');
    $product->logo_photo->asThumbnail;

    // Check whether file is empty or not
    $product->logo_photo->exists(); // whether file exists
    $product->logo_photo->exists('thumbnail'); // whether formatted file exists
    $product->logo_photo->isEmpty(/* null or 'thumbnail' */); // reverse of `exists()` method

    // Fetch other params
    $product->logo_photo->size(); // size of origin file in bytes
    $product->logo_photo->size('thumbnail'); // size of formatted file in bytes
    $product->logo_photo->mimeType(); // MIME type of origin file
    $product->logo_photo->mimeType('thumbnail'); // MIME type of formatted file
    $product->logo_photo->lastModified(); // the last modified timestamp of origin file
    $product->logo_photo->lastModified('thumbnail'); // the last modified timestamp formatted file
    $product->logo_photo->image()->width(); // origin image width in pixels
    $product->logo_photo->image('thumbnail')->width(); // formatted image width in pixels
    $product->logo_photo->image()->height(); // origin image height in pixels
    $product->logo_photo->image('thumbnail')->height(); // formatted image height in pixels
```

In your blade template:

```twig
    {{-- Output <img ... /> tag --}}
    <img src="{{ $product->logo_photo }}" alt='' />
    <img src="{{ $product->logo_photo->asThumbnail }}" alt='' />
    <img src="{{ $product->logo_photo->url() }}" alt='' />
    <img src="{{ $product->logo_photo->url('thumbnail') }}" alt='' />

    {{ $product->logo_photo->img() }} <!-- outputs 'img' tag -->
    {{ $product->logo_photo->img('thumbnail') }} <!-- outputs 'img' tag -->
    {{ $product->logo_photo->img(null, ['id' => 'some-tag-id']) }} <!-- outputs 'img' tag -->
    {{ $product->logo_photo->link() }} <!-- outputs 'a' tag -->
    {{ $product->logo_photo->link('thumbnail') }} <!-- outputs 'a' tag -->
    {{ $product->logo_photo->link(null, 'Download Title', ['class' => 'some-css-class']) <!-- outputs 'a' tag -->
    {{ $product->logo_photo->link(null, $product->logo_photo->img('thumbnail')) <!-- outputs link with image -->

    {{-- Check if file is not empty --}}
    @if($product->logo_photo->exists())
    // ...
    @endif
    @if($product->logo_photo->isEmpty())
    // ...
    @endif

    {{-- Output other characteristics --}}
    Size: {{ $product->logo_photo->size() }}
    Mime Type: {{ $product->logo_photo->mimeType() }}
    ...
```

In JSON action:

```php
    return response()->json($product);
    /* Returns something like:
        {
            // ...
            "logo_photo": {
                "url": "http://...",
                "path" => "0001/...",
                "formats": {
                    "thumbnail": "http://...",
                    "small": "http://...",
                    ...
                }
            },
            // ...
        }
```

You may read more about exporting files to array and json [here](./docs/07.in-rest-api.md);
Read also [how to configure](./docs/07.in-rest-api.md#configure-converter)
which properties of file must be exported.

Requests validation
===================

When you validate your requests using
[laravel's validation system](https://laravel.com/docs/5.3/validation#form-request-validation),
you not need write validation rules again.

Simply use `filecontext` validator, and file will be validates by context validation rules:

```php
// ...
$this->validate($request, [
    // ...
    'logo_photo' => 'required|filecontext:product-logo',
    'some_attribute' => 'filecontext:App\Models\SomeModel@some_attribute',
    // ...
]);
// ...
```

If file is valid, but any other attributes was failed,
the uploaded file will be saved in temporary storage.
So you may use `old($fileAttribute)` in your form:

```twig
@if (old('logo_photo'))
    <input type='hidden' name='logo_photo' value='{{ old('logo_photo')->relativePath() }}' />
    <img src='{{ old('logo_photo')->url() }}' />
@endif
```

Features under development
==========================

```twig
    // in blade
    @img($product->logo_photo)
    @img($product->logo_photo, 'thumbnail')
```

Documentation
=============

Read more about:

- [Contexts](./docs/01.contexts.md)
- [Formatting](./docs/02.formatting.md)
- [Validation](./docs/03.validation.md)
- [File Not Found Handlers](./docs/04.file-not-found-handlers.md)
- [Storages](./docs/05.storages.md)
- [Name Generators](./docs/06.name-generators.md)
- [Using in REST Api](./docs/07.in-rest-api.md)
- [Garbage Collector](./docs/08.garbage-collector.md)
