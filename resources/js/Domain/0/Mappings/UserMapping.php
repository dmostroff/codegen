<?php

namespace Domain\{0}\Mappings;

use LaravelDoctrine\Fluent\EntityMapping;
use LaravelDoctrine\Fluent\Fluent;

class ContactAddressMapping extends EntityMapping
{
    /**
     * @return string
     */
    public function mapFor()
    {
        return User::class;
    }

    /**
     * @param Fluent $builder
     */
    public function map(Fluent $builder)
    {
                $builder->;
    }
}