/**
 * Notification System using Bootstrap Toasts
 * Handles success/error/warning/info notifications with sound.
 */
class NotificationSystem {
    constructor() {
        this.containerId = 'notification-toast-container';
        this.sounds = {
            success: 'assets/sounds/success.mp3',
            error: 'assets/sounds/error.mp3',
            warning: 'assets/sounds/warning.mp3',
            info: 'assets/sounds/info.mp3'
        };
        // Preload sounds if possible
        this.audioCache = {};
    }

    ensureContainer() {
        if (!document.getElementById(this.containerId)) {
            const container = document.createElement('div');
            container.id = this.containerId;
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1060'; // High z-index to be on top of everything
            document.body.appendChild(container);
        }
    }

    playSound(type) {
        // We use Web Audio API to generate distinct tones for success (high) and error (low)
        // This ensures they sound different even if the same mp3 file is used as fallback.
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) throw new Error('Web Audio not supported');

            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gainNode = context.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(context.destination);

            let frequency = 440; // Default (Info)
            let type_osc = 'sine';

            if (type === 'success') {
                frequency = 880; // High pitch
            } else if (type === 'error') {
                frequency = 110; // Low pitch
                type_osc = 'square'; // Harsh sound for error
            } else if (type === 'warning') {
                frequency = 330;
            }

            oscillator.type = type_osc;
            oscillator.frequency.setValueAtTime(frequency, context.currentTime);

            // Volume envelope: fade in/out to avoid clicks
            gainNode.gain.setValueAtTime(0, context.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, context.currentTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.9);
            gainNode.gain.linearRampToValueAtTime(0, context.currentTime + 1.0);

            oscillator.start();
            oscillator.stop(context.currentTime + 1.0);

            // Cleanup context
            setTimeout(() => {
                if (context.state !== 'closed') context.close();
            }, 1100);

        } catch (e) {
            // Fallback to MP3 with pitch adjustment
            const soundPath = this.sounds[type] || this.sounds.info;
            const audio = new Audio(soundPath);
            audio.volume = 0.5;

            // Adjust playback rate to make them sound different
            if (type === 'success') audio.playbackRate = 1.5;
            if (type === 'error') audio.playbackRate = 0.7;

            audio.play().catch(err => console.warn('Audio play failed:', err));

            setTimeout(() => {
                audio.pause();
                audio.currentTime = 0;
            }, 1000);
        }
    }

    show(type, message) {
        this.ensureContainer();
        const container = document.getElementById(this.containerId);

        // Map types to specific styles
        const config = {
            success: { icon: 'ti-check', color: 'bg-success', title: 'Success' },
            error: { icon: 'ti-alert-circle', color: 'bg-danger', title: 'Error' },
            warning: { icon: 'ti-alert-triangle', color: 'bg-warning', title: 'Warning' },
            info: { icon: 'ti-info-circle', color: 'bg-info', title: 'Info' }
        };

        // Default to info if type unknown
        const theme = config[type] || config.info;
        const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

        // Toast HTML Structure matching Bootstrap 5
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${theme.color} text-white">
                    <i class="ti ${theme.icon} me-2"></i>
                    <strong class="me-auto">${theme.title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        // Create element
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = toastHtml.trim();
        const toastElement = tempDiv.firstChild;

        container.appendChild(toastElement);

        // Initialize Bootstrap Toast
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toastElement, { delay: 5000, autohide: true });
            bsToast.show();
        } else {
            toastElement.classList.add('show');
            setTimeout(() => {
                toastElement.remove();
            }, 5000);
        }

        // Play sound
        this.playSound(type);

        // Cleanup after hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    /**
     * Show a confirmation modal
     * @param {Object} options 
     * @returns {Promise<boolean>}
     */
    confirm(options) {
        const {
            title = 'Confirm Action',
            message = 'Are you sure you want to proceed?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            type = 'primary', // primary, danger, success, warning
            icon = 'ti-help'
        } = options;

        return new Promise((resolve) => {
            const modalId = 'confirmation-modal-' + Date.now();
            const btnClass = type === 'danger' ? 'btn-danger' : `btn-${type}`;

            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title d-flex align-items-center">
                                    <i class="ti ${icon} me-2 text-${type} fs-4"></i>
                                    ${title}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body py-3">
                                <p class="mb-0 text-muted">${message}</p>
                            </div>
                            <div class="modal-footer border-top-0 pt-0">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">${cancelText}</button>
                                <button type="button" class="btn ${btnClass} btn-sm" id="${modalId}-confirm-btn">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalElement = document.getElementById(modalId);
            const confirmBtn = document.getElementById(`${modalId}-confirm-btn`);

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = new bootstrap.Modal(modalElement);
                bsModal.show();

                confirmBtn.addEventListener('click', () => {
                    bsModal.hide();
                    resolve(true);
                });

                modalElement.addEventListener('hidden.bs.modal', () => {
                    modalElement.remove();
                    resolve(false);
                });
            } else {
                // Fallback for missing Bootstrap JS
                const confirmed = confirm(message);
                modalElement.remove();
                resolve(confirmed);
            }
        });
    }

    /**
     * Simplified confirmation for delete actions
     * @param {string} message 
     * @returns {Promise<boolean>}
     */
    confirmDelete(message = 'Are you sure you want to delete this item? This action cannot be undone.') {
        return this.confirm({
            title: 'Confirm Deletion',
            message: message,
            confirmText: 'Delete',
            type: 'danger',
            icon: 'ti-trash'
        });
    }
}

// Initialize system
const notificationSystem = new NotificationSystem();

/**
 * Global function to show notifications
 * @param {string} type - 'success', 'error', 'warning', 'info'
 * @param {string} message - The message to display
 */
function showNotification(type, message) {
    notificationSystem.show(type, message);
}

/**
 * Global function to show confirmation modals
 * @param {Object} options 
 * @returns {Promise<boolean>}
 */
function showConfirmation(options) {
    return notificationSystem.confirm(options);
}

/**
 * Global function for delete confirmation
 * @param {string} message 
 * @param {function} onConfirm - Optional callback
 * @returns {Promise<boolean>}
 */
function confirmDelete(message, onConfirm) {
    const promise = notificationSystem.confirmDelete(message);
    if (typeof onConfirm === 'function') {
        promise.then(confirmed => {
            if (confirmed) onConfirm();
        });
    }
    return promise;
}

/**
 * Legacy adapter for showtoastt
 * @param {string} message 
 * @param {string} type 
 */
function showtoastt(message, type) {
    // Map legacy 'error' type to new system, default to success
    const newType = (type === 'error' || type === 'danger') ? 'error' : 'success';
    showNotification(newType, message);
}
