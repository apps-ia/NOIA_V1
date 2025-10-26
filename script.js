/**
 * NOIA - Frontend Logic
 * Version: 2.0.0 (Direct OpenAI Integration with DEMO interface)
 */

// Configuration
const API_ENDPOINT = './api/proxy.php';
const DEMO_MODE = false; // Set to true for local testing without PHP server

// Elements
const chatLog = document.getElementById('chatLog');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
const communeSelect = document.getElementById('commune');
const todayCountEl = document.getElementById('todayCount');
const avgTimeEl = document.getElementById('avgTime');

// Stats
let messageCount = 0;
let responseTimes = [];

/**
 * Auto-resize textarea
 */
chatInput.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

/**
 * Send message on button click
 */
sendBtn.addEventListener('click', sendMessage);

/**
 * Send message on Enter (Shift+Enter for new line)
 */
chatInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

/**
 * Use a suggestion chip
 */
function useSuggestion(el) {
  chatInput.value = el.textContent;
  chatInput.focus();
  sendMessage();
}

/**
 * Send message to NOIA
 */
async function sendMessage() {
  const question = chatInput.value.trim();
  if (!question || sendBtn.disabled) return;

  // Clear empty state
  const emptyState = chatLog.querySelector('.empty-state');
  if (emptyState) emptyState.remove();

  // Add user message
  addMessage(question, 'user');

  // Clear input
  chatInput.value = '';
  chatInput.style.height = 'auto';

  // Show typing indicator
  const typingId = addTypingIndicator();
  sendBtn.disabled = true;
  chatInput.disabled = true;

  // Get commune
  const commune = communeSelect.value;

  // Record start time
  const startTime = Date.now();

  try {
    let data;

    // Check if demo mode or if API call fails
    if (DEMO_MODE) {
      // Simulate API delay
      await new Promise(resolve => setTimeout(resolve, 1500 + Math.random() * 1000));
      data = { success: true, response: generateDemoResponse(question) };
    } else {
      // Try real API call
      try {
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

        data = await response.json();

        if (!response.ok) {
          throw new Error(data.error || `Erreur HTTP ${response.status}`);
        }
      } catch (fetchError) {
        // If API fails (e.g., no PHP server), fallback to demo mode
        console.warn('API non disponible, passage en mode démo:', fetchError);
        await new Promise(resolve => setTimeout(resolve, 1500));
        data = {
          success: true,
          response: generateDemoResponse(question),
          demo: true
        };
      }
    }

    // Remove typing indicator
    removeTypingIndicator(typingId);

    if (data.success) {
      // Add bot response
      let response = data.response;

      // Add demo notice if in demo mode
      if (data.demo || DEMO_MODE) {
        response = `<div style="background:#fff3cd;border:1px solid #ffc107;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px;color:#856404;">
          ⚠️ <strong>Mode démonstration</strong> — Réponse simulée (serveur PHP requis pour l'API réelle)
        </div>` + response;
      }

      addMessage(response, 'bot', true);

      // Update stats
      const responseTime = Date.now() - startTime;
      responseTimes.push(responseTime);
      messageCount++;
      todayCountEl.textContent = messageCount;

      const avgTime = responseTimes.reduce((a,b) => a+b, 0) / responseTimes.length;
      avgTimeEl.textContent = `~${(avgTime/1000).toFixed(1)}s`;

    } else {
      throw new Error(data.error || 'Erreur inconnue');
    }

  } catch (error) {
    console.error('Erreur:', error);

    // Remove typing indicator
    removeTypingIndicator(typingId);

    // Show error message
    let errorMessage = 'Une erreur est survenue. Veuillez réessayer.';

    if (error.message.includes('429')) {
      errorMessage = 'Trop de requêtes. Veuillez patienter quelques minutes.';
    } else if (error.message.includes('401')) {
      errorMessage = 'Erreur d\'authentification OpenAI. Vérifiez votre clé API.';
    } else if (error.message.includes('Fetch') || error.message.includes('NetworkError')) {
      errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
    } else if (error.message) {
      errorMessage = error.message;
    }

    addMessage(
      `<div class="error-message">❌ <strong>Erreur :</strong> ${errorMessage}</div>`,
      'bot',
      true
    );

  } finally {
    sendBtn.disabled = false;
    chatInput.disabled = false;
    chatInput.focus();
  }
}

/**
 * Add message to chat
 */
function addMessage(content, type, isStructured = false) {
  const msgDiv = document.createElement('div');
  msgDiv.className = `message ${type}`;

  const avatar = document.createElement('div');
  avatar.className = 'message-avatar';
  avatar.textContent = type === 'user' ? 'U' : 'N';

  const contentDiv = document.createElement('div');
  contentDiv.className = 'message-content';

  if (isStructured && type === 'bot') {
    // Sanitize HTML for bot responses
    contentDiv.innerHTML = sanitizeHTML(content);
  } else {
    contentDiv.textContent = content;
  }

  msgDiv.appendChild(avatar);
  msgDiv.appendChild(contentDiv);
  chatLog.appendChild(msgDiv);

  // Scroll to bottom
  chatLog.scrollTop = chatLog.scrollHeight;
}

/**
 * Add typing indicator
 */
function addTypingIndicator() {
  const id = 'typing-' + Date.now();
  const msgDiv = document.createElement('div');
  msgDiv.className = 'message bot';
  msgDiv.id = id;

  const avatar = document.createElement('div');
  avatar.className = 'message-avatar';
  avatar.textContent = 'N';

  const typingDiv = document.createElement('div');
  typingDiv.className = 'message-content';
  typingDiv.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';

  msgDiv.appendChild(avatar);
  msgDiv.appendChild(typingDiv);
  chatLog.appendChild(msgDiv);
  chatLog.scrollTop = chatLog.scrollHeight;

  return id;
}

/**
 * Remove typing indicator
 */
function removeTypingIndicator(id) {
  const el = document.getElementById(id);
  if (el) el.remove();
}

/**
 * Sanitize HTML (allow only safe tags)
 */
function sanitizeHTML(html) {
  const allowedTags = ['div', 'p', 'strong', 'em', 'ul', 'li', 'br', 'span'];
  const allowedClasses = ['structured-response', 'response-section', 'section-title', 'section-content', 'error-message'];

  // Create temporary element
  const temp = document.createElement('div');
  temp.innerHTML = html;

  // Process all elements
  const elements = temp.getElementsByTagName('*');
  for (let i = elements.length - 1; i >= 0; i--) {
    const element = elements[i];

    // Remove if tag not allowed
    if (!allowedTags.includes(element.tagName.toLowerCase())) {
      element.parentNode.removeChild(element);
      continue;
    }

    // Clean attributes
    const attrs = element.attributes;
    for (let j = attrs.length - 1; j >= 0; j--) {
      const attr = attrs[j];
      if (attr.name === 'class') {
        // Keep only allowed classes
        const classes = attr.value.split(' ').filter(c => allowedClasses.includes(c));
        if (classes.length > 0) {
          element.className = classes.join(' ');
        } else {
          element.removeAttribute('class');
        }
      } else {
        // Remove all other attributes
        element.removeAttribute(attr.name);
      }
    }
  }

  return temp.innerHTML;
}

/**
 * Generate demo response (for testing without PHP server)
 */
function generateDemoResponse(question) {
  const qLower = question.toLowerCase();

  let refs = 'Plan comptable M57 — Section Matériel technique, page 42';
  let analysis = 'Les matériels motorisés d\'entretien sont classés dans la famille "Matériel technique". Il convient de vérifier le montant et la nature exacte du matériel.';
  let practical = 'Imputation sur compte <strong>21534</strong> (Matériel et outillage techniques). Référence page 42 du M57. Amortissement sur 5 à 10 ans selon durée d\'utilisation.';
  let act = 'Délibération d\'acquisition du matériel avec visa du trésorier — modèle disponible au format PDF.';

  if (/(rh|rémun|indice|carrière|avancement)/i.test(qLower)) {
    refs = 'Grilles indiciaires FPT — Décret n°87-1107 ; Portail emploi-collectivites.fr';
    analysis = 'Le calcul de la rémunération s\'effectue sur la base de l\'Indice Brut (IB) et de l\'Indice Majoré (IM) selon le grade et l\'ancienneté.';
    practical = 'Utiliser le tableau de correspondance IB/IM avec application de la valeur du point. Date d\'effet : date de nomination. Formule : (IM × 4,92302) / 100';
    act = 'Arrêté individuel de nomination (modèle CDG) avec mention du grade, échelon et indice.';
  }

  if (/(délib|délibération|conseil|municipal)/i.test(qLower)) {
    refs = 'Code Général des Collectivités Territoriales (CGCT) — Articles L2121-9 à L2121-21 ; Légifrance';
    analysis = 'Vérification du quorum (majorité absolue des membres en exercice), de la publicité et des règles de convocation. Respect des délais de transmission en préfecture (15 jours).';
    practical = 'Préparer un projet de délibération avec visa des textes applicables, exposé des motifs et dispositif. Convocation 5 jours francs avant la séance (3 jours en urgence).';
    act = 'Modèle de délibération (.docx) conforme — prêt à personnaliser et générer.';
  }

  if (/(urban|plu|permis|construire)/i.test(qLower)) {
    refs = 'Code de l\'urbanisme — Articles L.151-1 et suivants ; R.423-1 et suivants';
    analysis = 'Instruction selon les règles du PLU en vigueur. Vérification de la conformité avec le zonage et les servitudes. Délai standard : 2 mois pour maison individuelle, 3 mois pour autres constructions.';
    practical = 'Consultation obligatoire de l\'architecte des bâtiments de France si périmètre protégé. Le silence de l\'administration vaut accord. Affichage obligatoire du récépissé pendant toute la durée du chantier.';
    act = 'Certificat d\'urbanisme ou arrêté de permis de construire selon modèle CERFA réglementaire.';
  }

  if (/(budget|m57|comptable|finance)/i.test(qLower)) {
    refs = 'Instruction budgétaire M57 — DGCL, Tome 1 et 2';
    analysis = 'La nomenclature M57 organise les comptes en chapitres. Le budget doit être équilibré en section de fonctionnement et d\'investissement. Le compte administratif constate l\'exécution du budget.';
    practical = 'Utilisation des chapitres : 011 (charges générales), 012 (personnel), 70 (produits de services), 73 (impôts et taxes). Vote du budget primitif avant le 15 avril N ou le 30 avril les années de renouvellement.';
    act = 'Maquettes budgétaires M57 disponibles sur le site DGCL — format Excel avec formules intégrées.';
  }

  return `
    <div class="structured-response">
      <div class="response-section">
        <div class="section-title">📚 Références juridiques</div>
        <div class="section-content">${refs}</div>
      </div>
      <div class="response-section">
        <div class="section-title">🔍 Analyse réglementaire</div>
        <div class="section-content">${analysis}</div>
      </div>
      <div class="response-section">
        <div class="section-title">✅ Application pratique</div>
        <div class="section-content">${practical}</div>
      </div>
      <div class="response-section">
        <div class="section-title">📄 Proposition d'acte</div>
        <div class="section-content">${act}</div>
      </div>
    </div>
  `;
}

/**
 * Focus input on load
 */
chatInput.focus();

// Debug info
console.log('NOIA v2.0.0 - OpenAI Direct Integration');
console.log('Interface: DEMO style with sidebar');
console.log('Demo mode:', DEMO_MODE ? 'Enabled' : 'Auto-fallback if API fails');
