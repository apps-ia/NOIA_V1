/**
 * NOIA MVP - Chat JavaScript
 */

let currentThreadId = null;

document.addEventListener('DOMContentLoaded', async function() {
    // Vérifier l'authentification
    await checkAuth();

    // Event listeners
    document.getElementById('messageForm').addEventListener('submit', sendMessage);
    document.getElementById('logoutBtn').addEventListener('click', logout);

    // Auto-resize textarea
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Créer une nouvelle conversation
    await createNewConversation();
});

async function checkAuth() {
    try {
        const response = await fetch('api/auth.php?action=check');
        const data = await response.json();

        if (!data.authenticated) {
            window.location.href = 'index.html';
            return;
        }

        // Afficher le nom de l'utilisateur
        document.getElementById('userName').textContent =
            `${data.user.prenom} ${data.user.nom}`;
    } catch (error) {
        window.location.href = 'index.html';
    }
}

async function createNewConversation() {
    try {
        const formData = new FormData();
        formData.append('action', 'new_conversation');

        const response = await fetch('api/assistant.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            currentThreadId = data.thread_id;
        }
    } catch (error) {
        console.error('Erreur création conversation:', error);
    }
}

async function sendMessage(e) {
    e.preventDefault();

    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const message = messageInput.value.trim();

    if (!message || !currentThreadId) return;

    // Désactiver l'envoi
    messageInput.disabled = true;
    sendBtn.disabled = true;
    sendBtn.textContent = 'Envoi...';

    // Afficher le message de l'utilisateur
    addMessage('user', message);
    messageInput.value = '';

    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('thread_id', currentThreadId);
        formData.append('message', message);

        // Afficher un indicateur de chargement
        const loadingId = addMessage('assistant', '⏳ NOIA réfléchit...');

        const response = await fetch('api/assistant.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // Retirer le message de chargement
        document.getElementById(loadingId).remove();

        if (data.success) {
            addMessage('assistant', data.response);
        } else {
            addMessage('assistant', `❌ Erreur: ${data.error}`);
        }
    } catch (error) {
        addMessage('assistant', '❌ Erreur de connexion au serveur');
    } finally {
        // Réactiver l'envoi
        messageInput.disabled = false;
        sendBtn.disabled = false;
        sendBtn.textContent = 'Envoyer';
        messageInput.focus();
    }
}

function addMessage(role, content) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageId = 'msg-' + Date.now();

    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = role === 'user'
        ? 'bg-blue-100 rounded-lg p-4 ml-12'
        : 'bg-white rounded-lg p-4 mr-12 shadow';

    if (role === 'user') {
        messageDiv.innerHTML = `
            <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(content)}</p>
        `;
    } else {
        // Convertir Markdown en HTML pour les réponses de l'assistant
        messageDiv.innerHTML = `
            <div class="prose prose-sm max-w-none">
                ${marked.parse(content)}
            </div>
        `;
    }

    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    return messageId;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function logout() {
    try {
        const formData = new FormData();
        formData.append('action', 'logout');

        await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });

        window.location.href = 'index.html';
    } catch (error) {
        window.location.href = 'index.html';
    }
}
