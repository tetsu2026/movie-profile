<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function name_is_required(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make(['name' => ''], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_must_be_50_characters_or_less(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make(['name' => str_repeat('あ', 51)], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function name_with_50_characters_passes(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make(['name' => str_repeat('あ', 50)], $rules);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function biography_is_optional(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make(['name' => 'テスト', 'biography' => null], $rules);

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function biography_must_be_1000_characters_or_less(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'テスト',
            'biography' => str_repeat('あ', 1001),
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('biography', $validator->errors()->toArray());
    }

    /** @test */
    public function biography_with_1000_characters_passes(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'テスト',
            'biography' => str_repeat('あ', 1000),
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
