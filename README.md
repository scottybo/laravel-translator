
# Laravel-Translator for PHP and Vue

This is a forked version of Laravel-Translator (https://github.com/thiagocordeiro/laravel-translator) which adds the ability to separate generation of Vue translation files and PHP translation files. It's slightly refactored to play nicely with translated strings rather than named keys.

**Follow these simple rules when adding translations**

 - Always use `__()` instead of `lang()`, `trans()` etc
 - Any strings that contain a `"` must use `&quot;` instead
 - Always use `""` instead of `''` on the string, i.e. `{{ __("String that can be translated") }}`
 - Always use `''` instead of `""` on any embedded HTML, i.e. `<a href=''></a>`
 - Always use `{!! !!}` instead of `{{ }}` when embedding HTML
 - Never break translations onto a new line, it will create additional spaces / tabs
 - For pluralisation, casing etc refer to: [https://laravel.com/docs/5.6/localization](https://laravel.com/docs/5.6/localization)


### Translations in Laravel Blades
For simple strings:

    {{ __("String that can be translated") }}

For strings with variables

    {{ __("Hi :name, welcome to :app", ['name' => 'Scott', 'app' => 'Widgets 3000']) }}


### Translations in Javascript/Vue
**Translations not updating? Clear the cache!** `$ php artisan cache:clear`

Strings used in Vue templates and Javascript code are stored in `/resources/lang/_javascript/` and are handled separately from general PHP/system translations. This is to ensure the visitor only downloads translations that are used in JS, keeping file sizes small for faster downloads.

We load `<script src="/js/lang-{lang}.js"></script>` on every page of the system. The URL is actually a Laravel route which loads the corresponding language file (e.g. `/resources/lang/_javascript/{lang}.json`) and returns a json encoded string of the translations. This string is stored in the variable: window.i18n, which can then be referenced by Javascript.
```php
// Load the translations for javascript strings
Route::get('js/lang-{locale}.js', function ($locale) {

    try {
        // config('app.locales') gives all supported locales
        if (!array_key_exists($locale, config('app.locales'))) {
            $locale = config('app.fallback_locale');
        }

        // Add locale to the cache key
        $json = \Cache::rememberForever("lang-{$locale}.js", function () use ($locale) {
            $path = base_path().'/resources/lang/_javascript/'.$locale.'.json';
            $data = file_get_contents($path);
            return $data;
        });
        
    } catch (Exception $e) {
        $json = $e->getMessage();
    }

    $contents = 'window.i18n = ' . json_encode($json, config('app.debug', false) ? JSON_PRETTY_PRINT : 0) . ';';

    $response = \Response::make($contents, 200);
    $response->header('Content-Type', 'application/javascript');

    return $response;
});
```

During the generation of this string will tell Laravel to cache this response forever. Therefore if you ever update the translation file you need to clear the cache via `php artisan cache:clear`.

To keep the logic of translations consistent, we have a Vue prototype variable of `__` which handles the conversion of the translations (in `/resources/assets/js/app.js`):
```javascript
window._ = require('lodash');

// Localization of strings, see README
Vue.prototype.__ = (string, args) => {
    i18n_array = JSON.parse(window.i18n);

    let value = _.get(i18n_array, string);

    _.eachRight(args, (paramVal, paramKey) => {
        alert(`:${paramKey}`);
        value = _.replace(value, `:${paramKey}`, paramVal);
    });
    return value;
};
```

For simple strings:

    {{ __("String that can be translated") }}

For strings with variables:

    {{ __("Hi :name, welcome to :app", {'name': 'Scott', 'app', 'Widgets 3000'}) }}



----------

Laravel-translator scans your project `resources/view/` `resources/js/` and `app/` folder to find `lang(...)` and `__(...)` functions, then it create keys based on first parameter value and insert into json translation files.

### Installation

You just have to require the package

```sh
$ composer require scottybo/laravel-translator
```

This package register the provider automatically,
[See laravel package discover](https://laravel.com/docs/5.5/packages#package-discovery).

After composer finish it's installation, you'll be able to update your project translation keys running the following command:
```sh
$ php artisan translator:update
```

if for any reason artisan can't find `translator:update` command, you can register the provider manually on your `config/app.php` file:

```php
return [
    ...
    'providers' => [
        ...
        Translator\TranslatorServiceProvider::class,
        ...
    ]
]
```

### Usage
First you have to create your json translation files:
```
app/
  resources/
    lang/
      pt.json
      es.json
      fr.json
    lang/
	    _javascript/
		      pt.json
		      es.json
		      fr.json
```
Keep working as you are used to, when laravel built-in translation funcion can't find given key, it'll return itself, so if you create english keys, you don't need to create an english translation 
```html
blade:
<html>
    {{ __('Hello World') }}
</html>

controllers, models, etc.:
<?php
    __('Hello World');
```

also you can use params on translation keys
```
__('Welcome, :name', ['Arthur Dent'])
```

### Output
`translator:update` command will scan your code to identify new translation keys, then it'll update all json files on `app/resources/lang/` folder appending this keys.

