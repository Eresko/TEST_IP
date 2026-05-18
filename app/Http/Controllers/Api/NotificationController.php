<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\NotificationCreateRequest;
use App\Http\Requests\NotificationIndexRequest;
use App\Http\Requests\NotificationUpdateStatusRequest;
use App\Services\NotificationServices\NotificationService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    #[OA\Get(
        path: "/notifications",
        tags: ["Сообщения"],
        summary: "Получение списка сообщений с фильтрацией и пагинацией",
        operationId: "getNotifications",
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Фильтр по статусу",
                schema: new OA\Schema(type: "string", enum: ["waiting", "sent", "delivered","discarded"], example: "waiting")
            ),
            new OA\Parameter(
                name: "channel",
                in: "query",
                required: false,
                description: "Канал отправки",
                schema: new OA\Schema(type: "string", enum: ["sms", "email"], example: "email")
            ),
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
                description: "Сортировка",
                schema: new OA\Schema(type: "string", example: "status_asc")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Список сообщений с пагинацией",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 2007),
                                    new OA\Property(
                                        property: "user",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 57),
                                            new OA\Property(property: "name", type: "string", example: "Aniya Hackett"),
                                            new OA\Property(property: "phone", type: "integer", example: 89128060777),
                                            new OA\Property(property: "email", type: "string", example: "palma.skiles@example.com"),
                                            new OA\Property(property: "created_at", type: "string", example: "2025-07-06 11:33:05"),
                                            new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 05:31:36"),
                                        ]
                                    ),
                                    // Описание объекта Author
                                    new OA\Property(
                                        property: "author",
                                        type: "object",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Nestor Powlowski"),
                                            new OA\Property(property: "phone", type: "integer", example: 89128060777),
                                            new OA\Property(property: "email", type: "string", example: "heaven19@example.com"),
                                            new OA\Property(property: "created_at", type: "string", example: "2026-01-10 17:40:45"),
                                            new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 05:24:33"),
                                        ]
                                    ),
                                    new OA\Property(property: "status", type: "string", example: "waiting"),
                                    new OA\Property(property: "channel", type: "string", example: "email"),
                                    new OA\Property(property: "message", type: "string", example: "Привет! Это тестовое уведомление."),
                                    new OA\Property(property: "created_at", type: "string", example: "2026-05-02 06:26:54"),
                                    new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 06:26:54"),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "pagination",
                            type: "object",
                            properties: [
                                new OA\Property(property: "total", type: "integer", example: 100),
                                new OA\Property(property: "per_page", type: "integer", example: 10),
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "last_page", type: "integer", example: 10),
                            ]
                        )
                    ]
                )
            )
        ]
    )]

    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $productsPage = $this->notificationService->getByFilter($dto);

        return response()->json([
            'data' => NotificationResource::collection($productsPage->items()),
            'pagination' => [
                'total' => $productsPage->total(),
                'per_page' => $productsPage->perPage(),
                'current_page' => $productsPage->currentPage(),
                'last_page' => $productsPage->lastPage(),
            ],
        ]);
    }

    #[OA\Post(
        path: "/notifications",
        tags: ["Сообщения"],
        summary: "Создание нового сообщения",
        operationId: "createNotification",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["channel", "message"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", nullable: true, description: "ID получателя", example: 123),
                    new OA\Property(property: "author_id", type: "integer", nullable: true, description: "ID отправителя", example: 1),
                    new OA\Property(property: "message", type: "string", maxLength: 500, description: "Текст сообщения", example: "Привет! Это тестовое уведомление."),
                    new OA\Property(
                        property: "channel",
                        type: "string",
                        description: "Канал отправки",
                        enum: ["email", "sms"],
                        example: "email"
                    ),
                    new OA\Property(
                        property: "queue",
                        type: "string",
                        description: "Очередь обработки (приоритет доставки)",
                        enum: ["high", "default"],
                        default: "default",
                        example: "high"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Сообщение успешно создано и добавлено в очередь",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "id", type: "integer", example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Ошибка валидации",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The selected channel is invalid."),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function store(NotificationCreateRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $notificationResult = $this->notificationService->create($dto);
        $notificationResult->load(['user', 'author']);
        return response()->json([NotificationResource::make($notificationResult)],201);
    }


    #[OA\Get(
        path: "/notifications/{id}",
        tags: ["Сообщения"],
        summary: "Получение конкретного сообщения по ID",
        operationId: "getNotificationById",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Идентификатор сообщения",
                schema: new OA\Schema(type: "integer", example: 2007)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Данные сообщения",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 2007),
                        // Объект User
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 57),
                                new OA\Property(property: "name", type: "string", example: "Aniya Hackett"),
                                new OA\Property(property: "sms", type: "integer", example: 89128060777),
                                new OA\Property(property: "email", type: "string", example: "palma.skiles@example.com"),
                                new OA\Property(property: "created_at", type: "string", example: "2025-07-06 11:33:05"),
                                new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 05:31:36"),
                            ]
                        ),
                        // Объект Author
                        new OA\Property(
                            property: "author",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Nestor Powlowski"),
                                new OA\Property(property: "sms", type: "integer", example: 89128060777),
                                new OA\Property(property: "email", type: "string", example: "heaven19@example.com"),
                                new OA\Property(property: "created_at", type: "string", example: "2026-01-10 17:40:45"),
                                new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 05:24:33"),
                            ]
                        ),
                        new OA\Property(property: "status", type: "string", example: "delivered"),
                        new OA\Property(property: "channel", type: "string", example: "email"),
                        new OA\Property(property: "message", type: "string", example: "Привет! Это тестовое уведомление."),
                        new OA\Property(property: "created_at", type: "string", example: "2026-05-02 06:26:54"),
                        new OA\Property(property: "updated_at", type: "string", example: "2026-05-02 06:26:54"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Сообщение не найдено"
            )
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $notification = $this->notificationService->get($id);

        return response()->json(new NotificationResource($notification));
    }

    #[OA\Patch(
        path: "/notifications/{id}/status",
        tags: ["Сообщения"],
        summary: "Обновление статуса сообщения (Callback от внешних шлюзов)",
        operationId: "updateNotificationStatus",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID уведомления",
                schema: new OA\Schema(type: "integer", example: 2007)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "string",
                        description: "Новый статус сообщения",
                        enum: ["waiting", "sent", "delivered", "discarded"],
                        example: "delivered"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Статус успешно обновлен",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Status updated successfully")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Сообщение не найдено"),
            new OA\Response(response: 422, description: "Передан невалидный статус")
        ]
    )]
    public function updateStatus(NotificationUpdateStatusRequest $request, int $id): JsonResponse
    {

        $result = $this->notificationService->changeStatus($id, $request->input('status'));

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status or notification not found'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully'
        ], 200);
    }

}