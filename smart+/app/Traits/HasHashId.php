<?php

namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;

trait HasHashId
{
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (empty($field) || $field === $this->getKeyName()) {
            $decoded = Hashids::decode($value);
            if (empty($decoded)) {
                return null;
            }
            return $this->where($this->getKeyName(), $decoded[0])->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
