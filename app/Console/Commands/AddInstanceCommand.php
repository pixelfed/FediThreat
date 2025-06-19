<?php

namespace App\Console\Commands;

use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddInstanceCommand extends Command
{
    protected $signature = 'instance:add';

    protected $description = 'Interactively add a new Instance';

    public function handle(): int
    {
        $this->info('Adding a new Instance');

        $domain = text(
            label: 'Domain',
            placeholder: 'example.com',
            required: true,
            validate: ['domain' => 'required|max:255|unique:instances,domain']
        );
        $apiKey = text(label: 'API Key', default: Str::random(32), hint: '32-chars random key');
        $status = select(
            label: 'Status',
            options: [
                'active' => 'Active',
                'pending' => 'Pending',
                'suspended' => 'Suspended',
                'inactive' => 'Inactive',
            ],
        );

        $factory = new Instance;

        $instance = $factory->create([
            'domain' => $domain,
            'api_key' => $apiKey,
            'name' => $domain,
            'status' => $status,
            'verified_at' => $status === 'pending' ? null : now(),
            'last_seen_at' => now(),
        ]);

        $this->info("Instance [{$instance->id}] “{$instance->domain}” created successfully.");

        return self::SUCCESS;
    }
}
