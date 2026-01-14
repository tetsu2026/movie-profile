<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 管理者のみ使用（adminミドルウェアで保護済み）
        return true;
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
            'thumbnail_video_id' => 'nullable|exists:videos,id',
            'role' => 'required|in:admin,user',
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
            'role.required' => '権限を選択してください',
            'role.in' => '権限はadminまたはuserから選択してください',
        ];
    }
}
