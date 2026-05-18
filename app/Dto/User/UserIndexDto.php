<?php

namespace App\Dto\User;

use App\Enums\ChannelType;
use App\Enums\StatusMessage;
/**
 *  Для запроса пользователей c фильтрами
 *
 * @property string|null            $name                     Имя пользователя
 * @property string|null            $sort                     Сортировка
 * @property int                    $page                     Страница
 * @property int                    $perPage                  Кол-во элементов
 *
 */
class UserIndexDto {

    public function __construct(
        private  ?string $name,
        private  ?string $sort,
        private  int $page = 1,
        private  int $perPage = 10,
    )
    {
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

     /**
     * @return string|null
     */
    public function getSort(): ?string {
        return $this->sort;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int {
        return $this->page;
    }

    /**
     * @return int|null
     */
    public function getPerPage(): ?int {
        return $this->perPage;
    }

}