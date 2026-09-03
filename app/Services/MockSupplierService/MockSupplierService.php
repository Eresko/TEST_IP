<?php

namespace App\Services\MockSupplierService;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Dto\MockSupplierBuyDto;

class MockSupplierService
{


    /**
     * @param MockSupplierBuyDto $dto
     * @return array<string, mixed>
     */
    public function processBuy(MockSupplierBuyDto $dto): array
    {


        $existingKey = DB::table('product_keys')->where('order_id', $dto->partnerOrderId)->first();
        if ($existingKey) {
            return ['status' => 'success', 'code' => $existingKey->key_code, 'http_code' => Response::HTTP_OK];
        }

        /**
         * Таймауты падения
         */
        $this->simulateChaos($dto->name);

        /**
         * После симуляции идем и разбираем ключи
         */
        return DB::transaction(function () use ($dto) {
            $key = DB::table('product_keys')
                ->where('sku', $dto->sku)
                ->whereNull('order_id')
                ->lockForUpdate()
                ->skipLocked() // @phpstan-ignore-line
                ->first();

            if (!$key) {
                return [
                    'status' => 'error',
                    'reason' => 'out_of_stock',
                    'http_code' => Response::HTTP_UNPROCESSABLE_ENTITY
                ];
            }


            DB::table('product_keys')
                ->where('id', $key->id)
                ->update([
                    'order_id'    => $dto->partnerOrderId,
                    'assigned_at' => now()
                ]);

            return ['status' => 'success', 'code' => $key->key_code, 'http_code' => Response::HTTP_OK];
        });
    }

    /**
     * @param string $orderId
     * @return array<string, mixed>
     */
    public function getStatus(string $orderId): array
    {
        $key = DB::table('product_keys')->where('order_id', $orderId)->first();

        if ($key) {
            return ['issued' => true, 'code' => $key->key_code];
        }

        return ['issued' => false, 'reason' => 'order_not_found'];
    }

    /**
     * Симуляция сбоев
     * @param string $supplierName
     * @return void
     * @throws Exception
     */
    private function simulateChaos(string $supplierName): void
    {
        $config = match ($supplierName) {
            'supplier_a' => ['timeout_rate' => 30, 'error_rate' => 40],
            'supplier_b' => ['timeout_rate' => 5,  'error_rate' => 10],
            default      => ['timeout_rate' => 0,  'error_rate' => 0]
        };

        $dice = rand(1, 100);


        if ($dice <= $config['timeout_rate']) {
            sleep(7);
        }


        if ($dice > $config['timeout_rate'] && $dice <= ($config['timeout_rate'] + $config['error_rate'])) {
            throw new Exception("Internal Server Error Simulation", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}