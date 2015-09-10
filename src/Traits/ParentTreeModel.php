<?php

namespace Winponta\Treevel\Traits;

/**
 * Trait to manipulate a Eloquent model
 * tree sctructure schema in a collection, using the parent references concepts
 *
 * @author Ademir Mazer Jr <ademir.mazer.jr@gmail.com>
 */
trait ParentTreeModel {

    protected $parentField = 'parent_id';

    /**
     * @link http://www.archybold.com/blog/post/booting-eloquent-model-traits Based on post blog of Archybold.com
     */
    public static function bootParentTreeModel() {
        // register events
        self::creating(function ($model) {
            return $model->creatingHandler($model);
        });
    }

    public function creatingHandler($model) {
        if ($model->getAttribute($this->parentField)) {
            //$p = self::find(new \MongoId($model->getAttribute($this->parentField)));
            $p = self::find($model->getAttribute($this->parentField));

            if ($p == null) {
                throw new Exception('No register found with the id ' . $model->getAttribute($this->parentField));
                return false;
            }
        } else {
            $model->attributes[$this->parentField] = null;
        }


        //dd($this);
        return true;
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
        return $this->hasMany(static::class, $this->parentField, $this->primaryKey);
    }

    /**
     * Returns a collection of direct children of 
     * $this model id. 
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getChildren() {
        return self::where($this->parentField, $this->getAttribute($this->primaryKey))->get();
    }

    /**
     * Returns a collection with all descendants of current Model
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDescendants() {
        $children = $this->getChildren();

        foreach ($children as $item) {
            $item->setAttribute('children', $item->getDescendants());
        }

        return $children;
    }

    /**
     * Returns true if the model has direct children, false if not
     * 
     * @return boolean
     */
    public function hasChildren() {
        return self::getChildren()->count() > 0;
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTree() {
        $roots = self::rootNodes()->get();

        foreach ($roots as $item) {
            $item->setAttribute('children', $item->getDescendants());
        }

        return $roots;
    }

    public static function getTreeArray() {
        $col = self::getTree();

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
