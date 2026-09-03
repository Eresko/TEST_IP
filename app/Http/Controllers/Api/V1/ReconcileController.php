<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderServices\ReconcileService; // Импортируем наш новый сервис
use Illuminate\Http\JsonResponse;
use Exception;

class ReconcileController extends Controller
{
    public function __construct(
        protected ReconcileService $reconcileService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/reconcile",
     *     summary="Мгновенная сверка финансовых данных и поиск аномалий",
     *     description="Выполняет математический аудит журнала двойной записи и ищет зависшие оплаченные заказы (Этап 4).",
     *     operationId="adminReconcile",
     *     tags={"Admin / Reconciliation"},
     *     @OA\Response(
     *         response=200,
     *         description="Сверка успешно выполнена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2026-09-03T17:35:10.000000Z"),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="ledger_is_consistent", type="boolean", example=true, description="Сошлась ли бухгалтерия в ноль"),
     *                 @OA\Property(property="ledger_difference_rub", type="number", format="float", example=0.00, description="Разница между депозитами и отгрузками в рублях")
     *             ),
     *             @OA\Property(
     *                 property="anomalies",
     *                 type="object",
     *                 @OA\Property(
     *                     property="paid_but_not_issued_ids",
     *                     type="array",
     *                     description="ID заказов, где деньги взяли, но товар не выдали в течение 5 минут",
     *                     @OA\Items(type="string", format="uuid", example="9eea6ba9-5ab1-46f1-9c06-d1a43a5ce71b")
     *                 ),
     *                 @OA\Property(
     *                     property="issued_but_not_paid_ids",
     *                     type="array",
     *                     description="ID заказов, которые отгружены, но в финансовом журнале нет проводки",
     *                     @OA\Items(type="string", format="uuid", example="27e31a4f-67a9-44e7-9e48-2c8fa2764db4")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера при проведении аудита",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Database connection timeout")
     *         )
     *     )
     * )
     */
    public function reconcile(): JsonResponse
    {
        try {
            $result = $this->reconcileService->start();

            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
