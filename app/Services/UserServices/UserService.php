<?php

declare(strict_types=1);

namespace App\Services\UserServices;

use App\Dto\User\UserIndexDto;
use App\Models\User;
use App\Traits\HasSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Сервис для работы с пользователями.
 */
class UserService
{
    use HasSort;

    /**
     * @param int $userId
     * @return User|null
     */
    public function findById(int $userId): ?User
    {
        return User::find($userId);
    }

    /**
     * @param UserIndexDto $dto
     * @return LengthAwarePaginator
     */
    public function list(UserIndexDto $dto): LengthAwarePaginator
    {
        return User::query()
            ->when($dto->getName(), fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"))

            ->tap(fn (Builder $q) => $this->applySort($q, $dto->getSort()))

            ->paginate($dto->getPerPage(), ['*'], 'page', $dto->getPage());
    }
}
