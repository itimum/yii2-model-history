Model History
=============
Model History

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist itimum/yii2-model-history "*"
```

or add

```
"itimum/yii2-model-history": "*"
```

to the require section of your `composer.json` file.

Migration
----

```
php yii migrate --migrationPath=@itimum/modelHistory/migrations
```

Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
public function behaviors() {
    return [
        [
            'class' => itimum\modelHistory\ModelHistoryBehavior::class
        ]
    ];
}
```