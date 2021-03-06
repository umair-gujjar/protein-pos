<?php

namespace App\DataObjects;

use App\DataObjects\Decorators\Decorator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ModelDataObjects
 *
 * @package App\DataObjects
 */
abstract class ModelDataObjects extends BaseDataObject
{
    /** @var array */
    protected $guarded = ['created_by', 'updated_by'];
    /** @var array */
    protected $eagerLoaded = [];
    /** @var Model */
    protected $model;

    /** @param Model $model */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /** @return Model */
    public function getModel()
    {
        return $this->model;
    }

    /** {@inheritDoc} */
    public function serializeMember()
    {
        $attributes = $this->model->getAttributes();

        foreach ($this->guarded as $guarded) {
            if (array_key_exists($guarded, $attributes)) {
                unset($attributes[$guarded]);
            }
        }

        foreach ($this->eagerLoaded as $relationProperty => $data) {
            $this->model->load($relationProperty);

            if ($this->model->{$relationProperty}) {
                $eagerLoadedModel = (new $data['dataObject']($this->model->{$relationProperty}));

                if (isset($data['decorators']) && is_array($data['decorators'])) {
                    foreach ($data['decorators'] as $decorator) {
                        $eagerLoadedModel->addDecorator($decorator);
                    }
                }

                $attributes[$relationProperty] = $eagerLoadedModel->toArray();;
            }

            unset($attributes[$data['property']]);
        }

        if ($this->model->timestamps) {
            unset($attributes[$this->model->getCreatedAtColumn()]);
            unset($attributes[$this->model->getUpdatedAtColumn()]);
        }

        if (isset(class_uses_recursive($this->model)[SoftDeletes::class])) {
            unset($attributes[$this->model->getDeletedAtColumn()]);
        }

        return $attributes;
    }

    public function addEagerLoadDecorator($attributeKey, Decorator $decorator)
    {
        if (isset($this->eagerLoaded[$attributeKey])) {
            if (!isset($this->eagerLoaded[$attributeKey]['decorators'])) {
                $this->eagerLoaded[$attributeKey]['decorators'] = [];
            }

            $this->eagerLoaded[$attributeKey]['decorators'][] = $decorator;
        }
    }
}