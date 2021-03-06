# Using optimised binary UUIDs in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/boxed-code/laravel-binary-uuid.svg?style=flat-square)](https://packagist.org/packages/boxed-code/laravel-binary-uuid)
[![Tests](https://github.com/boxed-code/laravel-binary-uuid/actions/workflows/run_tests.yml/badge.svg)](https://github.com/boxed-code/laravel-binary-uuid/actions/workflows/run_tests.yml)
[![StyleCI](https://styleci.io/repos/110949385/shield?branch=master)](https://styleci.io/repos/110949385)

*Forked from the [archived package](https://github.com/spatie/laravel-binary-uuid) at spaite, maintenance releases only.*

Using a regular uuid as a primary key is guaranteed to be slow.

This package solves the performance problem by storing slightly tweaked binary versions of the uuid. You can read more about the storing mechanism here: [http://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/](http://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/).

The package can generate optimized versions of the uuid. It also provides handy model scopes to easily retrieve models that use binary uuids.

Want to test the perfomance improvements on your system? No problem, we've included [benchmarks](#running-the-benchmarks).

The package currently only supports MySQL and SQLite.

## Installation

You can install the package via Composer:

```bash
composer require boxed-code/laravel-binary-uuid
```

## Usage
 
To let a model make use of optimised UUIDs, you must add a `uuid` field as the primary field in the table.

```php
Schema::create('table_name', function (Blueprint $table) {
    $table->uuid('uuid');
    $table->primary('uuid');
});
```

To get your model to work with the encoded UUID (i.e. to use uuid as a primary key), you must let your model use the `BoxedCode\BinaryUuid\HasBinaryUuid` trait.

```php
use Illuminate\Database\Eloquent\Model;
use BoxedCode\BinaryUuid\HasBinaryUuid;

class TestModel extends Model
{
    use HasBinaryUuid;
}
```

If don't like the primary key named `uuid` you can manually specify the `getKeyName` method. Don't forget set `$incrementing` to false.

```php
use Illuminate\Database\Eloquent\Model;
use BoxedCode\BinaryUuid\HasBinaryUuid;

class TestModel extends Model
{
    use HasBinaryUuid;

    public $incrementing = false;
    
    public function getKeyName()
    {
        return 'custom_uuid';
    }
}
```

If you try converting your model to JSON with binary attributes, you will see errors. By declaring your binary attributes in `$uuidAttributes` on your model, you will tell the package to cast those UUID's to text whenever they are converted to array. Also, this adds a dynamic accessor for each of the uuid attributes.

```php
use Illuminate\Database\Eloquent\Model;
use BoxedCode\BinaryUuid\HasBinaryUuid;

class TestModel extends Model
{
    use HasBinaryUuid;
    
    /**
     * The suffix for the uuid text attribute 
     * by default this is '_text'
     * 
     * @var
     */
    protected $uuidSuffix = '_str';
    
    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'country_uuid' // foreign or related key
    ];
}
```

In your JSON you will see `uuid` and `country_uuid` in their textual representation. If you're also making use of composite primary keys, the above works well enough too. Just include your keys in the `$uuids` array or override the `getKeyName()` method on your model and return your composite primary keys as an array of keys. You can also customize the UUID text attribute suffix name. In the code above, instead of '\_text' it's '\_str'.

The `$uuids` array in your model defines fields that will be converted to uuid strings when retrieved and converted to binary when written to the database. You do not need to define these fields in the `$casts` array in your model.

#### A note on the `uuid` blueprint method

Laravel currently does not allow adding new blueprint methods which can be used out of the box.
Because of this, we decided to override the `uuid` behaviour which will create a `BINARY` column instead of a `CHAR(36)` column.

There are some cases in which Laravel's generated code will also use `uuid`, but does not support our binary implementation.
An example are database notifications. 
To make those work, you'll have to change the migration of those notifications to use `CHAR(36)`.

```php
// $table->uuid('id')->primary();

$table->char('id', 36)->primary();
```

### Creating a model

The UUID of a model will automatically be generated upon save.

```php
$model = MyModel::create();

dump($model->uuid); // b"\x11þ╩ÓB#(ªë\x1FîàÉ\x1EÝ." 
```

### Getting a human-readable UUID

UUIDs are only stored as binary in the database. You can however use a textual version for eg. URL generation.

```php
$model = MyModel::create();

dump($model->uuid_text); // "6dae40fa-cae0-11e7-80b6-8c85901eed2e" 
```

If you want to set a specific UUID before creating a model, that's also possible.

It's unlikely though that you'd ever want to do this.

```php
$model = new MyModel();

$model->uuid_text = $uuid;

$model->save();
```

### Querying the model

The most optimal way to query the database:

```php
$uuid = 'ff8683dc-cadd-11e7-9547-8c85901eed2e'; // UUID from eg. the URL.

$model = MyModel::withUuid($uuid)->first();
``` 

The `withUuid` scope will automatically encode the UUID string to query the database.
The manual approach would be something like this.

```php
$model = MyModel::where('uuid', MyModel::encodeUuid($uuid))->first();
```

You can also query for multiple UUIDs using the `withUuid` scope.

```php
$models = MyModel::withUuid([
    'ff8683dc-cadd-11e7-9547-8c85901eed2e',
    'ff8683ab-cadd-11e7-9547-8c85900eed2t',
])->get();
```

Note: Version 1.3.0 added simplified syntax for finding data using a uuid string.

```php
$uuid = 'ff8683dc-cadd-11e7-9547-8c85901eed2e'; // UUID from eg. the URL.

$model = MyModel::find($uuid);  

$model = MyModel::findOrFail($uuid);
```

Version 1.3.0 query for multiple UUIDs.

```php
$uuids = [
    'ff8683dc-cadd-11e7-9547-8c85901eed2e',
    'ff8683ab-cadd-11e7-9547-8c85900eed2t',
];

$model = MyModel::findMany($uuids);
``` 

#### Querying relations

You can also use the `withUuid` scope to query relation fields by specifying a field to query.

```php
$models = MyModel::withUuid('ff8683dc-cadd-11e7-9547-8c85901eed2e', 'relation_field')->get();

$models = MyModel::withUuid([
    'ff8683dc-cadd-11e7-9547-8c85901eed2e',
    'ff8683ab-cadd-11e7-9547-8c85900eed2t',
], 'relation_field')->get();
```

## Running the benchmarks

The package contains benchmarks that prove storing uuids in a tweaked binary form is really more performant. 

Before running the tests you should set up a MySQL database and specify the connection configuration in `phpunit.xml.dist`.

To run the tests issue this command.
```
phpunit -d memory_limit=-1 --testsuite=benchmarks
```

Running the benchmarks can take several minutes. You'll have time for several cups of coffee!


While the test are running average results are outputted in the terminal. After the tests are complete you'll find individual query stats as CSV files in the test folder.

You may use this data to further investigate the performance of UUIDs in your local machine.

Here are some results for the benchmarks running on our machine.

*A comparison of the normal ID, binary UUID and optimised UUID approach. Optimised UUIDs outperform all other on larger datasets.*

![Comparing different methods](./docs/comparison.png "Comparing different methods")

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email green2go@gmail.com instead of using the issue tracker.

## Credits

- [Brent Roose](https://github.com/brendt)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
