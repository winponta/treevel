<?php

namespace Winponta\Treevel\Traits;

/**
 * Trait to manipulate a Eloquent model
 * tree sctructure schema in a collection, using the parent references concepts
 *
 * @author Ademir Mazer Jr <ademir.mazer.jr@gmail.com>
 */
trait ParentTreeModel {

    /**
    * The name of the id field at parent table/collection.
    * If it's null then Eloquent PrimaryKey property is used on code
    * @var string
    */
    protected $parentIdField = null;

    /**
    * The name of the field in the Model that holds the parent id value reference
    * @var string
    */
    protected $parentField = 'parent_id';
    
    /**
    * The name of the field in the Model that holds the level of the node
    * @var string
    */
    protected $levelField = 'node_level';
    
    /**
     * @link http://www.archybold.com/blog/post/booting-eloquent-model-traits Based on post blog of Archybold.com
     */
    public static function bootParentTreeModel() {
    	self::creating(function ($model) {
            return $model->creatingHandler($model);
        });

        self::saving(function ($model) {
            return $model->savingHandler($model);
        });
        
        self::updating(function ($model) {
            return $model->updatingHandler($model);
        });
    }
    
    protected function setParentIdField($name) {
        $this->parentIdField = $name;
    }
    
    protected function setParentField($name) {
        $this->parentField = $name;
    }
	
    protected function setLevelField($name) {
        $this->levelField = $name;
    }
	
    protected function calculateLevelField() {
        if ($this->getAttribute($this->parentField) == null) {
            $this->attributes[$this->levelField] = 0;
        } else {
            $auxModel = $this;
            $l = 0;
            while ($auxModel->parent()->count() > 0) {
                $l++;
                $auxModel = $auxModel->parent;
            }
            $this->attributes[$this->levelField] = $l;
        }
    }

    public function updateLevel() {
        $this->calculateLevelField($this);
        $this->save();
    }
    
    protected function creatingHandler($model) {
        return $this->savingHandler($model);
    }
    
    protected function updatingHandler($model) {
        return $this->savingHandler($model);
    }
    
    protected function savingHandler($model) {
        if ($model->getAttribute($this->parentField)) {
			// If is set the name of the parent register that contains the value of identification
			// then use it over primaryKey
            if ($this->parentIdField) {
                $p = self::where($this->parentIdField, $model->getAttribute($this->parentField))->first();
            } else {
                $p = self::find($model->getAttribute($this->parentField));				
			}

            if ($p == null) {
                throw new \Exception('No register found with the id ' . $model->getAttribute($this->parentField));
                return false;
            }
        } else {
            $model->attributes[$this->parentField] = null;
        }

        $model->calculateLevelField();

        return true;
    }

	protected function getParentIdField() {
		return (is_null($this->parentIdField)) ? $this->primaryKey : $this->parentIdField;
	}

    /**
     * Returns a hasOne relation of parent for 
     * $this model id. 
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        //return $this->where($this->parentField, $this->getAttribute($this->primaryKey))->get();
        return $this->belongsTo(static::class, $this->parentField, $this->getParentIdField());
    }

    /**
     * Returns a hasMany relation of direct children of 
     * $this model id. 
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children() {
        //return $this->where($this->parentField, $this->getAttribute($this->primaryKey))->get();
        return $this->hasMany(static::class, $this->parentField, $this->getParentIdField());
    }

    /**
     * Returns a collection of direct children of 
     * $this model id. 
     *
     * @param array $options An array with options to be appplied when recovering descendants
     * <ul>
     * <li>where => [key, value] - recover only descendants the key matches the value
     * </ul>
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildren($options = []) {
        $children = self::where($this->parentField, $this->getAttribute($this->getParentIdField()))->get();

        if (array_key_exists('where', $options)) {
            $children = $children->where($options['where'][0], $options['where'][1]);
        }

        return $children;

    }

    /**
     * Returns a collection with all descendants of current Model
     *
     * @param array $options An array with options to be appplied when recovering descendants
     * <ul>
     * <li>where => [key, value] - recover only descendants the key matches the value
     * </ul>
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDescendants($options = []) {
        $children = $this->getChildren($options);

        foreach ($children as $item) {
            $item->setAttribute('children', $item->getDescendants($options));
        }

        return $children;
    }

    /**
     * Returns true if the model has direct children, false if not
     *
     * @param array $options An array with options to be appplied when recovering descendants
     * <ul>
     * <li>where => [key, value] - recover only descendants the key matches the value
     * </ul>
     *
     * @return boolean
     */
    public function hasChildren($options = []) {
        return self::getChildren($options)->count() > 0;
    }

    /**
     * Returns a collection with the siblings of current Model
     * 
     * @param boolean $excludeMe Exclude the actual node/register from the return.
     *                           If true (default) returns the siblings without the current Model,
     *                           else include it.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSiblings($excludeMe = true) {
        if (is_null($this->getAttribute($this->parentField))) {
            return $this->getRootNodes();
        } else {
            $col = $this->where($this->parentField, $this->getAttribute($this->parentField))
                    ->get();

            if ($excludeMe) {
                return $col->diff([$this]);
            } else {
                return $col;
            }
        }
    }

    /**
     * Returns a query builder where clause that filters query by all root nodes.
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeRootNodes($query) {
        return $query->where($this->parentField, null);
    }

    /**
     * Returns a collection with all the root nodes 
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRootNodes() {
        return self::rootNodes()->get();
    }

    /**
     * Returns the full tree from database
     *
     * @param array $options An array with options to be appplied when recovering descendants
     * <ul>
     * <li>where => [key, value] - recover only descendants the key matches the value
     * </ul>
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTree($options = []) {
        $roots = self::rootNodes()->get();

        foreach ($roots as $item) {
            $item->setAttribute('children', $item->getDescendants($options));
        }

        return $roots;
    }

    /**
     * Returns the full tree from database as a nested array
     *
     * @param array $options An array with options to be appplied when recovering descendants
     * <ul>
     * <li>where => [key, value] - recover only descendants the key matches the value
     * </ul>
     *
     * @return array
     */
    public static function getTreeArray($options = []) {
        $col = self::getTree($options);

        return self::treeToArray($col);
    }

    protected static function treeToArray($col) {
        $array = [];

        foreach ($col as $item) {
            $item = $item->toArray();

            if (empty($item['children']) === false) {
                $item['children'] = self::treeToArray($item['children']);
            }
            
            $array[] = $item;
        }
        
        return $array;
    }

}
