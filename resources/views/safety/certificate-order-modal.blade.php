<!-- Модальное окно для изменения порядка сертификатов -->
<div class="modal fade" id="certificateOrderModal" tabindex="-1" aria-labelledby="certificateOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificateOrderModalLabel">
                    <i class="fas fa-sort"></i> Изменение порядка сертификатов
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Перетащите сертификаты для изменения их порядка в таблице. Порядок будет сохранен автоматически.
                </div>
                
                <div id="certificateOrderList" class="list-group">
                    @foreach($certificates as $certificate)
                        <div class="list-group-item certificate-order-item" data-certificate-id="{{ $certificate->id }}">
                            <div class="d-flex align-items-center">
                                <div class="drag-handle me-3">
                                    <i class="fas fa-grip-vertical text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $certificate->name }}</h6>
                                    @if($certificate->expiry_date)
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i>
                                            Срок действия: {{ $certificate->expiry_date }} {{ $certificate->expiry_date == 1 ? 'год' : ($certificate->expiry_date < 5 ? 'года' : 'лет') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="order-number">
                                    <span class="badge bg-primary">{{ $certificate->order ? $certificate->order->sort_order : 'Н/Д' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Отмена
                </button>
                <button type="button" class="btn btn-primary" id="saveCertificateOrder">
                    <i class="fas fa-save"></i> Сохранить порядок
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.certificate-order-item {
    cursor: move;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 5px;
    border-radius: 8px;
}

.certificate-order-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.certificate-order-item.sortable-ghost {
    opacity: 0.5;
    background-color: #e3f2fd;
    border-color: #2196f3;
}

.certificate-order-item.sortable-chosen {
    background-color: #bbdefb;
    border-color: #1976d2;
    transform: scale(1.02);
}

.drag-handle {
    cursor: grab;
    color: #6c757d;
}

.drag-handle:active {
    cursor: grabbing;
}

.order-number {
    min-width: 40px;
    text-align: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация перетаскивания
    const certificateOrderList = document.getElementById('certificateOrderList');
    if (certificateOrderList && typeof Sortable !== 'undefined') {
        const sortable = Sortable.create(certificateOrderList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            handle: '.drag-handle',
            onEnd: function(evt) {
                updateOrderNumbers();
            }
        });
    }

    // Обновление номеров порядка
    function updateOrderNumbers() {
        const items = certificateOrderList.querySelectorAll('.certificate-order-item');
        items.forEach((item, index) => {
            const orderBadge = item.querySelector('.order-number .badge');
            if (orderBadge) {
                orderBadge.textContent = index + 1;
            }
        });
    }

    // Сохранение порядка
    document.getElementById('saveCertificateOrder').addEventListener('click', function() {
        const items = certificateOrderList.querySelectorAll('.certificate-order-item');
        const certificateIds = Array.from(items).map(item => item.getAttribute('data-certificate-id'));
        
        fetch('/safety/update-certificate-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                certificate_ids: certificateIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Показываем уведомление об успехе
                showNotification('Порядок сертификатов успешно обновлен!', 'success');
                
                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(document.getElementById('certificateOrderModal'));
                modal.hide();
                
                // Перезагружаем страницу для обновления таблицы
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('Ошибка при сохранении порядка: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Произошла ошибка при сохранении порядка', 'error');
        });
    });

    // Функция для показа уведомлений
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
});
</script>
