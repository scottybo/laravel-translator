

# Holly Social Readme
## Queues & Workers
TODO
## Backups
TODO
## Whitelabel
TODO
## Localization

Holly can be translated into any language. Language files are stored in `resources/lang`, where 3 different sets of language files are available:

 - **{lang}.json** = Translation strings used in Laravel blades
 - **/_javascript/{lang}.json** = Translation strings used in Vue templates and other Javascript files
 - **/{lang}/{system-area}.php** = Translation strings for various Laravel system messages

As there are so many strings to translate we **don't** use named keys (e.g. 'auth.login.fail.message') and instead just put the full string to be translated (e.g. 'We were not able to login you in').

We use [POEditor](https://poeditor.com) to manage translations. POEditor pulls in the JSON files (it doesn't support the PHP files) from Github and allows collaboration before committing back to Github.

**Follow these simple rules when adding translations**

 - Always use `__()` instead of `lang()`, `trans()` etc
 - Any strings that contain a `"` must use `&quot;` instead
 - Always use `""` instead of `''` on the string, i.e. `{{ __("String that can be translated") }}`
 - Always use `''` instead of `""` on any embedded HTML, i.e. `<a href=''></a>`
 - Always use `{!! !!}` instead of `{{ }}` when embedding HTML
 - Never break translations onto a new line, it will create additional spaces / tabs
 - For pluralisation, casing etc refer to: [https://laravel.com/docs/5.6/localization](https://laravel.com/docs/5.6/localization)

### Choosing which language to show
Currently the language is set at the system level. Whitelabel tenants have a "locale" column  in the whitelabel table of the (core database) where the language for that tenancy is defined.

### Generating the JSON files
**Short version:**  `$ php artisan translator:update`  and then `$ php artisan cache:clear` 

**Long version:** Manually managing the JSON translation files would be a major headache. Instead we use the package: [scottybo/laravel-translator](https://github.com/scottybo/laravel-translator) (a forked version of thiagocordeiro/laravel-translator with the added separation of Javascript/PHP translations to fit our requirements).

The package above has a README, but generally all you need to do is run `$ php artisan translator:update` which will search for any new translation strings and update the json files. Make sure to clear the cache `$ php artisan cache:clear` each time any new translations are added.
### Adding a language
Simply add the json file using the format below for the language you want to add then run `$ php artisan translator:update`
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

### Translations in Laravel Blades
For simple strings:

    {{ __("String that can be translated") }}

For strings with variables

    {{ __("Hi :name, welcome to :app", ['name' => 'Scott', 'app' => 'Holly Social']) }}


### Translations in Javascript/Vue
**Translations not updating? Clear the cache!** `$ php artisan cache:clear`

Strings used in Vue templates and Javascript code are stored in `/resources/lang/_javascript/` and are handled separately from general PHP/system translations. This is to ensure the visitor only downloads translations that are used in JS, keeping file sizes small for faster downloads.

We load `<script src="/js/lang-{lang}.js"></script>` on every page of the system. The URL is actually a Laravel route which loads the corresponding language file (e.g. `/resources/lang/_javascript/{lang}.json`) and returns a json encoded string of the translations. This string is stored in the variable: window.i18n, which can then be referenced by Javascript.

During the generation of this string will tell Laravel to cache this response forever. Therefore if you ever update the translation file you need to clear the cache via `php artisan cache:clear`.

To keep the logic of translations consistent, we have a Vue prototype variable of __ which handles the conversion of the translations (see `/resources/assets/js/app.js`).

For simple strings:

    {{ __("String that can be translated") }}

For strings with variables:

    {{ __("Hi :name, welcome to :app", {'name': 'Scott', 'app', 'Holly Social'}) }}

