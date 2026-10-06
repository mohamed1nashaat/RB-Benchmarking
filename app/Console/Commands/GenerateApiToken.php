<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-api-token
                            {email : The email address of the user}
                            {--name=API Access : The token name}
                            {--abilities=* : Abilities to grant to the token (default: *)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Sanctum API token for a user.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name');
        $abilities = $this->option('abilities') ?: ['*'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return self::FAILURE;
        }

        $token = $user->createToken($name, $abilities);

        $this->info('API token generated successfully.');
        $this->newLine();
        $this->line('User:    ' . $user->email);
        $this->line('Name:    ' . $name);
        $this->line('Abilities: ' . implode(', ', $abilities));
        $this->newLine();
        $this->warn('Token:   ' . $token->plainTextToken);
        $this->newLine();
        $this->line('Use this token in the Authorization header:');
        $this->line("Authorization: Bearer {$token->plainTextToken}");

        return self::SUCCESS;
    }
}
