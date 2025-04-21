const chatButton = document.querySelector('.chat-button');
const chatWindow = document.querySelector('.chat-window');
const closeBtn = document.querySelector('.close-btn');
const sendBtn = document.querySelector('.send-btn');
const chatInput = document.querySelector('.chat-input input');
const chatBody = document.querySelector('.chat-body');

chatButton.addEventListener('click', () => {
    chatWindow.classList.toggle('active');
    
});

closeBtn.addEventListener('click', () => {
    chatWindow.classList.remove('active');
});

function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    addMessage(message, 'user');

    setTimeout(() => {
        addMessage('Thanks for your message! Our team will respond shortly.', 'bot');
    }, 1000);

    chatInput.value = '';
}

function addMessage(text, sender) {
    const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    messageDiv.innerHTML = `
        ${text}
        <div class="timestamp">${timestamp}</div>
    `;
    chatBody.appendChild(messageDiv);
    
    chatBody.scrollTop = chatBody.scrollHeight;
}
sendBtn.addEventListener('click', sendMessage);
chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
});

setTimeout(() => {
    addMessage('Hello! How can we help you today?', 'bot');
}, 500);