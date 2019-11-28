# Page Marker

A Simple Library for remembering/recording URL Query String used for filtering list

## Installation

Via Composer:

```
composer require adods/page-marker
```

or just download the file manually and put in your lib directory

## Requirement

`PHP >= 7.0` and PHP Session enabled

## Simple Usage

Include the library to your file, create instance, `init()` and `remember()`. 
Use `forget()` to reset.

```php
require "path/to/lib/src/PageMarker.php";
// or if you're using composer
require "path/to/vendor/autoload.php";

use Adods\PageMarker\PagerMarker;

// Make sure there's no output before doing this
$pm = new PageMarker;

// Setup default setting and redirect when condition are met
$pm->init();

// Start recording
$pm->remember();
```

### `init()`

By default, `init()` will generate default name from current URL Path, 
lowercased, then replace slashes `'/'`, dots `'.'`, stripes `'-'`, and spaces 
`' '` to underscore `_`. Set default url to current url without it's query 
string. And use the content of $_GET Super Global variable as base data. But, 
it can be changed.

After default properties are set, then it will check if redirect to the last 
state is met or just go on with the process.

The conditions are:
- If the base data contain a reset key element, then the remembered session will be erased and will be redirected to the base URL.
- If the base data is not empty, then process will continue normally
- Then it will check the session for existing data.
- If session data is empty, then process will continue normally
- Lastly, it will redirect to the base URL with the data from session as Query String parameters

## Main Settings/Setup

Settings below should be set BEFORE `init()`.

### Session Name:

Name is used as part of the session key.

```php
$pm->setName('new_name');
```

Name should be easy to read and descriptive about the list and also array key 
friendly. And any characters mentioned above will be replaced with underscore 
`'_'`

### Change base URL:

```php
$om->setUrl('http://newurl.test/path')
```

Any query string will be omitted.

### Change base data source

```php
$pm->setBase($somearray);
```

## More Function

### Add/Change data

```php
$pm->add('newkey', 'newvalue');
// or
$pm->add([
    'key1' => 'val1',
    'key2' => 'val2',
    'key3' => 'val3'
]);
```
Using array as parameter will merge the array with current data

### Excluding data

```php
$pm->except('nothisone');
// or
$pm->except(['notme', 'alsonotme']);
```

### Remember Options

Data can also overrided from `remember()` method.

```php
$pm->remember([
    'shouldbeme' => 'yes'
]);
```

By default, the given parameter will REPLACE the current base data. To add them,
you can use `PageMarker::OVERRIDE_APPEND` constant as second parameter.

```php
$pm->remember([
    'letmejoin' => 'yes'
], PageMarker::OVERRIDE_APPEND);
```

### Manually Forget/Reset

```php
$pm->forget();
```

### Get Forget/Reset URL

```php
$pm->getResetUrl();
```

## Full Example

```php
require_once './vendor/autoload.php';

use Adods\PageMarker\PageMarker;

$pm = new PageMarker;

$pm->init();

$pm->remember();

var_dump($pm->getBase());
?>
<div>
    Base URL: <?php echo $pm->getUrl(); ?>
</div>
<a href="<?php echo $pm->getResetUrl(); ?>">Reset</a>
```

Try to add parameters to the URL. Everytime the base URL accessed it will automatically redirect with last state URL with Query String Parameters. Click on Reset link to clear all the saved data.