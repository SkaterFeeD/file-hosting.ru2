<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Right;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RightController extends Controller
{
    // права доступа
    public function add(Request $request, $file_id)
    {
        // Проверка, авторизован ли пользователь
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // Поиск файла по его ID
        $file = File::where('file_id', $file_id)->first();

        // Проверка, существует ли файл
        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Проверка, является ли пользователь владельцем файла
        if ($file->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Валидация входящего запроса
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        // Если валидация не прошла, возвращаем ошибку
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        // Поиск пользователя по email
        $user = User::where('email', $request->email)->first();

        // Создание записи в базе данных для нового права доступа
        $right = new Right();
        $right->file_id = $file->id;
        $right->user_id = $user->id;
        $right->save();

        // Получаем список оставшихся пользователей, которые ещё имеют доступ к файлу
        $response = $this->usersAccess($file);

        return response()->json($response, 200);
    }

    // Удаление прав доступа
    public function destroy(Request $request, $file_id)
    {
        // Находим файл
        $file = File::where('file_id', $file_id)->first();

        // Проверяем, что пользователь авторизован и является владельцем файла
        if ($request->user()->id !== $file->user_id) {
            return response()->json(['message' => 'Forbidden for you'], 403);
        }

        // Находим пользователя, которого нужно удалить из списка соавторов
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Удаляем запись о праве доступа к файлу для указанного пользователя
        $right = Right::where('file_id', $file->id)->where('user_id', $user->id)->first();

        if ($right) {
            $right->delete();
        } else {
            return response()->json(['message' => 'User has no access to this file'], 404);
        }

        // Получаем список оставшихся пользователей, которые ещё имеют доступ к файлу
        $response = $this->usersAccess($file);

        return response()->json($response);
    }

    // Список пользователей, имеющих доступ к файлу
    public function usersAccess($file) {
        // Получение всех пользователей, имеющих доступ к файлу
        $rights = Right::where('file_id', $file->id)->with('user')->get();

        // Добавление автора файла
        $author = $file->user;
        $response[] = [
            'fullname' => $author->full_name,
            'email' => $author->email,
            'type' => 'author',
            'code' => 200,
        ];

        // Добавление соавторов файла
        foreach ($rights as $access) {
            $user = $access->user;
            $response[] = [
                'fullname' => $user->full_name,
                'email' => $user->email,
                'type' => 'co-author',
                'code' => 200,
            ];
        }

        return $response;
    }
}
