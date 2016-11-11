# Laravel 5.3+ Support Module

This package includes a bunch of generators out of the box:

- `support:resource`
- `support:resource:controller`
- `support:resource:model`
- `support:resource:repository`
- `support:resource:request`
- `support:resource:route`
- `support:resource:schema`
- `support:resource:validator`

*More features are on their way*

## Usage

### Step 1: Install Through Composer

```
composer require chipaau/support --dev
```

### Step 2: Add the Service Provider

You'll only want to use these generators for local development, so you don't want to update the production  `providers` array in `config/app.php`. Instead, add the provider in `app/Providers/AppServiceProvider.php`, like so:

```php
public function register()
{
	if ($this->app->environment() == 'local') {
		$this->app->register(\Support\SupportServiceProvider::class);
	}
}
```


### Step 3: Run Artisan!

You're all set. Run `php artisan` from the console, and you'll see the new commands in the `support:*` namespace section.

## Examples

- [Creating full resource bundles](#creating-full-resource-bundle)
- [Creating individual elements of resource](#creating-individual-elements-of-resource)
- [Generating resources for modules](#generating-resources-for-modules)

### Creating full resource bundles

```
php artisan support:resource ResourceOne ResourceTwo ResourceThree ...
```

Notice the format that we use, when giving the command more than 1 resource to create, we separate them with spaces

This would create the whole bundle required for the support module to work. This bundle includes:

- `Resource controller`
- `Resource model`
- `Resource repository`
- `Resource request`
- `Adding the Resource route to the routes file`
- `Resource schema`
- `Resource validator`

### Creating individual elements of resource

```
php artisan support:resource:controller ResourceOne ResourceTwo ResourceThree ...
```

This would create the controllers for the required resources. This could be used with all the other available artisan commands.

### Generating resources for modules

```
php artisan vendor:publish --tag="support"
```

This would copy the configuration file required for modular file generation to the `config` directory.

```
php artisan support:resource ResourceOne ResourceTwo ResourceThree ... --module="ModuleName"
```

This would create the whole bundle required for the support module, inside the modules folder included in the `config/support.php` configuration file. This could be used with all the other available artisan commands.