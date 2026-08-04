<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\AvailabilityIndexRequest;
use App\Http\Requests\CreateHoldRequest;
use App\Services\SlotServices\SlotService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\SlotResource;
use OpenApi\Attributes as OA;

class AvailabilityController extends Controller
{
    public function __construct(private SlotService  $slotService)
    {
    }

    #[OA\Get(
        path: "/slots/availability",
        tags: ["Слоты"],
        summary: "Получение доступных слотов",
        operationId: "getSlotsAvailability",
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "sort",
                in: "query",
                required: false,
                description: "Сортировка (например: id_asc, id_desc, remaining_asc, remaining_desc)",
                schema: new OA\Schema(type: "string", example: "id_asc")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Список доступных слотов с пагинацией",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "slot_id", type: "integer", example: 1),
                                    new OA\Property(property: "capacity", type: "integer", example: 10),
                                    new OA\Property(property: "remaining", type: "integer", example: 6),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "pagination",
                            type: "object",
                            properties: [
                                new OA\Property(property: "total", type: "integer", example: 45),
                                new OA\Property(property: "per_page", type: "integer", example: 10),
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "last_page", type: "integer", example: 5),
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function getSlotsAvailability(AvailabilityIndexRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $slotsPage = $this->slotService->getAvailableSlotsWithCache($dto);

        return response()->json([
            'data' => SlotResource::collection($slotsPage->items()),
            'pagination' => [
                'total' => $slotsPage->total(),
                'per_page' => $slotsPage->perPage(),
                'current_page' => $slotsPage->currentPage(),
                'last_page' => $slotsPage->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: "/slots/{id}/hold",
        tags: ["Холды"],
        summary: "Создание холда (бронирования места)",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "Idempotency-Key", in: "header", required: true, schema: new OA\Schema(type: "string", format: "uuid"))
        ],
        responses: [
            new OA\Response(response: 201, description: "Холд успешно создан"),
            new OA\Response(response: 409, description: "Конфликт: нет свободных мест"),
            new OA\Response(response: 400, description: "Некорректный UUID в заголовке")
        ]
    )]
    public function hold(CreateHoldRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        
        $holdResult = $this->slotService->createHold($dto);

        return response()->json($holdResult['data'], $holdResult['status']);
    }
}