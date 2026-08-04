<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ConfirmHoldRequest;
use App\Services\SlotServices\HoldService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
class HoldController extends Controller
{
    public function __construct(
        private HoldService $holdService
    ) {}

    #[OA\Post(
        path: "/holds/{id}/confirm",
        tags: ["Холды"],
        summary: "Подтверждение холда",
        operationId: "confirmHold",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Холд успешно подтвержден"),
            new OA\Response(response: 409, description: "Конфликт: нет свободных мест или холд не в статусе held"),
            new OA\Response(response: 404, description: "Холд не найден")
        ]
    )]
    public function confirm(ConfirmHoldRequest $request): JsonResponse
    {
        $holdId = (int)$request->route('id');

        $this->holdService->confirmHold($holdId);

        return response()->json([
            'status' => 'success',
            'message' => 'Холд успешно подтвержден'
        ], 200);
    }

    #[OA\Delete(
        path: "/holds/{id}",
        tags: ["Холды"],
        summary: "Отмена холда",
        operationId: "cancelHold",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Холд успешно отменен"),
            new OA\Response(response: 404, description: "Холд не найден")
        ]
    )]
    public function cancel(ConfirmHoldRequest $request): JsonResponse
    {
        $holdId = (int)$request->route('id');

        $this->holdService->cancelHold($holdId);

        return response()->json([
            'status' => 'success',
            'message' => 'Холд успешно отменен'
        ], 200);
    }
}
