<?php

namespace App\Traits;

use App\Role;
use App\User;
use App\Order;
use App\Status;
use App\Comment;
use App\UserInRole;
use App\ExecutorInOrder;
use App\Traits\ClientTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Трэйт, содержащий методы для работы администраторской части
 * 
 * Содержит методы, возвращающие необходимые данные для работы страниц администраторской части
 * 
 * @author Inara Durdyeva <inara97_97@mail.ru>
 * @copyright Copyright (c) Inara Durdyeva
 */
trait AdminTrait
{

    use ClientTrait;
    /**
     * Получение списка пользователей в системе
     * 
     * @param Request $request - Get Запрос
     * @return array - массив параметров для отображения
     */
    private function getAllUsers(Request $request): array
    {
        $users = array();
        if ($request->has('roleId')) {
            $roleId = $request->get('roleId');
            $user_in_roles = UserInRole::where(['role_id' => $roleId])->pluck('user_id')->toArray();
            $users = User::whereIn('id', $user_in_roles)->paginate(12);
        } else {
            $users = User::paginate(12);
        }
        $roles = Role::all();
        return [
            'users' => $users,
            'roles' => $roles
        ];
    }

    /**
     * Получение списка заявок для администора
     * 
     * @param Request $request Get запрос
     * @return array Список заказов
     */
    private function getAllRequests(Request $request): array
    {
        $statusId = $request->get('statusId');
        $clientId = $request->get('clientId');
        $executorId = $request->get('executorId');

        //Получение списка клиентов
        $clientsId = Order::distinct('user_id')->pluck('user_id')->toArray();
        $allClients = User::whereIn('id', $clientsId)->get();

        //Получение списка заказов
        $executorsId = ExecutorInOrder::distinct('user_id')->pluck('user_id')->toArray();
        $allExecutors = User::whereIn('id', $executorsId)->get();

        $orders = null;
        if ($statusId == 'new') {
            $orders = Order::where(['status_id' => null]);
        } else {
            $statusId == null ?  $orders = Order::where('status_id', '!=', null)
                : $orders = Order::where(['status_id' => $statusId]);
        }

        if ($clientId != null) {
            $orders = $orders->where(['user_id' => $clientId]);
        }
        if ($executorId != null) {
            $executorOrders = ExecutorInOrder::where(['user_id' => $executorId])->pluck('order_id');
            $orders = $orders->whereIn('id', $executorOrders);
        }
        $orders = $orders->paginate(12);

        $statuses = Status::all();
        $parsedOrders = [];
        foreach ($orders as $order) {
            $parsedOrders[] = $this->parseOrder($order);
        }
        return [
            'orders' => $parsedOrders,
            'orderPaginate' => $orders,
            'statuses' => $statuses,
            'allClients' => $allClients,
            'allExecutors' => $allExecutors
        ];
    }

    /**
     * Отображение информации об 1 заявке
     */
    private function getOrderById(int $id): array
    {
        $order = Order::find($id);
        $parsedOrders = $this->parseOrder($order);
        $executors = $order->executors;
        $comments = Comment::where([
            'order_id' => $order != null ? $order->id : null
        ])->paginate(12);
        $availableExecutors = $this->getExecutors();
        $availableExecutors = $availableExecutors->whereNotIn(
            'id',
            $executors->pluck('id')->toArray()
        );
        $statuses = Status::all();
        return [
            'order' => $parsedOrders,
            'executors' => $executors,
            'availableExecutors' => $availableExecutors,
            'comments' => $comments,
            'statuses' => $statuses
        ];
    }

    /**
     * Получение списка пользователей с ролью "Исполнитель"
     */
    private function getExecutors()
    {
        $roleExecutor = Role::where(['name' => 'executor'])->first();
        $availableExecutors = null;

        if ($roleExecutor != null) {
            $user_in_roles = UserInRole::where(['role_id' => $roleExecutor->id])->get();
            if (!$user_in_roles->isEmpty()) {
                $usersId = $user_in_roles->pluck('user_id')->toArray();
                $availableExecutors = User::whereIn('id', $usersId)->get();
            }
        }

        return $availableExecutors;
    }

    /**
     * Назначение исполнителя на заявку
     * 
     * @param int $orderId Номер заявки
     * @param int $userId Исполнитель
     * @return bool Назначен ли исполнитель
     */
    private function assignExecutorToOrder(int $orderId, int $userId): bool
    {
        //Результат выполнения операции
        $response = false;

        //Назначен ли уже такой исполнитель на заявку
        $recordCount = ExecutorInOrder::where([
            'user_id' => $userId,
            'order_id' => $orderId
        ])->count();

        //Является ли назначаемый пользователь исполнителем
        $roleExecutor = Role::where(['name' => 'executor'])->first();
        $userInRole = UserInRole::where([
            'user_id' => $userId,
            'role_id' => $roleExecutor->id
        ])->count();

        if ($recordCount == 0 && $userInRole > 0) {
            $response = ExecutorInOrder::create([
                'order_id' => $orderId,
                'user_id' => $userId
            ])->save();
        }
        return $response;
    }

    /**
     * Убрать исполнителя из заявки
     * 
     * @param int $orderId Номер заказа
     * @param int $userId Id пользователя
     */
    private function revokeUserFromOrder(int $orderId, int $userId): bool
    {
        $result =  ExecutorInOrder::where([
            'order_id' => $orderId,
            'user_id' => $userId
        ])->delete();
        return $result;
    }

    /**
     * Создание пользователя в систем администратором
     * 
     * @param $request POST-запрос с параметрами пользователя
     * @return bool Результат создания пользователя
     */
    private function postUser($request): bool
    {
        $resultCreation = false;
        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'address' => $request->get('address'),
            'organization' => $request->get('organization'),
            'phone_number' => $request->get('phone_number'),
            'password' => Hash::make($request->get('password'))
        ]);
        $resultCreation = $user->save();
        return $resultCreation;
    }

    /**
     * Удаление пользователя
     */
    private function deleteUser(int $id): bool
    {
        $deleteResult = false;
        $deleteUser = User::find($id);
        if ($deleteUser) {
            $deleteResult = $deleteUser->delete();
        }
        return $deleteResult;
    }

    /**
     * Удаления комментария
     * 
     * @param int $commentId Номер комментария
     * @return bool Удален ли комментарий
     */
    private function deleteComment(int $commentId): bool
    {
        $resultOperation = false;
        $comment = Comment::find($commentId);
        if ($comment) {
            $resultOperation = $comment->delete();
        }
        return $resultOperation;
    }

    /**
     * Получение информации о пользователе
     * 
     * @param int $userId Id пользователя
     * @return array Пользователь со списком ролей в базе
     */
    private function getUserInfo(int $userId): array
    {
        $user = User::find($userId);
        $roles = Role::all();
        return [
            'user' => $user,
            'roles' => $roles
        ];
    }

    /**
     * Реализация добавления роли для пользователя
     * 
     * @param int $userId Id пользователя
     * @param int $roleId Номер роли
     * @return string Результат добавления
     */
    private function grantRoleToUser(int $userId, int $roleId): string
    {
        $resultGrant = '';
        $userInRole = UserInRole::where(['user_id' => $userId, 'role_id' => $roleId])->count();
        if ($userInRole > 0) {
            $resultGrant = 'existed';
        } else {
            $newUserInRole = UserInRole::create(['user_id' => $userId, 'role_id' => $roleId])->save();
            $newUserInRole == true ? $resultGrant = 'created' : $result = 'error';
        }
        return $resultGrant;
    }

    /**
     * Удаление пользователя из роли
     * @param int $userId Id пользователя
     * @param int $roleId Номер роли
     * @return bool Результат операции
     */
    private function revokeRole(int $userId, int $roleId): bool
    {
        $result = false;
        $userInRole = UserInRole::where(['user_id' => $userId, 'role_id' => $roleId]);
        if ($userInRole) {
            $result = $userInRole->delete();
        }
        return $result;
    }

    /**
     * Удаление заявки
     * 
     * @param int $id Номер заявки
     * @return bool Результат удаления
     */
    private function deleteRequestById(int $id): bool
    {
        $resultOperation = false;
        $order = Order::find($id);
        if ($order) {
            $resultOperation = $order->delete();
        }
        return $resultOperation;
    }
}
