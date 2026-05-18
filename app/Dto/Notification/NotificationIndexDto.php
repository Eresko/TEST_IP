<?php

namespace App\Dto\Notification;

use App\Enums\ChannelType;
use App\Enums\StatusMessage;
/**
 *  Для запроса сообщений c фильтрами
 *
 * @property StatusMessage          $status                     Статус сообщения
 * @property ChannelType            $chanel                     Канал
 * @property string                 $sort                       Сортировка
 * @property int                    $page                       Страница
 * @property int                    $perPage                    Кол-во элементов
 *
 */
class NotificationIndexDto {

    public function __construct(
        private  ?StatusMessage $status,
        private  ?ChannelType $chanel,
        private  ?string $sort,
        private  int $page = 1,
        private  int $perPage = 10,
    )
    {
    }

    /**
     * @return StatusMessage
     */
    public function getStatus(): ?StatusMessage {
        return $this->status;
    }

    /**
     * @return ChannelType
     */
    public function getChannel(): ?ChannelType {
        return $this->chanel;
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