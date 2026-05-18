<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\UserIndexRequest;
use App\Services\UserServices\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    #[OA\Get(
        path: "/users",
        tags: ["Пользователи"],
        summary: "Получение списка пользователей с фильтрацией и пагинацией",
        operationId: "getUsers",
        parameters: [
            new OA\Parameter(
                name: "name",
                in: "query",
                required: false,
                description: "Имя",
            ),
            new OA\Parameter(
                name: "sort",
                in: "query",
                required: false,
                description: "Сортировка",
                schema: new OA\Schema(type: "string", example: "name_asc")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Страница",
                schema: new OA\Schema(type: "integer", example: 1)
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
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "user_id", type: "integer", nullable: true, example: 123),
                                    new OA\Property(property: "message", type: "string", example: "Ваш код подтверждения: 1234"),
                                    new OA\Property(property: "channel", type: "string", example: "email"),
                                    new OA\Property(property: "status", type: "string", example: "pending"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2025-09-25T00:24:43Z"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2025-09-25T00:24:43Z"),
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
            ),
            new OA\Response(
                response: 422,
                description: "Ошибка валидации параметров"
            )
        ]
    )]
    public function index(UserIndexRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $productsPage = $this->userService->list($dto);

        return response()->json([
            'data' => UserResource::collection($productsPage->items()),
            'pagination' => [
                'total' => $productsPage->total(),
                'per_page' => $productsPage->perPage(),
                'current_page' => $productsPage->currentPage(),
                'last_page' => $productsPage->lastPage(),
            ],
        ]);
    }
}