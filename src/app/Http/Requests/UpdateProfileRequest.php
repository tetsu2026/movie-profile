<?php

namespace App\Http\Requests;

use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'required|string|max:50',
            'biography' => 'nullable|string|max:1000',
            'thumbnail_video_id' => [
                'nullable',
                'integer',
                'exists:videos,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $video = Video::find($value);
                        // 所有権チェック
                        if (!$video || $video->user_id !== $this->user()->id) {
                            $fail('選択された動画が無効です');
                        }
                        // エンコード完了チェック
                        if ($video && $video->status !== 'completed') {
                            $fail('エンコードが完了していない動画は選択できません');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です',
            'name.max' => '名前は50文字以内で入力してください',
            'biography.max' => '経歴は1000文字以内で入力してください',
            'thumbnail_video_id.exists' => '選択された動画が見つかりません',
        ];
    }
}
