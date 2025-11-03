/**
 * NOIA MVP - Authentication JavaScript (VERSION CORRIGÉE)
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');

    console.log('Auth.js chargé - Version debug activée');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        console.log('Tentative de connexion pour:', email);

        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);

            console.log('Envoi requête vers: /api/auth.php');

            const response = await fetch('/api/auth.php', {
                method: 'POST',
                body: formData
            });

            console.log('Réponse HTTP:', response.status, response.statusText);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erreur HTTP:', response.status, errorText);
                throw new Error(`Erreur serveur (${response.status}): ${errorText.substring(0, 100)}`);
            }

            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);

            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Réponse non-JSON reçue:', text.substring(0, 200));
                throw new Error('Le serveur a renvoyé une réponse invalide');
            }

            const data = await response.json();
            console.log('Réponse JSON:', data);

            if (data.success) {
                console.log('✅ Connexion réussie, redirection...');
                window.location.href = 'chat.html';
            } else {
                console.error('❌ Connexion échouée:', data.error);
                showError(data.error || 'Erreur de connexion');
            }
        } catch (error) {
            console.error('❌ Exception:', error);
            showError('Erreur de connexion au serveur: ' + error.message);
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');

        setTimeout(() => {
            errorMessage.classList.add('hidden');
        }, 5000);
    }
});
