# treevel

## Installation

1. Require this package in your composer.json and run composer update (or run `composer require winponta/treevel` directly):

    "winponta/treevel": "0.*"

## Using

To enable your models to be ready to handle tree hierarchy records, just use one of the traits (for now only Parent tree model is avaiable):

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

