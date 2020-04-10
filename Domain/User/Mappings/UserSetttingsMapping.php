<?php

namespace Domain\{User}\Mappings;

use LaravelDoctrine\Fluent\EntityMapping;
use LaravelDoctrine\Fluent\Fluent;

class ContactAddressMapping extends EntityMapping
{
    /**
     * @return string
     */
    public function mapFor()
    {
        return ::class;
    }

    /**
     * @param Fluent $builder
     */
    public function map(Fluent $builder)
    {
                $builder->integer;
        $builder->integer;
        $builder->string->length(32);
        $builder->string->length(32);
        $builder->text->length(4294967295);
    }
}