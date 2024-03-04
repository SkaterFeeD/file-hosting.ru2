<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileStoreRequest;
use App\Models\File;
use App\Models\Right;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    // скачивание файла
    public function download($file_id)
    {
        $file = File::where('file_id', $file_id)->first();
        if (!$file) {
            // файл не найден
            return response()->json(['message' => 'Not found'], 404);
        }
        // доступ к файлу
        if (!auth()->check() || auth()->user()->id !== $file->user_id) {
            // доступ запрещен
            return response()->json(['message' => 'Forbidden for you'], 403);
        }
        // получаем путь к файлу
        $filePath = storage_path('app/uploads/' . $file->path);
        if (!Storage::exists('uploads/' . $file->path)) {
            return response()->json(['message' => 'Not found'], 404);
        }
        // вовзращение файла
        return response()->download($filePath, $file->name . '.' . $file->extension);
    }

    // добавление
    public function store(FileStoreRequest $request)
    {
        // проверка есть ли файл
        if ($request->hasFile('files')) {
            $uploadedFiles = [];
            foreach ($request->file('files') as $file) {
                // валидация
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                // уник имя файла
                $fileName = $this->generateUniqueFileName($originalName, $extension);
                // сохранение файла на сервере
                $file->storeAs('uploads', $fileName);
                // создание записи в бд
                $uploadedFile = new File();
                $uploadedFile = auth()->user()->files()->create([
                    'name' => pathinfo($originalName, PATHINFO_FILENAME),
                    'extension' => $extension,
                    'path' => $fileName,
                    'file_id' => Str::random(10),
                    'user_id' => auth()->id(),
                ]);
                $uploadedFile->save();

                $uploadedFiles[] = [
                    'success' => true,
                    'code' => 200,
                    'message' => 'Success',
                    'name' => $originalName,
                    'url' => url("files/{$uploadedFile->id}"),
                    'file_id' => $uploadedFile->file_id
                ];
            }
            return response()->json($uploadedFiles);
        }
        // файла нет
        return response()->json(['message' => 'No files to upload'], 400);
    }
    // уникальное имя
    private function generateUniqueFileName($originalName, $extension)
    {
        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $i = 1;
        while (Storage::exists("uploads/{$fileName}.{$extension}")) {
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . " ({$i})";
            $i++;
        }
        return $fileName . '.' . $extension;
    }

    // редактирование
    public function edit(Request $request, $file_id)
    {
        // существует файл
        $file = File::where('file_id', $file_id)->first();
        // не найден
        if (!$file) {
            return response()->json(['message' => 'Not found'], 404);
        }
        // проверка доступа
        if (!auth()->check() || auth()->user()->id !== $file->user_id) {
            // доступ запрещен
            return response()->json(['message' => 'Forbidden for you'], 403);
        }
        $file->name = $request->input('name');
        $file->save();

        // ответ
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Renamed'
        ]);
    }

    // удаление
    public function destroy($file_id)
    {
        // существует файл
        $file = File::where('file_id', $file_id)->first();
        // не найден
        if (!$file) {
            return response()->json(['message' => 'Not found'], 404);
        }
        // проверка доступа
        if (!auth()->check() || auth()->user()->id !== $file->user_id) {
            // доступ запрещен
            return response()->json(['message' => 'Forbidden for you'], 403);
        }
        // удаление файла
        Storage::delete('uploads/' . $file->path);

        $file->delete();
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'File deleted'
        ]);
    }

    // просмотр файлов
    public function owned(Request $request)
    {
        $userId = $request->user()->id;
        $files = File::where('user_id', $userId)->with('rights.user')->get();
        // формируем ответ
        $response = [];
        foreach ($files as $file) {
            $accesses = [];
            foreach ($file->rights as $right) {
                $accesses[] = [
                    'fullname' => $right->user->full_name,
                    'email' => $right->user->email,
                    'type' => 'co-author',
                ];
            }
            $response[] = [
                'file_id' => $file->file_id,
                'name' => $file->name,
                'code' => 200,
                'url' => url("files/{$file->file_id}"),
                'accesses' => $accesses
            ];
        }
        // возврат ответа
        return response()->json($response, 200);
    }

    // просмотр файлов, к которым имеет доступ пользователь
    public function allowed()
    {
        $userId = auth()->id();
        $filesWithAccess = Right::where('user_id', $userId)->with('file')->get()->pluck('file');
        $response = [];
        foreach ($filesWithAccess as $file) {
            if ($file->user_id != $userId) {
                $response[] = [
                    'file_id' => $file->file_id,
                    'code' => 200,
                    'name' => $file->name,
                    'url' => url("files/{$file->file_id}")
                ];
            }
        }
        // возврат ответа
        return response()->json($response, 200);
    }
}
