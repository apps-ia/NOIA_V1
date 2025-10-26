/**
 * NOIA - Frontend Logic
 * Version: 2.0.0 (Direct OpenAI Integration with DEMO interface)
 */

// Configuration
const API_ENDPOINT = './api/proxy.php';

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
    // Call API
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

    // Remove typing indicator
    removeTypingIndicator(typingId);

    if (!response.ok) {
      throw new Error(data.error || `Erreur HTTP ${response.status}`);
    }

    if (data.success) {
      // Add bot response
      addMessage(data.response, 'bot', true);

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
 * Focus input on load
 */
chatInput.focus();

// Debug info
console.log('NOIA v2.0.0 - OpenAI Direct Integration');
console.log('Interface: DEMO style with sidebar');
