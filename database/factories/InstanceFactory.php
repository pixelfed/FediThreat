<?php

namespace Database\Factories;

use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InstanceFactory extends Factory
{
    protected $model = Instance::class;

    public function definition(): array
    {
        return [
            'domain' => $this->faker->domainName,
            'api_key' => Str::random(32),
            'name' => $this->faker->company,
            'software' => $this->faker->randomElement(['pixelfed', 'mastodon', 'pleroma', 'misskey']),
            'software_version' => $this->faker->semver,
            'total_users' => $this->faker->numberBetween(100, 10000),
            'admin_email' => $this->faker->safeEmail,
            'status' => 'active',
            'verified_at' => now(),
            'last_seen_at' => now(),
            'settings' => [
                'reporting_threshold' => 3,
                'auto_block' => false,
                'notify_on_high_risk' => true,
                'allowed_reporters' => ['admin', 'moderator']
            ],
            'metadata' => [
                'country' => $this->faker->countryCode,
                'language' => $this->faker->languageCode,
                'registration_policy' => $this->faker->randomElement(['open', 'approval', 'closed']),
                'server_stats' => [
                    'storage' => $this->faker->numberBetween(1000, 100000),
                    'media_attachments' => $this->faker->numberBetween(100, 10000),
                    'status_count' => $this->faker->numberBetween(1000, 100000)
                ]
            ],
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Instance is pending verification
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null
        ]);
    }

    /**
     * Instance is suspended
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $this->faker->randomElement([
                'spam',
                'abuse',
                'tos_violation',
                'admin_request'
            ])
        ]);
    }

    /**
     * Instance has high activity
     */
    public function highActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_users' => $this->faker->numberBetween(50000, 200000),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'server_stats' => [
                    'storage' => $this->faker->numberBetween(100000, 1000000),
                    'media_attachments' => $this->faker->numberBetween(50000, 200000),
                    'status_count' => $this->faker->numberBetween(1000000, 5000000)
                ]
            ])
        ]);
    }

    /**
     * Instance is configured for strict moderation
     */
    public function strictModeration(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], [
                'reporting_threshold' => 1,
                'auto_block' => true,
                'notify_on_high_risk' => true,
                'allowed_reporters' => ['admin']
            ])
        ]);
    }

    /**
     * Instance hasn't been seen recently
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'last_seen_at' => now()->subDays(30)
        ]);
    }

    /**
     * Instance is a Pixelfed instance
     */
    public function pixelfed(): static
    {
        return $this->state(fn (array $attributes) => [
            'software' => 'pixelfed',
            'software_version' => $this->faker->semver,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'features' => [
                    'stories' => true,
                    'collections' => true,
                    'direct_messages' => true
                ]
            ])
        ]);
    }

    /**
     * Instance has reported many threats
     */
    public function withReports(int $count = 5): static
    {
        return $this->has(
            \App\Models\Report::factory()
                ->count($count)
                ->sequence(fn ($sequence) => [
                    'created_at' => now()->subHours($sequence->index)
                ])
        );
    }
}
