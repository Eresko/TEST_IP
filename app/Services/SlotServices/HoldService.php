<?php

namespace App\Services\SlotServices;

use App\Models\Hold;
use App\Models\Slot;
use App\Enums\HoldStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Cache;
use App\Events\HoldStatusUpdated;
class HoldService
{
    
    public function __construct(
        private SlotService $slotService
    ) {}

    /**
     * Подтверждение холда
     */
    public function confirmHold(int $holdId): void
    {
        DB::transaction(function () use ($holdId) {
            $holdBeforeLock = Hold::findOrFail($holdId);

            if ($holdBeforeLock->status === HoldStatus::CONFIRMED) {
                return;
            }

            if ($holdBeforeLock->status !== HoldStatus::HELD) {
                throw new HttpException(409, 'Можно подтвердить только холд в статусе held.');
            }

            $slot = Slot::where('id', $holdBeforeLock->slot_id)->lockForUpdate()->firstOrFail();

            $hold = Hold::where('id', $holdId)->lockForUpdate()->firstOrFail();

            if ($hold->status !== HoldStatus::HELD) {
                throw new HttpException(409, 'Статус холда изменился, пока ожидалась блокировка.');
            }

            if ($slot->remaining <= 0) {
                throw new HttpException(409, 'Конфликт: В слоте не осталось свободных мест (remaining = 0).');
            }

            $slot->decrement('remaining');

            $hold->update(['status' => HoldStatus::CONFIRMED]);
        });

        $this->slotService->invalidateAvailabilityCache();
    }

    /**
     * Отмена холда
     */
    public function cancelHold(int $holdId): void
    {
        DB::transaction(function () use ($holdId) {
            $holdBeforeLock = Hold::findOrFail($holdId);

            if ($holdBeforeLock->status === HoldStatus::CANCELLED) {
                return;
            }

            Slot::where('id', $holdBeforeLock->slot_id)->lockForUpdate()->firstOrFail();
            $hold = Hold::where('id', $holdId)->lockForUpdate()->firstOrFail();

            $hold->update(['status' => HoldStatus::CANCELLED]);
        });

        $this->slotService->invalidateAvailabilityCache();
    }

    /**
     * @param array $data
     * @return void
     */
    public function executeGeodistributedHold(array $data): void {

        $slotId = $data['slot_id'];
        $impotencyKey = $data['idempotency_key'];
        try {
            DB::transaction(function () use ($slotId, $impotencyKey) {
                $slot = Slot::where("id", $slotId)->lockForUpdate()->firstOrFail();
                if ($slot->remaining <= 0) {
                    throw new \Exception("В БД закончились столы");
                }
                $slot->decrement('remaining');
                Hold::create([
                        'slot_id' => $slotId,
                        'idempotency_key' => $impotencyKey,
                        'status' => HoldStatus::HELD,
                        'expired_at' => now()->addMinutes(5),
                        'response_data' => [
                            'slot_id' => $slotId,
                            'status' => HoldStatus::HELD->value,
                        ],
                    ]
                );

                $this->slotService->invalidateAvailabilityCache();
                event(new HoldStatusUpdated(
                    $impotencyKey,
                    HoldStatus::HELD->value,
                    'Место успешно забронировано'

                ));

            });

        }
        catch (\Exception $e) {
            Cache::increment("slots:{$slotId}:remaining");
            event(new HoldStatusUpdated(
                $impotencyKey,
                'failed',
                'Ошибка брони '. $e->getMessage()
            ));


        }

    }
}
