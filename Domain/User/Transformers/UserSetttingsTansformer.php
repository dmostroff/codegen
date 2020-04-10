<?php


namespace Domain\Donor\Transformers;


use Carbon\Carbon;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Domain\{User}\DTO\{DTO};
use Domain\{User}}\Entities\{};
use Illuminate\Foundation\Http\FormRequest;
use Support\DTO\DTO;
use Support\Entities\BaseEntity;
use Support\GlobalUtility;
use Support\Transformers\BaseTransformer;

class DonorTransformer extends BaseTransformer
{
    public static function transformEntityToView (?BaseEntity $entity): array
    {
        if (is_null($entity)) {
            return null;
        }

        return [
            'id'            => $entity->getId(),
            'userId'        => $entity->getUserId(),
            'prefix'        => $entity->getPrefix(),
            'keyName'       => $entity->getKeyName(),
            'keyValue'      => $entity->getKeyValue(),
        ];
    }

    public static function transformRequestToDTO (FormRequest $request): DTO
    {
        $entityDTO = new DTO();
        $entityDTO            ->setId($request->get('id'))
            ->setUserId($request->get('user_id'))
            ->setPrefix($request->get('prefix'))
            ->setKeyName($request->get('key_name'))
            ->setKeyValue($request->get('key_value'))
            ;

        return $entityDTO;
    }

    public static function transformDTOToEntity (DTO $entityDTO,  $ = null): BaseEntity
    {
        $entity = $ ?? new ();
        $entity            ->setId($DTO->getId())
            ->setUserId($DTO->getUserId())
            ->setPrefix($DTO->getPrefix())
            ->setKeyName($DTO->getKeyName())
            ->setKeyValue($DTO->getKeyValue())
            ;

        return $entity;
    }
}