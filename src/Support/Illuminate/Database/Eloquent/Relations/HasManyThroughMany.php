<?php 
namespace Support\Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class HasManyThroughMany extends Relation
{
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $localKey, $throughKey)
    {
        $this->localKey = $localKey;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;
        $this->throughKey = $throughKey;
        $this->farParentRelatedKey = $this->getModelRelatedKey($this->farParent);
        // dd($localKey, $firstKey, $secondKey, $throughKey);
        parent::__construct($query, $parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $parentTable = $this->parent->getTable();

        $this->setJoin();
        if (static::$constraints && !$this->throughKey) {
            $localValue = $this->farParent[$this->localKey];
            $this->query->where($parentTable.'.'.$this->firstKey, '=', $localValue);
        } else {
            $localValue = $this->farParent['id'];
            $this->query->where($this->firstKey . '.' . $this->farParentRelatedKey, '=', $localValue);
        }

    }

    protected function setJoin(Builder $query = null)
    {

        $query = $query ?: $this->query;

        $foreignKey = $this->related->getTable().'.'.$this->secondKey;
        $farParentTable = $this->farParent->getTable();
        $farParentTableKey = $farParentTable.'.'.$this->localKey;
        $firstKey = $this->parent->getTable().'.'.$this->firstKey;

        $id = $this->related->getTable() . '.id as id';
        $all = $this->related->getTable() . '.*';
        $columns = [$id, $all];
        if(!$this->throughKey){
            $query->addSelect($columns)->join($this->parent->getTable(), $this->getQualifiedParentKeyName(), '=', $foreignKey)
            ->join($farParentTable,$farParentTableKey, '=', $firstKey);
        } else {
            $query->addSelect($columns)
            ->join($this->secondKey, $this->secondKey . '.' . $this->localKey, '=', $this->related->getTable() . '.id')
            ->join($this->firstKey, $this->firstKey . '.' . $this->throughKey, '=', $this->secondKey . '.' . $this->throughKey)
            ->join($farParentTable,$farParentTable . '.id', '=', $this->firstKey . '.' . $this->farParentRelatedKey);
        }
        if ($this->parentSoftDeletes()) {
            $query->whereNull($this->parent->getQualifiedDeletedAtColumn());
        }

    }

    public function getModelRelatedKey($model)
    {
        return strtolower(class_basename($model)) . '_id';
    }

    public function getForeignKey()
    {
        return $this->related->getTable().'.'.$this->secondKey;
    }

    public function parentSoftDeletes()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_class($this->parent)));
    }

    public function addEagerConstraints(array $models){}
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }
    public function match(array $models, Collection $results, $relation){}
    public function getResults()
    {
        return $this->get();
    }    
}