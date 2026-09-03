<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\MockSupplierBuyRequest;
use App\Http\Requests\MockSupplierStatusRequest;
use App\Services\MockSupplierService\MockSupplierService;
use Throwable;
class MockSupplierController extends Controller
{

    public function __construct(protected MockSupplierService $supplierService)
    {

    }
    /**
     * @OA\Post(
     *     path="/api/v1/mock-supplier/{name}/buy",
     *     summary="Эмуляция покупки/выдачи цифрового товара шлюзом поставщика",
     *     description="Эндпоинт для интеграции с поставщиком. Поддерживает строгую идемпотентность по partner_order_id. Имитирует таймауты (30% для supplier_a) и жесткие сбои (40% для supplier_a).",
     *     tags={"Mock Supplier"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Имя поставщика (supplier_a или supplier_b)",
     *         @OA\Schema(type="string", enum={"supplier_a", "supplier_b"}, example="supplier_a")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"partner_order_id", "sku"},
     *             @OA\Property(property="partner_order_id", type="string", format="uuid", example="9c2d1b4a-8e3f-4b1a-9c2d-1b4a8e3f4b1a", description="UUID заказа нашей системы"),
     *             @OA\Property(property="sku", type="string", maxLength=50, example="STEAM-GTA5-KEY", description="SKU цифрового товара")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная генерация или возврат ранее выданного ключа (Идемпотентный ответ)",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="SUPPLIER_A_ABCD_1234_XYZ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Невалидная структура запроса",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="reason", type="string", example="bad_request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Товар закончился в пуле поставщика (Out of stock)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="reason", type="string", example="out_of_stock")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Симуляция внутреннего сбоя шлюза поставщика",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="reason", type="string", example="internal_server_error")
     *         )
     *     )
     * )
     */

    public function buy(MockSupplierBuyRequest $request): JsonResponse
    {

        try {
            $result = $this->supplierService->processBuy($request->toDto());
            if ($result['status'] === 'error') {
                return response()->json(['status' => 'error', 'reason' => $result['reason']], $result['http_code']);
            }

            return response()->json(['code' => $result['code']], Response::HTTP_OK);

        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'reason' => 'internal_server_error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/mock-supplier/{name}/status",
     *     summary="Эмуляция проверки статуса выдачи у поставщика (Анти-таймаут)",
     *     description="Используется сервисом доставки для проверки, был ли сгенерирован код во время предыдущего таймаута сети.",
     *     tags={"Mock Supplier"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Имя поставщика (supplier_a или supplier_b)",
     *         @OA\Schema(type="string", example="supplier_a")
     *     ),
     *     @OA\Parameter(
     *         name="partner_order_id",
     *         in="query",
     *         required=true,
     *         description="UUID заказа в нашей системе (выступает ключом идемпотентности)",
     *         @OA\Schema(type="string", format="uuid", example="9c2d1b4a-8e3f-4b1a-9c2d-1b4a8e3f4b1a")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ от шлюза поставщика",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Код успешно найден (Был выдан ранее)",
     *                     @OA\Property(property="issued", type="boolean", example=true),
     *                     @OA\Property(property="code", type="string", example="SUP_A_GTA5_X9F2_KL8Z")
     *                 ),
     *                 @OA\Schema(
     *                     title="Заказ не найден (Выдачи не было)",
     *                     @OA\Property(property="issued", type="boolean", example=false),
     *                     @OA\Property(property="reason", type="string", example="order_not_found")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос (отсутствуют обязательные параметры)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="reason", type="string", example="bad_request")
     *         )
     *     )
     * )
     */

    public function status(MockSupplierStatusRequest $request): JsonResponse
    {

        $result = $this->supplierService->getStatus($request->input('partner_order_id'));

        if (!empty($result['issued'])) {
            return response()->json([
                'issued' => true,
                'code'   => $result['code']
            ], Response::HTTP_OK);
        }

        return response()->json([
            'issued' => false,
            'reason' => $result['reason'] ?? 'order_not_found'
        ], Response::HTTP_OK);
    }
}

