<?php

namespace App\Services\SlotServices;

use App\Dto\Slot\SlotAvailabilityDto;
use App\Dto\Slot\CreateHoldDto;
use App\Models\Slot;
use App\Models\Hold;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use App\Jobs\ProcessGeodistributedHold;
use Illuminate\Support\Facades\Redis;

class SlotService
{

    /**
     * Получение доступных слотов с защитой от Cache Stampede
     * @param SlotAvailabilityDto $dto
     * @return LengthAwarePaginator
     */
    public function getAvailableSlotsWithCache(SlotAvailabilityDto $dto): LengthAwarePaginator
    {

        $cacheKey = sprintf('slots_availability_page_%d_sort_%s', $dto->getPage(), $dto->getSort() ?? 'none');

        $cachedData = Cache::get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        try {

            return Cache::lock($cacheKey . '_lock', 5)->block(5, function () use ($cacheKey, $dto) {

                $data = Cache::get($cacheKey);
                if ($data !== null) {
                    return $data;
                }

                $data = $this->fetchFromDatabase($dto);

                $ttl = rand(5, 15);
                Cache::put($cacheKey, $data, now()->addSeconds($ttl));

                return $data;
            });
        } catch (LockTimeoutException $e) {

            return $this->fetchFromDatabase($dto);
        }
    }


    /**
     * Создание холда с защитой от оверсела
     * @param CreateHoldDto $dto
     * @return array<string, mixed>
     */
    public function createHold(CreateHoldDto $dto): array
    {
        $slotId = $dto->getSlotId();
        $idempotencyKey = $dto->getIdempotencyKey();
        $keyDbKey = "slots:{$slotId}:remaining";
        if (!Cache::has($keyDbKey)) {
            $slot = Slot::find($slotId);
            if (!$slot) {
                throw new HttpException(404, "Слот не найден");
            }

            Cache::put($keyDbKey, $slot->remaining, now()->addDay());
        }
        /**
         * Уменьшаем атомарно счетчик
         */
        $remaining = Cache::decrement($keyDbKey);

        if ($remaining < 0) {
            /**
             * Если ушли в минус
             */
            Cache::increment($keyDbKey);
            throw new HttpException(409,"Извините, все слоты в этом месте заняты");
        }

        ProcessGeodistributedHold::dispatch([
            'slot_id' => $slotId,
            'idempotency_key' => $idempotencyKey,
        ])->onQueue('hold_processing');

        return [
          'status' => 202,
          'data' => [
              'slot_id' => $slotId,
              'status' => 'pending',
              'message' => 'Ваш запрос обрабатывается. Сделедите за статусом'
          ]
        ];

    }




    /**
     *
     * Получение данных из БД
     * @param SlotAvailabilityDto $dto
     * @return LengthAwarePaginator
     */
    private function fetchFromDatabase(SlotAvailabilityDto $dto): LengthAwarePaginator
    {
        $query = Slot::query();

        if ($dto->getSort()) {
            [$field, $direction] = explode('_', $dto->getSort());
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->paginate(
            perPage: $dto->getPerPage() ?? 10,
            page: $dto->getPage()
        );
    }


    /**
     * Очистка всех страниц и сортировок
     * @return void
     */
    public function invalidateAvailabilityCache(): void
    {
        $redis = Redis::connection();
        $prefix = config('database.redis.options.prefix', '');

        // Ищем ключи по маске
        $pattern = $prefix . 'slots_availability_page_*';
        $keys = $redis->keys($pattern);

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $cleanKey = $key;
                if ($prefix !== '' && str_starts_with($key, $prefix)) {
                    $cleanKey = substr($key, strlen($prefix));
                }

                Cache::forget($cleanKey);
            }
        }
    }
}
