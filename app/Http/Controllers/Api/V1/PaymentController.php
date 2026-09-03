<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebHookRequest;
use App\Services\OrderServices\PaymentService;
use App\Exceptions\BusinessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class PaymentController
 *
 * Обработка входящих транзакций и нотификаций от платежных шлюзов.
 */
class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/payments/webhook",
     *     summary="Вебхук приема оплаты от платежной системы",
     *     description="Обрабатывает транзакции от платежного шлюза. Имподентность",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Данные транзакции от платежного шлюза",
     *         @OA\JsonContent(
     *             required={"order_id", "payment_id"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="9c2d1b4a-8e3f-4b1a-9c2d-1b4a8e3f4b1a", description="UUID заказа в нашей системе"),
     *             @OA\Property(property="payment_id", type="string", maxLength=255, example="ch_3Mv8Y2LkdjuYxtv102Kls9a", description="Уникальный ID транзакции со стороны платежной системы")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная обработка ИЛИ контролируемая бизнес-ошибка ",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Успешный платеж",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Платеж успешно обработан")
     *                 ),
     *                 @OA\Schema(
     *                     title="Бизнес-ошибка (Заказ уже оплачен / Конфликт статуса)",
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="error", type="string", example="Заказ #9c2d1b4a-8e3f-4b1a-9c2d-1b4a8e3f4b1a не может повторно оплачен")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации структуры запроса (Невалидный UUID или пустые поля)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="array", @OA\Items(type="string", example="The order id field must be a valid UUID."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Критический инфраструктурный сбой (Падение БД, Redis, синтаксическая ошибка). Сигнализирует платежке о необходимости повторить запрос позже.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function webhook(WebHookRequest $request): JsonResponse
    {
        try {
            $this->paymentService->processPayment($request->toDto());

            return response()->json([
                'success' => true,
                'message' => 'Платеж успешно обработан'
            ], Response::HTTP_OK);

        } catch (BusinessException $e) {
            Log::warning('Webhook business error', [
                'message' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_OK);

        } catch (Throwable $e) {
            Log::critical('Webhook infrastructure crash', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
