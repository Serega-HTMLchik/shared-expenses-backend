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
#[OA\Tag(name: 'Invitations')]
class OpenApi
{
    #[OA\Post(path: '/register', tags: ['Authentication'], summary: 'Регистрация пользователя', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'email', 'password', 'password_confirmation', 'device_name'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', format: 'password'), new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'), new OA\Property(property: 'device_name', type: 'string')])), responses: [new OA\Response(response: 201, description: 'Пользователь и токен созданы')])]
    public function register(): void {}

    #[OA\Post(path: '/login', tags: ['Authentication'], summary: 'Вход и выдача токена', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['email', 'password', 'device_name'], properties: [new OA\Property(property: 'email', type: 'string', format: 'email'), new OA\Property(property: 'password', type: 'string', format: 'password'), new OA\Property(property: 'device_name', type: 'string')])), responses: [new OA\Response(response: 200, description: 'Токен выдан'), new OA\Response(response: 422, description: 'Неверные данные')])]
    public function login(): void {}

    #[OA\Post(path: '/logout', tags: ['Authentication'], security: [['sanctum' => []]], summary: 'Отозвать текущий токен', responses: [new OA\Response(response: 204, description: 'Выход выполнен')])]
    public function logout(): void {}

    #[OA\Get(path: '/groups', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Список групп текущего пользователя', responses: [new OA\Response(response: 200, description: 'Список групп'), new OA\Response(response: 401, description: 'Не авторизован')])]
    public function groups(): void {}

    #[OA\Post(path: '/groups', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Создать группу', requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['name', 'currency'], properties: [new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'currency', type: 'string', example: 'RUB'), new OA\Property(property: 'reminder_after_days', type: 'integer', example: 7)])), responses: [new OA\Response(response: 201, description: 'Группа создана')])]
    public function createGroup(): void {}

    #[OA\Get(path: '/groups/{group}', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Получить группу и участников', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Группа'), new OA\Response(response: 404, description: 'Группа недоступна')])]
    public function showGroup(): void {}

    #[OA\Post(path: '/groups/{group}/close', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Закрыть группу', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Группа закрыта')])]
    public function closeGroup(): void {}

    #[OA\Post(path: '/groups/{group}/reopen', tags: ['Groups'], security: [['sanctum' => []]], summary: 'Открыть закрытую группу', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Группа открыта')])]
    public function reopenGroup(): void {}

    #[OA\Post(path: '/groups/{group}/invitations', tags: ['Invitations'], security: [['sanctum' => []]], summary: 'Создать приглашение по ссылке или email', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true), new OA\Property(property: 'expires_in_days', type: 'integer', example: 7)])), responses: [new OA\Response(response: 201, description: 'Возвращает accept_url; если указан email, приглашение отправлено письмом')])]
    public function invite(): void {}

    #[OA\Post(path: '/invitations/{token}/accept', tags: ['Invitations'], security: [['sanctum' => []]], summary: 'Принять приглашение', parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))], responses: [new OA\Response(response: 200, description: 'Участник добавлен'), new OA\Response(response: 410, description: 'Приглашение истекло или использовано')])]
    public function acceptInvitation(): void {}

    #[OA\Get(path: '/groups/{group}/balances', tags: ['Balances'], security: [['sanctum' => []]], summary: 'Балансы и минимальные переводы группы', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Балансы')])]
    public function groupBalances(): void {}

    #[OA\Get(path: '/balances', tags: ['Balances'], security: [['sanctum' => []]], summary: 'Балансы по всем группам', responses: [new OA\Response(response: 200, description: 'Балансы')])]
    public function allBalances(): void {}

    #[OA\Get(path: '/groups/{group}/expenses', tags: ['Expenses'], security: [['sanctum' => []]], summary: 'Список расходов группы', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Расходы')])]
    public function expenses(): void {}

    #[OA\Post(path: '/groups/{group}/expenses', tags: ['Expenses'], security: [['sanctum' => []]], summary: 'Добавить расход', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['payer_id', 'amount_cents', 'split_type', 'shares', 'spent_on'], properties: [new OA\Property(property: 'payer_id', type: 'integer'), new OA\Property(property: 'amount_cents', type: 'integer', example: 10001), new OA\Property(property: 'split_type', type: 'string', enum: ['equal', 'shares', 'exact']), new OA\Property(property: 'shares', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'spent_on', type: 'string', format: 'date')])), responses: [new OA\Response(response: 201, description: 'Расход создан')])]
    public function expense(): void {}

    #[OA\Put(path: '/groups/{group}/expenses/{expense}', tags: ['Expenses'], security: [['sanctum' => []]], summary: 'Изменить расход', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'expense', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Расход обновлён')])]
    public function updateExpense(): void {}

    #[OA\Delete(path: '/groups/{group}/expenses/{expense}', tags: ['Expenses'], security: [['sanctum' => []]], summary: 'Удалить расход', parameters: [new OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'expense', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Расход удалён')])]
    public function deleteExpense(): void {}
}
