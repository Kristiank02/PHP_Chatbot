document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('composer');
  const textarea = document.getElementById('input');
  const messages = document.getElementById('messages');
  const statusPill = document.getElementById('status');
  const sendButton = document.getElementById('send');

  if (!form || !textarea || !messages) {
    return;
  }

  textarea.addEventListener('keydown', (event) => {
    const isEnter = event.key === 'Enter';
    const isModifierPressed = event.shiftKey || event.ctrlKey || event.metaKey || event.altKey;

    if (!isEnter || isModifierPressed) {
      return;
    }

    event.preventDefault();

    if (!textarea.value.trim()) {
      return;
    }

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(sendButton || null);
    } else {
      form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const content = textarea.value.trim();
    if (!content) {
      return;
    }

    const formData = new FormData(form);
    formData.set('message', content);

    appendMessage('user', content);
    textarea.value = '';
    textarea.focus();
    setStatus('Sending…');
    setComposerDisabled(true);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`Server responded with ${response.status}`);
      }

      const payload = await parseJson(response);
      if (!payload.success) {
        throw new Error(payload.error || 'Unknown server error');
      }

      if (typeof payload.reply !== 'string') {
        throw new Error('Empty reply from the assistant');
      }

      appendMessage('assistant', payload.reply);
      setStatus('Ready');
    } catch (error) {
      console.error(error);
      setStatus('Error');
      alert(`Couldn't send the message: ${error.message || error}`);
      appendMessage('assistant', `⚠️ ${error.message || error}`);
    } finally {
      setComposerDisabled(false);
    }
  });

  function appendMessage(role, content) {
    const article = document.createElement('article');
    const isUser = role === 'user';
    article.className = `msg${isUser ? ' msg--user' : ''}`;
    article.dataset.role = role;

    const avatar = document.createElement('div');
    avatar.className = 'msg__avatar';
    avatar.textContent = isUser ? 'U' : 'AI';

    const body = document.createElement('div');
    body.className = 'msg__content';
    body.innerHTML = `${escapeHtml(content).replace(/\n/g, '<br>')}
      <div class="msg__meta">${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</div>`;

    article.appendChild(avatar);
    article.appendChild(body);
    messages.appendChild(article);
    messages.scrollTop = messages.scrollHeight;
  }

  function setComposerDisabled(disabled) {
    textarea.disabled = disabled;
    if (sendButton) {
      sendButton.disabled = disabled;
    }
  }

  function setStatus(label) {
    if (statusPill) {
      statusPill.textContent = label;
    }
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  async function parseJson(response) {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error('Unexpected response from the server. Were you logged out?');
    }
  }
});
