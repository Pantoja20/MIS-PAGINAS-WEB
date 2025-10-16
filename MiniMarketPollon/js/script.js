// Confirmaciones mejoradas para enlaces y botones
const deleteButtons = document.querySelectorAll('.btn-delete');
deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const deleteUrl = this.getAttribute('href');
        const productName = this.closest('tr').querySelector('td:first-child')?.textContent || 'el elemento';
        showDeleteConfirmation(e, deleteUrl, productName);
    });
});

// Función para mostrar confirmación moderna de eliminación
function showDeleteConfirmation(e, deleteUrl = null, productName = 'el elemento') {
    // Crear overlay del modal
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay active';
    modalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        animation: fadeIn 0.3s forwards;
    `;

    // Crear modal de confirmación
    const modal = document.createElement('div');
    modal.className = 'delete-modal';
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transform: translateY(-20px);
        animation: slideUp 0.3s forwards;
        overflow: hidden;
    `;

    // Contenido del modal
    modal.innerHTML = `
        <div style="padding: 25px; text-align: center;">
            <div style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;">⚠️</div>
            <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 20px;">¿Confirmar eliminación?</h3>
            <p style="color: #7f8c8d; line-height: 1.5; margin-bottom: 25px;">
                ¿Estás seguro de que deseas eliminar <strong>${productName}</strong>?<br>
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="cancel-btn" style="
                    background: #95a5a6;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s;
                    min-width: 100px;
                ">Cancelar</button>
                <button class="confirm-btn" style="
                    background: #e74c3c;
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s;
                    min-width: 100px;
                ">Eliminar</button>
            </div>
        </div>
    `;

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        @keyframes slideUp {
            to { transform: translateY(0); }
        }
        @keyframes fadeOut {
            to { opacity: 0; }
        }
        @keyframes slideDown {
            to { transform: translateY(20px); opacity: 0; }
        }
        .cancel-btn:hover {
            background: #7f8c8d !important;
            transform: translateY(-2px);
        }
        .confirm-btn:hover {
            background: #c0392b !important;
            transform: translateY(-2px);
        }
        
        /* Estilos para notificación moderna */
        .modern-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(400px);
            opacity: 0;
            animation: slideInRight 0.5s forwards, slideOutRight 0.5s forwards 4s;
            max-width: 350px;
        }
        
        .modern-notification.error {
            background: #e74c3c;
        }
        
        .modern-notification.warning {
            background: #f39c12;
        }
        
        .notification-icon {
            font-size: 20px;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .notification-message {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes slideInRight {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Agregar al DOM
    modalOverlay.appendChild(modal);
    document.body.appendChild(modalOverlay);

    // Manejar eventos de los botones
    const cancelBtn = modal.querySelector('.cancel-btn');
    const confirmBtn = modal.querySelector('.confirm-btn');

    cancelBtn.addEventListener('click', () => {
        closeModal();
    });

    confirmBtn.addEventListener('click', () => {
        closeModal();
        showSuccessNotification(`"${productName}" eliminado correctamente`);
        
        // Esperar un momento para que se vea la notificación antes de redirigir
        setTimeout(() => {
            if (deleteUrl) {
                window.location.href = deleteUrl;
            }
        }, 1500);
    });

    // Función para cerrar modal
    function closeModal() {
        modalOverlay.style.animation = 'fadeOut 0.3s forwards';
        modal.style.animation = 'slideDown 0.3s forwards';
        setTimeout(() => {
            if (modalOverlay.parentElement) {
                document.body.removeChild(modalOverlay);
            }
            if (style.parentElement) {
                document.head.removeChild(style);
            }
        }, 300);
    }

    // Cerrar al hacer clic fuera del modal
    modalOverlay.addEventListener('click', (event) => {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function closeOnEscape(event) {
        if (event.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', closeOnEscape);
        }
    });
}

// Función para mostrar notificación de éxito moderna
function showSuccessNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'modern-notification';
    notification.innerHTML = `
        <div class="notification-icon">✅</div>
        <div class="notification-content">
            <div class="notification-title">Eliminación Exitosa</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Función para mostrar notificación de error
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'modern-notification error';
    notification.innerHTML = `
        <div class="notification-icon">❌</div>
        <div class="notification-content">
            <div class="notification-title">Error</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}