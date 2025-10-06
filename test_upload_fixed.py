#!/usr/bin/env python3
"""
Улучшенный тест загрузки файлов через API
"""
import os
import requests
import tempfile
from pathlib import Path

# Конфигурация
BASE_URL = "http://127.0.0.1:8000"
API_TOKEN = "H7Ui3AMYom..."  # Замените на ваш реальный токен

def create_test_pdf():
    """Создает тестовый PDF файл"""
    # Создаем простой PDF файл для тестирования
    pdf_content = b"""%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj

4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test PDF) Tj
ET
endstream
endobj

xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000204 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
297
%%EOF"""
    
    # Создаем временный файл
    temp_file = tempfile.NamedTemporaryFile(suffix='.pdf', delete=False)
    temp_file.write(pdf_content)
    temp_file.close()
    
    return temp_file.name

def test_api_connection():
    """Тестирует подключение к API"""
    print("🔍 Тестируем подключение к API...")
    
    headers = {
        'Authorization': f'Bearer {API_TOKEN}',
        'Accept': 'application/json'
    }
    
    try:
        response = requests.get(f"{BASE_URL}/api/people/compact?limit=1", headers=headers, timeout=10)
        print(f"📊 Статус: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✅ API доступен")
                return True
            else:
                print(f"❌ API ошибка: {data.get('message')}")
                return False
        else:
            print(f"❌ HTTP ошибка: {response.status_code}")
            print(f"Ответ: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Ошибка подключения: {e}")
        return False

def test_upload_file(people_id, file_path):
    """Тестирует загрузку файла с улучшенной обработкой"""
    print(f"📁 Тестируем загрузку файла для человека ID: {people_id}")
    print(f"📄 Файл: {file_path}")
    
    headers = {
        'Authorization': f'Bearer {API_TOKEN}',
        'Accept': 'application/json'
    }
    
    try:
        # Проверяем размер файла
        file_size = os.path.getsize(file_path)
        print(f"📏 Размер файла: {file_size} байт")
        
        with open(file_path, 'rb') as file:
            files = {
                'certificates_file': (
                    os.path.basename(file_path),
                    file,
                    'application/pdf'
                )
            }
            
            print("🔄 Отправляем запрос...")
            response = requests.post(
                f"{BASE_URL}/api/people/{people_id}/certificates-file",
                files=files,
                headers=headers,
                timeout=60  # Увеличиваем таймаут
            )
        
        print(f"📤 Статус ответа: {response.status_code}")
        
        # Пытаемся получить JSON ответ
        try:
            data = response.json()
            print(f"📋 JSON ответ: {data}")
            
            if response.status_code == 200 and data.get('success'):
                print("✅ Файл успешно загружен!")
                print(f"📁 Имя файла: {data['data']['filename']}")
                print(f"📏 Размер: {data['data']['size']} байт")
                print(f"📂 Путь: {data['data']['path']}")
                return True
            else:
                print(f"❌ Ошибка загрузки: {data.get('message', 'Неизвестная ошибка')}")
                if 'error' in data:
                    print(f"🔍 Детали ошибки: {data['error']}")
                return False
                
        except Exception as e:
            print(f"❌ Ошибка парсинга JSON: {e}")
            print(f"📋 Текстовый ответ: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Ошибка загрузки файла: {e}")
        return False

def find_test_person():
    """Находит тестового человека"""
    print("🔍 Ищем тестового человека...")
    
    headers = {
        'Authorization': f'Bearer {API_TOKEN}',
        'Accept': 'application/json'
    }
    
    try:
        response = requests.get(f"{BASE_URL}/api/people/compact", headers=headers, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                people = data.get('data', [])
                if people:
                    person = people[0]  # Берем первого человека
                    print(f"✅ Найден человек: {person.get('full_name')} (ID: {person.get('id')})")
                    return person
                else:
                    print("❌ Люди не найдены")
                    return None
            else:
                print(f"❌ API ошибка: {data.get('message')}")
                return None
        else:
            print(f"❌ HTTP ошибка: {response.status_code}")
            return None
            
    except Exception as e:
        print(f"❌ Ошибка поиска: {e}")
        return None

def main():
    """Основная функция тестирования"""
    print("🚀 Начинаем тестирование API загрузки файлов (исправленная версия)")
    print("=" * 70)
    
    # 1. Тестируем подключение к API
    if not test_api_connection():
        print("❌ Не удалось подключиться к API")
        return
    
    # 2. Ищем тестового человека
    person = find_test_person()
    if not person:
        print("❌ Не удалось найти человека для тестирования")
        return
    
    # 3. Создаем тестовый файл
    print("\n📄 Создаем тестовый PDF файл...")
    test_file = create_test_pdf()
    print(f"✅ Создан файл: {test_file}")
    
    try:
        # 4. Тестируем загрузку
        print(f"\n🔄 Тестируем загрузку файла...")
        success = test_upload_file(person['id'], test_file)
        
        if success:
            print("\n🎉 ТЕСТ ПРОЙДЕН! API загрузки файлов работает корректно")
        else:
            print("\n❌ ТЕСТ НЕ ПРОЙДЕН! API загрузки файлов не работает")
            
    finally:
        # 5. Удаляем тестовый файл
        try:
            os.unlink(test_file)
            print(f"🗑️ Удален тестовый файл: {test_file}")
        except:
            pass

if __name__ == "__main__":
    main()
