<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--name=Admin} {--email=admin@example.com} {--password=admin123}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать пользователя-администратора';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        // Проверяем, существует ли уже пользователь с таким email
        if (User::where('email', $email)->exists()) {
            $this->error("Пользователь с email {$email} уже существует!");
            return 1;
        }

        // Создаем пользователя
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Пользователь-администратор успешно создан!");
        $this->line("Имя: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Пароль: {$password}");
        $this->warn("Обязательно смените пароль после первого входа!");

        return 0;
    }
}
