<?php

namespace App\Http\Requests;

use getID3;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // authミドルウェアで認証済み
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // 100MB
        ];
    }

    /**
     * バリデーション後の追加チェック
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('video')) {
                $file = $this->file('video');

                // 動画の長さをチェック（getID3使用）
                $getID3 = new getID3;
                $fileInfo = $getID3->analyze($file->getRealPath());

                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = $fileInfo['playtime_seconds'];
                    if ($duration > 60) {
                        $validator->errors()->add('video', '動画の長さは1分以内にしてください');
                    }
                }
            }
        });
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'video.required' => '動画ファイルを選択してください',
            'video.file' => 'ファイルをアップロードしてください',
            'video.mimes' => '対応している動画形式はmp4, mov, avi, wmvです',
            'video.max' => 'ファイルサイズは100MB以内にしてください',
        ];
    }
}
