<?php

namespace Domain$parentName\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Illuminate\Support\Collection;

/**
 * @method integer getId()
 * @method integer getUserId()
 * @method string getPrefix()
 * @method string getKeyName()
 * @method string getKeyValue()
*/
class UserSetttings
{
    use GettersAndSetters;
    use TimestampableEntity;

    protected integer $id;
    protected integer $userId;
    protected string $prefix;
    protected string $keyName;
    protected string $keyValue;
}
