<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Services\OrderServices\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Class OrderController
 *
 * Управляет жизненным циклом заказов.
 *
 * @package App\Http\Controllers\Api
 */
class OrderController extends Controller
{

    /**
     * OrderController constructor.
     *
     * @param OrderService $orderService
     */
    public function __construct(protected OrderService $orderService)
    {

    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Создание нового заказа",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku"},
     *             @OA\Property(property="sku", type="string", maxLength=50, example="PROD-123", description="Уникальный идентификатор товара")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Заказ успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Заказ успешно создан"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=152),
     *                 @OA\Property(property="sku", type="string", example="PROD-123"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="price_cents", type="integer", example=15000),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-02T09:36:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации входных данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="sku", type="array", @OA\Items(type="string", example="The sku field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера или бизнес-логики",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Произошла непредвиденная ошибка на сервере.")
     *         )
     *     )
     * )
     */
    public function create(CreateOrderRequest $request): JsonResponse
    {

        try {
            $sku = $request->input('sku');
            $order = $this->orderService->createOrder($sku);

            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно создан',
                'data' => [
                    'order_id' => $order->id,
                    'sku' => $order->sku,
                    'status' => $order->status,
                    'price_cents' => $order->price_cents,
                    'created_at' => $order->created_at,
                ]
            ], 201);

        } catch (Exception $e) {
            $code = (int) $e->getCode();
            
            $httpStatus = ($code >= 400 && $code <= 599) ? $code : 500;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], $httpStatus);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Получение информации о конкретном заказе",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Идентификатор заказа",
     *         @OA\Schema(type="string", example="9c2d1b4a-8e3f-4b1a-9c2d-1b4a8e3f4b1a")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Информация о заказе успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=152),
     *                 @OA\Property(property="sku", type="string", example="PROD-123"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="price_cents", type="integer", example=15000),
     *                 @OA\Property(property="supplier_id", type="integer", nullable=true, example=42),
     *                 @OA\Property(property="issued_product_code", type="string", nullable=true, example="XYZ-9876"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-02T09:36:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-09-02T09:45:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Заказ не найден")
     *         )
     *     )
     * )
     */

    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->getById($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Заказ не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'sku' => $order->sku,
                'status' => $order->status,
                'price_cents' => $order->price_cents,
                'supplier_id' => $order->supplier_id,
                'issued_product_code' => $order->issued_product_code,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]
        ], 200);
    }

    
}
