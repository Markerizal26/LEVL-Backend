<?php

declare(strict_types=1);

namespace Database\Factories;

use Bezhanov\Faker\Provider\Commerce;
use Bezhanov\Faker\Provider\Educator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        fake()->addProvider(new Educator(fake()));
        fake()->addProvider(new Commerce(fake()));
        
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $fullName = "$firstName $lastName";
        $username = strtolower($firstName . '.' . $lastName . rand(10, 999));

        $bioTemplates = [
            "Passionate {$this->getEducatorRole()} with {$this->getYearsExperience()} years of experience in education.",
            "Technology enthusiast and lifelong learner exploring new opportunities in online education.",
            "Dedicated to making quality education accessible to everyone. Love teaching and mentoring.",
            "Former {$this->getIndustryRole()} transitioning to education. Excited about learning new skills.",
            "Student at heart, always curious about {$this->getInterestArea()}. Believer in continuous improvement.",
        ];

        return [
            'name' => $fullName,
            'username' => $username,
            'email' => strtolower($firstName . '.' . $lastName . rand(100, 9999)) . '@' . fake()->safeEmailDomain(),
            'phone' => fake()->optional(0.7)->e164PhoneNumber(),
            'bio' => fake()->optional(0.6)->randomElement($bioTemplates),
            'password' => static::$password ??= Hash::make('password'),
            'status' => fake()->randomElement([
                UserStatus::Active->value,
                UserStatus::Pending->value,
                UserStatus::Inactive->value,
            ]),
            'email_verified_at' => fake()->boolean(80) ? now()->subDays(rand(1, 365)) : null,
            'is_password_set' => true,
            'account_status' => 'active',
            'last_profile_update' => fake()->optional(0.8)->dateTimeBetween('-6 months'),
            'remember_token' => Str::random(10),
        ];
    }
    
    private function getEducatorRole(): string
    {
        return fake()->randomElement([
            'educator', 'instructor', 'teacher', 'lecturer', 'tutor',
            'mentor', 'coach', 'trainer', 'facilitator'
        ]);
    }
    
    private function getYearsExperience(): int
    {
        return fake()->numberBetween(1, 20);
    }
    
    private function getIndustryRole(): string
    {
        return fake()->randomElement([
            'software engineer', 'data analyst', 'project manager', 'designer',
            'marketing specialist', 'business analyst', 'consultant'
        ]);
    }
    
    private function getInterestArea(): string
    {
        return fake()->randomElement([
            'technology', 'science', 'arts', 'business', 'languages',
            'programming', 'design', 'data science', 'digital marketing'
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'status' => UserStatus::Pending->value,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Pending->value,
            'email_verified_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Active->value,
            'email_verified_at' => now()->subDays(rand(1, 365)),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Inactive->value,
            'email_verified_at' => now()->subDays(rand(1, 365)),
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Banned->value,
            'email_verified_at' => now()->subDays(rand(1, 365)),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'deleted',
        ])->trashed();
    }

    public function passwordNotSet(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_password_set' => false,
            'password' => Hash::make(Str::random(12)),
        ]);
    }

    public function withCompleteProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->paragraph(nb_sentences: 5),
            'last_profile_update' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function withMinimalProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'bio' => null,
            'last_profile_update' => null,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (!$user->trashed()) {
                $user->gamificationStats()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'total_points' => 0,
                        'current_streak' => 0,
                        'longest_streak' => 0,
                        'total_badges' => 0,
                        'completed_challenges' => 0,
                    ]
                );
            }
        });
    }
}
