<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TreeBuilder
{
    /**
     * @param  Collection<int, Model>  $models
     * @return Collection<int, Model>
     */
    public function build(
        Collection $models,
        string $parentKey = 'parent_id',
        string $childrenRelation = 'children',
        string $pathAttribute = 'tree_path',
    ): Collection {
        $modelsById = $models->keyBy(
            fn (Model $model): string => (string) $model->getKey(),
        );
        $roots = new Collection;

        $models->each(
            fn (Model $model) => $model->setRelation($childrenRelation, new Collection),
        );

        foreach ($models as $model) {
            $parentId = $model->getAttribute($parentKey);
            $parent = $parentId === null ? null : $modelsById->get((string) $parentId);

            if ($parent instanceof Model && $parent->getKey() !== $model->getKey()) {
                $parent->getRelation($childrenRelation)->push($model);

                continue;
            }

            $roots->push($model);
        }

        $roots = $roots->values();
        $this->assignPaths($roots, $childrenRelation, $pathAttribute);

        return $roots;
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    private function assignPaths(
        Collection $models,
        string $childrenRelation,
        string $pathAttribute,
        ?string $parentPath = null,
    ): void {
        foreach ($models->values() as $index => $model) {
            $position = (string) ($index + 1);
            $path = $parentPath === null ? $position : "{$parentPath}-{$position}";

            $model->setAttribute($pathAttribute, $path);

            if ($model->relationLoaded($childrenRelation)) {
                $this->assignPaths(
                    $model->getRelation($childrenRelation),
                    $childrenRelation,
                    $pathAttribute,
                    $path,
                );
            }
        }
    }
}
