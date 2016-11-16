# treevel

## Installation

1. Require this package in your composer.json and run composer update (or run `composer require winponta/treevel` directly):

    "winponta/treevel": "0.*"

2. Run copmposer:

    `composer update`


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

##### Parent primary key (parentIdField)

This property is used to resolve the parent table primary key. If it's null the Eloquent primaryKey model property is used. You can change this value customizing the database field name your table/collection is using. Do this by setting the property calling the `setParentIdField` method in the model `__constructot`:

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

            public function __construct() {
                $this->setParentIdField( 'pk_on_parent_table' );
            }


##### Parent references  (parentField)

The default field name used by the package to handle the reference value to the parent record is named `parent_id`, you can change this value customizing the database field name your table/collection is using. Do this by setting the property calling the `setParentField` method in the model `__constructot`:

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

            public function __construct() {
                $this->setParentField( 'my_father_id' );
            }

##### Node level property  (levelField)

This property controls the deep level of the node on the tree. The default field value used to handle this feature is named `node_level`, you can change this value customizing the database field name your table/collection is using. Do this by setting the property calling the `setLevelField` method in the model `__constructot`:

    <?php
        ...
        class MyModel extends Eloquent {
            use \Winponta\Treevel\Traits\ParentTreeModel;

            public function __construct() {
                $this->setLevelField( 'depth' );
            }
