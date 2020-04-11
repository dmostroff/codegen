<?php


namespace Domain\{0}\Transformers;


use Carbon\Carbon;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Domain\{0}\DTO\{UserDTO};
use Domain\{0}}\Entities\{User};
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
            ''              => $entity->get(),
        ];
    }

    public static function transformRequestToDTO (FormRequest $request): DTO
    {
        $entityDTO = new UserDTO();
        $entityDTO            ->set($request->get(''))
            ;

        return $entityDTO;
    }

    public static function transformDTOToEntity (DTO $entityDTO, User $user = null): BaseEntity
    {
        $entity = $user ?? new User();
        $entity            ->set($userDTO->get())
            ;

        return $entity;
    }
}