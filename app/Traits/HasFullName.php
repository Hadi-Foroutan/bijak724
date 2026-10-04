<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasFullName
{
    public static function bootHasFullName(): void
    {
        static::saving(function (Model $model): void {
            $model->setAttribute('full_name', $model->makeFullName());
        });
    }

    protected function makeFullName(): ?string
    {
        $fullName = Str::squish(
            (string) $this->getAttribute('first_name').' '.(string) $this->getAttribute('last_name'),
        );

        return $fullName === '' ? null : $fullName;
    }
}
