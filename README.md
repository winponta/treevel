# treevel

## Installation

1. Require this package in your composer.json and run composer update (or run `composer require winponta/treevel` directly):

    "winponta/treevel": "0.*"

2. Run copmposer:

    composer update


## Using

Enable your models to be ready to handle tree hierarchy records, using one of the traits of the package (for now only Parent tree model is available).

### Parent tree model

Parent tree models are handled using `parent id` references.

#### Trait

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

#### Default properties

##### Parent id references

The default field name used by the package to handle the reference to parent record is named `parent_id`, you can change this value customizing the database field name your table/collection is using. Do this by overwriting the `$parentField` propertie:

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

            protected $parentField = 'my_father_id';

    
