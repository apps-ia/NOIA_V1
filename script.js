/**
 * NOIA - Frontend Logic
 * Version: 2.0.0 (Direct OpenAI Integration)
 */

// Configuration
const API_ENDPOINT = './api/proxy.php';

// Éléments DOM
const questionForm = document.getElementById('questionForm');
const questionInput = document.getElementById('questionInput');
const sendButton = document.getElementById('sendButton');
const chatContainer = document.getElementById('chatContainer');
const loadingIndicator = document.getElementById('loadingIndicator');
const communeSelect = document.getElementById('commune');
const charCounter = document.getElementById('charCounter');
const toast = document.getElementById('toast');

// État
let isProcessing = false;

/**
 * Initialisation
 */
document.addEventListener('DOMContentLoaded', () => {
    // Compteur de caractères
    questionInput.addEventListener('input', updateCharCounter);

    // Auto-resize du textarea
    questionInput.addEventListener('input', autoResize);

    // Soumettre avec Ctrl+Entrée
    questionInput.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            questionForm.dispatchEvent(new Event('submit'));
        }
    });

    // Gérer la soumission du formulaire
    questionForm.addEventListener('submit', handleSubmit);

    // Focus sur l'input au chargement
    questionInput.focus();
});

/**
 * Mise à jour du compteur de caractères
 */
function updateCharCounter() {
    const length = questionInput.value.length;
    charCounter.textContent = `${length}/500`;

    if (length > 450) {
        charCounter.style.color = '#e74c3c';
    } else {
        charCounter.style.color = '#7f8c8d';
    }
}

/**
 * Auto-resize du textarea
 */
function autoResize() {
    questionInput.style.height = 'auto';
    questionInput.style.height = Math.min(questionInput.scrollHeight, 150) + 'px';
}

/**
 * Afficher une notification toast
 */
function showToast(message, type = 'info') {
    toast.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.style.display = 'block';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 5000);
}

/**
 * Ajouter un message au chat
 */
function addMessage(content, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;

    const iconDiv = document.createElement('div');
    iconDiv.className = 'message-icon';
    iconDiv.textContent = isUser ? '👤' : '🤖';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';

    if (isUser) {
        contentDiv.textContent = content;
    } else {
        // Pour les réponses de l'assistant, permettre le HTML
        contentDiv.innerHTML = sanitizeHTML(content);
    }

    messageDiv.appendChild(iconDiv);
    messageDiv.appendChild(contentDiv);
    chatContainer.appendChild(messageDiv);

    // Scroll vers le bas
    chatContainer.scrollTop = chatContainer.scrollHeight;

    return messageDiv;
}

/**
 * Sanitize HTML (liste blanche de balises)
 */
function sanitizeHTML(html) {
    const allowedTags = ['div', 'p', 'strong', 'em', 'ul', 'li', 'br', 'span'];
    const allowedClasses = ['structured-response', 'response-section', 'section-title', 'section-content'];

    // Créer un élément temporaire
    const temp = document.createElement('div');
    temp.innerHTML = html;

    // Parcourir tous les éléments
    const elements = temp.getElementsByTagName('*');
    for (let i = elements.length - 1; i >= 0; i--) {
        const element = elements[i];

        // Supprimer si la balise n'est pas autorisée
        if (!allowedTags.includes(element.tagName.toLowerCase())) {
            element.parentNode.removeChild(element);
            continue;
        }

        // Nettoyer les attributs
        const attrs = element.attributes;
        for (let j = attrs.length - 1; j >= 0; j--) {
            const attr = attrs[j];
            if (attr.name === 'class') {
                // Garder uniquement les classes autorisées
                const classes = attr.value.split(' ').filter(c => allowedClasses.includes(c));
                if (classes.length > 0) {
                    element.className = classes.join(' ');
                } else {
                    element.removeAttribute('class');
                }
            } else {
                // Supprimer tous les autres attributs
                element.removeAttribute(attr.name);
            }
        }
    }

    return temp.innerHTML;
}

/**
 * Afficher/masquer l'indicateur de chargement
 */
function setLoading(loading) {
    isProcessing = loading;
    loadingIndicator.style.display = loading ? 'flex' : 'none';
    sendButton.disabled = loading;
    questionInput.disabled = loading;

    if (!loading) {
        questionInput.focus();
    }
}

/**
 * Gérer la soumission du formulaire
 */
async function handleSubmit(e) {
    e.preventDefault();

    if (isProcessing) return;

    const question = questionInput.value.trim();
    if (!question) return;

    const commune = communeSelect.value;

    // Ajouter le message de l'utilisateur
    addMessage(question, true);

    // Réinitialiser le formulaire
    questionInput.value = '';
    updateCharCounter();
    autoResize();

    // Afficher le chargement
    setLoading(true);

    try {
        // Appel à l'API
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question: question,
                commune: commune
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `Erreur HTTP ${response.status}`);
        }

        if (data.success) {
            // Ajouter la réponse de l'assistant
            addMessage(data.response, false);

            // Afficher les statistiques (optionnel)
            if (data.sources_count) {
                const totalSources = data.sources_count.central + data.sources_count.local;
                if (totalSources > 0) {
                    console.log(`Sources utilisées: ${totalSources} (${data.sources_count.central} centrales, ${data.sources_count.local} locales)`);
                }
            }
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }

    } catch (error) {
        console.error('Erreur:', error);

        // Message d'erreur utilisateur
        let errorMessage = 'Une erreur est survenue. Veuillez réessayer.';

        if (error.message.includes('429')) {
            errorMessage = 'Trop de requêtes. Veuillez patienter quelques minutes.';
        } else if (error.message.includes('Fetch')) {
            errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
        }

        addMessage(
            `<div class="error-message">❌ <strong>Erreur :</strong> ${errorMessage}</div>`,
            false
        );

        showToast(errorMessage, 'error');

    } finally {
        setLoading(false);
    }
}

/**
 * Gestion du changement de commune
 */
communeSelect.addEventListener('change', () => {
    const commune = communeSelect.options[communeSelect.selectedIndex].text;
    console.log(`Commune sélectionnée: ${commune}`);
});

/**
 * Statistiques d'utilisation (optionnel)
 */
function getMessageCount() {
    const messages = chatContainer.querySelectorAll('.user-message');
    return messages.length;
}

// Debug info (à supprimer en production)
console.log('NOIA v2.0.0 - Intégration OpenAI directe');
console.log('Make.com a été supprimé avec succès!');
