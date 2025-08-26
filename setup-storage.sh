#!/bin/bash

# Создаем необходимые директории
mkdir -p storage/app/public/photos
mkdir -p storage/app/public/passports
mkdir -p storage/app/public/certificates
mkdir -p storage/app/public/uploads/people

# Устанавливаем права доступа
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Создаем символическую ссылку для storage
php artisan storage:link

echo "Storage directories created and permissions set!"
