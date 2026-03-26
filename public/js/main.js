/**
 * SondagePro - Scripts principaux
 */

document.addEventListener('DOMContentLoaded', function() {
    // Animation des éléments au scroll
    initScrollAnimations();
    
    // Initialisation des tooltips Bootstrap
    initTooltips();
    
    // Auto-dismiss des alertes
    initAlertAutoDismiss();
});

/**
 * Animations au scroll
 */
function initScrollAnimations() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.card, .stat-card').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialiser les tooltips Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

/**
 * Auto-dismiss des alertes après 5 secondes
 */
function initAlertAutoDismiss() {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Copier du texte dans le presse-papier
 */
function copyToClipboard(text, successMessage = 'Copié !') {
    navigator.clipboard.writeText(text).then(() => {
        showToast(successMessage, 'success');
    }).catch(err => {
        console.error('Erreur lors de la copie:', err);
        showToast('Erreur lors de la copie', 'danger');
    });
}

/**
 * Afficher un toast notification
 */
function showToast(message, type = 'info') {
    // Créer le conteneur de toasts s'il n'existe pas
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Créer le toast
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type}" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Supprimer après fermeture
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Confirmation avant suppression
 */
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

/**
 * Formater une date en français
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Valider un formulaire
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    
    // Vérifier les champs requis
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Charger les résultats en AJAX
 */
function loadResults(sondageId, containerId) {
    fetch(`ajax/get_results.php?id=${sondageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateResultsDisplay(containerId, data);
            }
        })
        .catch(err => console.error('Erreur:', err));
}

/**
 * Mettre à jour l'affichage des résultats
 */
function updateResultsDisplay(containerId, data) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Mettre à jour le total des votes
    const totalElement = container.querySelector('.total-votes');
    if (totalElement) {
        totalElement.textContent = data.total_votes;
    }
    
    // Mettre à jour chaque barre de résultat
    data.resultats.forEach(result => {
        const bar = container.querySelector(`[data-option-id="${result.id}"] .result-fill`);
        if (bar) {
            bar.style.width = `${Math.max(result.percentage, 5)}%`;
            bar.textContent = `${result.percentage}%`;
        }
    });
}
