<?php

namespace Database\Factories;

use App\Models\Homeowner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Homeowner>
 */
class HomeownerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Homeowner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof'];
        $title = $this->faker->randomElement($titles);

        // 70% chance of having a first name, 30% chance of just having an initial
        $hasFirstName = $this->faker->boolean(70);

        return [
            'title' => $title,
            'first_name' => $hasFirstName ? $this->faker->firstName() : null,
            'initial' => $hasFirstName ? null : strtoupper($this->faker->randomLetter()),
            'last_name' => $this->faker->lastName(),
        ];
    }

    /**
     * Create a homeowner with only a title and last name
     */
    public function titleOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => null,
            'initial' => null,
        ]);
    }

    /**
     * Create a homeowner with a specific title
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Create a homeowner with first name (no initial)
     */
    public function withFirstName(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => $this->faker->firstName(),
            'initial' => null,
        ]);
    }

    /**
     * Create a homeowner with initial (no first name)
     */
    public function withInitial(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => null,
            'initial' => strtoupper($this->faker->randomLetter()),
        ]);
    }

    /**
     * Create a homeowner with a hyphenated last name
     */
    public function withHyphenatedLastName(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_name' => $this->faker->lastName().'-'.$this->faker->lastName(),
        ]);
    }

    /**
     * Create a Doctor
     */
    public function doctor(): static
    {
        return $this->withTitle('Dr');
    }

    /**
     * Create a Professor
     */
    public function professor(): static
    {
        return $this->withTitle('Prof');
    }

    /**
     * Create a Mr
     */
    public function mr(): static
    {
        return $this->withTitle('Mr');
    }

    /**
     * Create a Mrs
     */
    public function mrs(): static
    {
        return $this->withTitle('Mrs');
    }

    /**
     * Create a Ms
     */
    public function ms(): static
    {
        return $this->withTitle('Ms');
    }

    /**
     * Create a couple with the same last name
     */
    public function couple(): static
    {
        $lastName = $this->faker->lastName();

        return $this->state(fn (array $attributes) => [
            'last_name' => $lastName,
        ]);
    }
}
