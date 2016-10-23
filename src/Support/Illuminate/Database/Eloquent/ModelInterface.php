<?php

namespace Support\Illuminate\Database\Eloquent;

interface ModelInterface {

    /**
     * mapping for the fields
     * @return array mapping array
     */
    public static function mappings();
}