<?php

namespace App\Http\Controllers;

use App\Traits\HomeTrait;
use App\Interfaces\IService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ContactRequest;
use App\Interfaces\ICartService;
use Illuminate\Support\Facades\Session;

/**
 * Контроллер для работы с главной страницей
 *
 * Содержит методы для генерации страниц
 *
 * @author Inara Durdyeva <inara97_97@mail.ru>
 * @copyright Copyright (c) Inara Durdyeva
 */
class HomeController extends Controller
{
    use HomeTrait;

    private IService $serviceImpl;
    private ICartService $cartService;

    public function __construct(IService $serviceImpl, ICartService $cartService)
    {
        $this->serviceImpl = $serviceImpl;
        $this->cartService = $cartService;
    }

    /**
     * Индексная страница
     */
    public function index()
    {
        $data = $this->getDataForIndexPage();
        return view('frontend.index', $data);
    }

    /**
     * Получает список услуг
     *
     * @param Request $request - Get-запрос
     * @return View
     */
    public function getListServices()
    {
        $services = $this->serviceImpl->getServices();
        return view('client.services', ['services' => $services]);
    }

    /**
     * Метод для добавления услуги в корзину
     *
     * @param Request $request - запрос
     * @return JsonResponse - возвращает JSON с общим количеством услуг в корзине
     */
    public function addToCart(Request $request): JsonResponse
    {
        $id = $request['serviceId'];
        $cart = $this->cartService->addCartToItem($id);
        $request->session()->put('cart', $cart);
        return response()->json(['count' => $cart->totalQty]);
    }

    /**
     * Получение списка услуг в корзине
     *
     * @return View возвращает страницу со списком заказанных услуг
     */
    public function getShoppingCart()
    {
        if (!Session::has('cart')) {
            return view('frontend.shoppingCartView');
        }
        $cartInfo = $this->cartService->getCartInfo();
        return view('frontend.shoppingCartView', $cartInfo);
    }

    /**
     * Уменьшение на 1 позицию в корзине
     * @param Request $request Запрос с параметром заказа
     */
    public function reduceItemRequest(Request $request): JsonResponse
    {
        $id = $request['orderId'];
        $results = $this->cartService->reduceItem($id);
        return response()->json(['updated_results' => $results], 200);
    }

    /**
     * Увеличение на 1 позицию в корзине
     * @param $request Запрос с параметром заказа
     */
    public function increaseItemRequest(Request $request): JsonResponse
    {
        $id = $request['orderId'];
        $updated_results = $this->cartService->increaseItem($id);
        return response()->json(['updated_results' => $updated_results], 200);
    }

    /**
     * Удаление услуги полностью
     *
     * @param Request $request - post запрос на удаление услуги из списка
     * @return  JsonResponse Ответ об удалении в JSON-формате
     */
    public function deleteItemRequest(Request $request): JsonResponse
    {
        $id = $request['orderId'];
        $results = $this->cartService->deleteItem($id);
        return response()->json(['updated_results' => $results], 200);
    }

    /**
     * @param ContactRequest $request Запрос с параметрами для связи
     * @return JsonResponse Добавлена ли запись или нет
     */
    public function contactRequest(ContactRequest $request): JsonResponse
    {
        $result = false;
        if ($request->validated()) {
            $result = $this->addContactMe($request);
        }
        return response()->json($result);
    }
}
