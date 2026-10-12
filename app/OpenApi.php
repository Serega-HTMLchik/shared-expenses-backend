<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'Shared Expenses API', description: 'API для совместных расходов. Защищённые методы используют Sanctum Bearer token.')]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(securityScheme: 'sanctum', type: 'http', scheme: 'bearer', bearerFormat: 'API token')]
#[OA\Tag(name: 'Authentication')]
#[OA\Tag(name: 'Groups')]
#[OA\Tag(name: 'Expenses')]
#[OA\Tag(name: 'Balances')]
class OpenApi
{
    #[OA\Post(path: '/register', tags: ['Authentication'], summary: 'Регистрация пользователя', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'email', 'password', 'password_confirmation', 'device_name'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', format: 'password'), new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'), new OA\Property(property: 'device_name', type: 'string')])), responses: [new OA\Response(response: 201, description: 'Пользователь и токен созданы')])]
    public function register(): void {}

    #[OA\Post(path: '/login', tags: ['Authentication'], summary: 'Вход и выдача токена', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['email', 'password', 'device_name'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', format: 'password'), new OA\Property(property: 'device_name', type: 'string')])), responses: [new OA\Response(response: 200, description: 'Токен выдан'), new OA\Response(response: 422, description: 'Неверные данные')])]
    public function login(): void {}

    #[OA\Get(path: '/groups', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Список групп текущего пользователя', responses: [new OA\Response(response: 200, description: 'Список групп'), new OA\Response(response: 401, description: 'Не авторизован')])]
    public function groups(): void {}

    #[OA\Post(path: '/groups/{group}/expenses', tags: ['Expenses'], security: [['sanctum' => []]], summary: 'Добавить расход', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['payer_id', 'amount_cents', 'split_type', 'shares', 'spent_on'], properties: [new OA\Property(property: 'payer_id', type: 'integer'), new OA\Property(property: 'amount_cents', type: 'integer', example: 10001), new OA\Property(property: 'split_type', type: 'string', enum: ['equal', 'shares', 'exact']), new OA\Property(property: 'shares', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'spent_on', type: 'string', format: 'date')])), responses: [new OA\Response(response: 201, description: 'Расход создан')])]
    public function expense(): void {}
}
