<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PowerOutageSchedule>
 */
class PowerOutageScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduleData = [];
        $queues = ['1 черга', '2 черга', '3 черга', '4 черга', '5 черга', '6 черга'];

        foreach ($queues as $queue) {
            foreach ([1, 2] as $subqueue) {
                $scheduleData[] = [
                    'queue' => $queue,
                    'subqueue' => (string) $subqueue,
                    'hourly_status' => array_map(
                        fn () => fake()->randomElement(['on', 'off', 'maybe']),
                        range(1, 48)
                    ),
                ];
            }
        }

        $periods = [
            [
                'from' => '07:00',
                'to' => '16:00',
                'queues' => fake()->randomFloat(1, 1, 4),
            ],
            [
                'from' => '16:00',
                'to' => '23:59',
                'queues' => fake()->randomFloat(1, 1, 6),
            ],
        ];

        $data = [
            'description' => fake()->sentence(),
            'periods' => $periods,
            'schedule_data' => $scheduleData,
            'fetched_at' => now(),
        ];

        return [
            'schedule_date' => fake()->date(),
            'description' => $data['description'],
            'periods' => $periods,
            'schedule_data' => $scheduleData,
            'fetched_at' => now(),
            'hash' => md5(json_encode($data)),
        ];
    }
}
