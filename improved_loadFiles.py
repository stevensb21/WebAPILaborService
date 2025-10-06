import os
import requests
import asyncio
from pathlib import Path

# Конфигурация
BASE_URL = "http://127.0.0.1:8000"

async def loadFiles(people_id, file_path):
    """
    Загружает и обрабатывает файлы для конкретного человека
    
    Args:
        people_id: ID человека в системе
        file_path: Путь к файлу или директории для обработки
    
    Returns:
        dict: Результат обработки с информацией о найденных людях
    """
    try:
        # Проверяем, существует ли файл/директория
        if not os.path.exists(file_path):
            return {
                "error": f"Файл или директория не найдены: {file_path}",
                "success": False
            }
        
        # Проверяем, что файл существует и это PDF
        if not file_path.lower().endswith('.pdf'):
            return {
                "error": "Файл должен быть в формате PDF",
                "success": False
            }
        
        print(f"📁 Загружаем файл: {file_path}")
        print(f"👤 ID сотрудника: {people_id}")
        
        # Проверяем доступность файла
        if not os.access(file_path, os.R_OK):
            return {
                "error": f"Файл недоступен для чтения: {file_path}",
                "success": False
            }
        
        # Получаем API токен
        api_token = os.getenv("API_TOKEN")
        if not api_token:
            return {
                "error": "API_TOKEN не установлен в переменных окружения",
                "success": False
            }
        
        headers = {
            'Authorization': f'Bearer {api_token}',
            'Accept': 'application/json'
        }
        
        print(f"🔍 Токен: {api_token[:10]}...")
        
        # Проверяем доступность API
        print("🔍 Проверяем доступность API...")
        try:
            # Проверяем общий endpoint
            general_resp = requests.get(
                f"{BASE_URL}/api/people/compact?limit=1",
                headers=headers,
                timeout=10
            )
            print(f"🔍 Общий endpoint статус: {general_resp.status_code}")
            
            if general_resp.status_code != 200:
                print(f"❌ Проблема с общим API: {general_resp.status_code}")
                return {
                    "error": f"API недоступен: {general_resp.status_code}",
                    "success": False
                }
            else:
                print("✅ API доступен, переходим к загрузке файла")
                        
        except Exception as e:
            print(f"⚠️ Проблема с API: {e}")
            return {
                "error": f"Ошибка подключения к API: {e}",
                "success": False
            }
        
        # Проверяем размер файла
        file_size = os.path.getsize(file_path)
        print(f"📏 Размер файла: {file_size} байт")
        
        # Проверяем лимит файла (200MB согласно коду)
        max_size = 200 * 1024 * 1024  # 200MB
        if file_size > max_size:
            print("⚠️ Файл слишком большой для загрузки")
            return {
                "error": f"Файл слишком большой: {file_size} байт (лимит: 200MB)",
                "success": False
            }
        
        # Загружаем файл
        print("🔄 Загружаем файл...")
        
        try:
            with open(file_path, 'rb') as file:
                files = {
                    'certificates_file': (
                        os.path.basename(file_path),
                        file,
                        'application/pdf'
                    )
                }
                
                # Увеличиваем таймаут для больших файлов
                timeout = max(60, file_size // (1024 * 1024) * 2)  # 2 секунды на MB
                
                resp = requests.post(
                    f"{BASE_URL}/api/people/{people_id}/certificates-file",
                    files=files,
                    headers=headers,
                    timeout=timeout
                )
            
            print(f"📤 Статус ответа: {resp.status_code}")
            
            # Логируем ответ для отладки
            try:
                response_data = resp.json()
                print(f"📋 Ответ сервера: {response_data}")
            except:
                print(f"📋 Ответ сервера (текст): {resp.text}")
            
            if resp.status_code == 200:
                if response_data.get('success'):
                    print(f"✅ Файл успешно загружен: {response_data['data']['filename']}")
                    return {
                        "success": True,
                        "message": "Файл успешно загружен",
                        "filename": response_data['data']['filename'],
                        "response": response_data
                    }
                else:
                    return {
                        "success": False,
                        "error": f"Ошибка сервера: {response_data.get('message', 'Неизвестная ошибка')}",
                        "response": response_data
                    }
            else:
                return {
                    "success": False,
                    "error": f"HTTP {resp.status_code}: {response_data.get('message', resp.text)}",
                    "response": response_data if 'response_data' in locals() else resp.text
                }
                
        except requests.exceptions.Timeout:
            return {
                "success": False,
                "error": "Превышено время ожидания загрузки файла",
                "success": False
            }
        except Exception as e:
            return {
                "success": False,
                "error": f"Ошибка при загрузке файла: {str(e)}",
                "success": False
            }
            
    except Exception as e:
        return {
            "error": f"Ошибка при обработке файлов: {str(e)}",
            "success": False
        }

async def search_employees(name):
    """Поиск сотрудников по имени"""
    try:
        api_token = os.getenv("API_TOKEN")
        if not api_token:
            return None
            
        headers = {
            'Authorization': f'Bearer {api_token}',
            'Accept': 'application/json'
        }
        
        # Используем compact API для поиска
        response = requests.get(
            f"{BASE_URL}/api/people/compact",
            headers=headers,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                people = data.get('data', [])
                
                # Ищем по имени (регистронезависимый поиск)
                name_lower = name.lower()
                for person in people:
                    if name_lower in person.get('full_name', '').lower():
                        print(f"✅ Найден сотрудник: {person['full_name']}")
                        return person
                
                print(f"❌ Сотрудник не найден: {name}")
                return None
            else:
                print(f"❌ Ошибка API: {data.get('message')}")
                return None
        else:
            print(f"❌ HTTP ошибка: {response.status_code}")
            return None
            
    except Exception as e:
        print(f"❌ Ошибка поиска: {e}")
        return None

async def main():
    """Основная функция для тестирования loadFiles"""
    # Устанавливаем токен для тестирования
    os.environ["API_TOKEN"] = "H7Ui3AMYom..."  # Замените на ваш реальный токен
    
    people = await search_employees('Иванов Иван Иванович')
    if people:
        print(f"Найден сотрудник: {people['full_name']} (ID: {people['id']})")
        
        # Проверяем, что файл существует
        file_path = "result/Иванов_Иван_Иванович.pdf"
        if os.path.exists(file_path):
            result = await loadFiles(people['id'], file_path)
            print(f"Результат: {result}")
        else:
            print(f"❌ Файл не найден: {file_path}")
    else:
        print("❌ Сотрудник не найден")

if __name__ == "__main__":
    asyncio.run(main())
