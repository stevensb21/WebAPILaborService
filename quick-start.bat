@echo off
echo 🚀 Быстрый запуск WebAPI Labor Service...

REM Проверка Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker не установлен!
    pause
    exit /b 1
)

REM Запуск контейнеров
echo 📦 Запуск контейнеров...
docker-compose up -d

REM Ожидание запуска
echo ⏳ Ожидание запуска сервисов...
timeout /t 5 /nobreak >nul

REM Проверка статуса
echo 📊 Статус контейнеров:
docker-compose ps

echo.
echo ✅ Проект запущен!
echo.
echo 🌐 Веб-интерфейс: http://localhost:8081
echo 📚 API документация: http://localhost:8081/api-test.html
echo 🗄️  База данных: localhost:5432
echo.
echo 📋 Команды управления:
echo   - Остановка: docker-compose down
echo   - Логи: docker-compose logs -f
echo   - Перезапуск: docker-compose restart
echo.
pause
