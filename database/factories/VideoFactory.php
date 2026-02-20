<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => fake()->word() . '.mp4',
            'original_path' => 'users/1/original/' . fake()->uuid() . '.mp4',
            'encoded_path' => null,
            'thumbnail_path' => null,
            'duration' => fake()->numberBetween(10, 60),
            'file_size' => fake()->numberBetween(1000000, 50000000),
            'status' => 'uploading',
            'retry_count' => 0,
            'error_message' => null,
        ];
    }

    /**
     * エンコード中の状態
     */
    public function encoding(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'encoding',
            'original_path' => 'users/1/original/test.mp4',
        ]);
    }

    /**
     * エンコード完了の状態
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'encoded_path' => 'users/1/encoded/' . fake()->uuid() . '.mp4',
        ]);
    }

    /**
     * エンコード失敗の状態
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'retry_count' => 3,
            'error_message' => 'FFmpegエラー: Conversion failed',
        ]);
    }
}
