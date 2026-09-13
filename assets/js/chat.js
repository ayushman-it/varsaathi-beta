/**
 * VARSAATHI Dating App - Ultra-Reliable Real-Time Live Chat Engine
 * Features:
 * - Instant optimistic UI & deduplicated sync
 * - Swipe / Slide to Reply gesture (Touch drag right to quote message)
 * - Quoted message context bar & preview
 * - iOS Chat Themes support
 */

document.addEventListener('DOMContentLoaded', () => {
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const chatMessages = document.getElementById('chatMessages');
  const mediaInput = document.getElementById('mediaUploadInput');
  const replyPreviewBar = document.getElementById('replyPreviewBar');

  if (!chatMessages || !chatForm) return;

  const matchId = chatForm.dataset.matchId;
  const currentUserId = parseInt(chatForm.dataset.userId, 10);
  const partnerName = chatForm.dataset.partnerName || 'Partner';

  let lastMessageId = 0;
  let isFetching = false;
  let activePollInterval = null;
  const renderedMsgIds = new Set();
  const msgElementMap = new Map(); // id -> DOM element
  let activeReplyPayload = null;

  // Smart Auto Scroll
  function scrollToBottom(force = false) {
    const threshold = 140; // px from bottom
    const isNearBottom = (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) <= threshold;
    if (force || isNearBottom) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }

  // Unlock full chat and calling features once match is accepted
  function unlockAcceptedChat() {
    const matchRequestBar = document.getElementById('matchRequestBar');
    if (matchRequestBar) matchRequestBar.remove();

    chatForm.style.display = 'flex';
    chatForm.dataset.matchStatus = 'accepted';

    const audioCallBtn = document.getElementById('audioCallBtn');
    const videoCallBtn = document.getElementById('videoCallBtn');

    if (audioCallBtn) {
      audioCallBtn.classList.remove('disabled');
      audioCallBtn.style.opacity = '1';
      audioCallBtn.style.cursor = 'pointer';
    }
    if (videoCallBtn) {
      videoCallBtn.classList.remove('disabled');
      videoCallBtn.style.opacity = '1';
      videoCallBtn.style.cursor = 'pointer';
    }
  }

  // Fetch messages from backend
  function fetchMessages() {
    if (isFetching) return;
    isFetching = true;

    fetch(`api/chat.php?action=fetch&match_id=${matchId}&last_id=${lastMessageId}`)
      .then(res => res.json())
      .then(data => {
        isFetching = false;
        if (!data.success) return;

        if (data.match_status === 'accepted' && chatForm.style.display === 'none') {
          unlockAcceptedChat();
        }

        let hasNewMessages = false;

        if (data.messages && data.messages.length > 0) {
          data.messages.forEach(msg => {
            const msgId = parseInt(msg.id, 10);
            if (!renderedMsgIds.has(msgId)) {
              appendMessage(msg);
              hasNewMessages = true;
              lastMessageId = Math.max(lastMessageId, msgId);
            }
          });
        }

        if (data.read_ids && Array.isArray(data.read_ids)) {
          data.read_ids.forEach(id => {
            const el = msgElementMap.get(parseInt(id, 10));
            if (el) {
              const tick = el.querySelector('.msg-status-tick');
              if (tick && !tick.classList.contains('read')) {
                tick.className = 'msg-status-tick read';
                tick.innerHTML = '<i class="fa-solid fa-check-double"></i>';
              }
            }
          });
        }

        if (hasNewMessages) {
          scrollToBottom();
        }
      })
      .catch(err => {
        isFetching = false;
        console.warn('[Chat Engine] Fetch error:', err);
      });
  }

  // Render message bubble into chat feed & attach Slide to Reply touch gesture
  function appendMessage(msg) {
    const msgId = parseInt(msg.id, 10);
    if (renderedMsgIds.has(msgId)) return;
    renderedMsgIds.add(msgId);

    const isSent = parseInt(msg.sender_id, 10) === currentUserId;
    const bubbleWrapper = document.createElement('div');
    bubbleWrapper.className = `msg-bubble-wrapper ${isSent ? 'sent' : 'received'}`;
    bubbleWrapper.dataset.msgId = msgId;

    const bubble = document.createElement('div');
    bubble.className = `msg-bubble ${isSent ? 'sent' : 'received'}`;

    const date = msg.created_at ? new Date(msg.created_at) : new Date();
    const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    let statusTickHtml = '';
    if (isSent) {
      const isRead = parseInt(msg.is_read, 10) === 1;
      const tickClass = isRead ? 'msg-status-tick read' : 'msg-status-tick delivered';
      const tickIcon = isRead ? '<i class="fa-solid fa-check-double"></i>' : '<i class="fa-solid fa-check"></i>';
      statusTickHtml = `<span class="${tickClass}">${tickIcon}</span>`;
    }

    // Clean plain text snippet for reply
    const rawContent = msg.message_text;
    const formattedContent = formatMessageContent(rawContent);
    const plainSnippet = stripHtml(rawContent).substring(0, 80);

    bubble.innerHTML = `
      <div class="msg-content">${formattedContent}</div>
      <div class="msg-footer-row">
        <span class="msg-time">${timeStr}</span>
        ${statusTickHtml}
      </div>
    `;

    // Slide to Reply Icon indicator
    const replyIcon = document.createElement('div');
    replyIcon.className = 'swipe-reply-icon';
    replyIcon.innerHTML = '<i class="fa-solid fa-reply"></i>';

    bubbleWrapper.appendChild(replyIcon);
    bubbleWrapper.appendChild(bubble);

    // ATTACH SLIDE TO REPLY TOUCH GESTURE
    attachSwipeToReply(bubbleWrapper, msgId, isSent ? 'You' : partnerName, plainSnippet);

    chatMessages.appendChild(bubbleWrapper);
    msgElementMap.set(msgId, bubble);
  }

  // Slide / Swipe to Reply Touch Gesture Implementation
  function attachSwipeToReply(wrapper, msgId, senderName, snippet) {
    let startX = 0;
    let currentX = 0;
    let isSwiping = false;

    wrapper.addEventListener('touchstart', (e) => {
      if (e.touches.length !== 1) return;
      startX = e.touches[0].clientX;
      isSwiping = true;
    }, { passive: true });

    wrapper.addEventListener('touchmove', (e) => {
      if (!isSwiping) return;
      currentX = e.touches[0].clientX;
      const diffX = currentX - startX;

      // Only allow swipe to right
      if (diffX > 0 && diffX <= 100) {
        wrapper.style.transform = `translateX(${diffX}px)`;
        const replyIcon = wrapper.querySelector('.swipe-reply-icon');
        if (replyIcon) {
          replyIcon.style.opacity = Math.min(1, diffX / 50);
          replyIcon.style.transform = `scale(${Math.min(1.2, diffX / 50)})`;
        }
      }
    }, { passive: true });

    wrapper.addEventListener('touchend', (e) => {
      if (!isSwiping) return;
      isSwiping = false;
      const diffX = currentX - startX;

      wrapper.style.transform = 'translateX(0)';
      const replyIcon = wrapper.querySelector('.swipe-reply-icon');
      if (replyIcon) replyIcon.style.opacity = '0';

      // Trigger Reply Mode if swiped right >= 50px
      if (diffX >= 50) {
        if ('vibrate' in navigator) {
          try { navigator.vibrate(30); } catch(err) {}
        }
        triggerReplyMode(msgId, senderName, snippet);
      }
      startX = 0;
      currentX = 0;
    }, { passive: true });
  }

  // Activate Reply Mode
  window.triggerReplyMode = function(msgId, senderName, snippet) {
    activeReplyPayload = {
      msgId: msgId,
      senderName: senderName,
      snippet: snippet
    };

    if (replyPreviewBar) {
      document.getElementById('replySenderName').textContent = `Replying to ${senderName}`;
      document.getElementById('replyTextSnippet').textContent = `"${snippet}"`;
      replyPreviewBar.style.display = 'block';
    }
    chatInput.focus();
  };

  // Cancel Reply Mode
  window.cancelReplyMode = function() {
    activeReplyPayload = null;
    if (replyPreviewBar) {
      replyPreviewBar.style.display = 'none';
    }
  };

  function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
  }

  function formatMessageContent(rawText) {
    if (!rawText) return '';
    // If rawText has escaped quote HTML entity tags, unescape quote box safely
    let text = rawText.replace(/&lt;div class="msg-quote-box"&gt;&lt;div class="quote-author"&gt;(.*?)&lt;\/div&gt;&lt;div class="quote-text"&gt;(.*?)&lt;\/div&gt;&lt;\/div&gt;/g, (match, author, snippet) => {
      return `<div class="msg-quote-box"><div class="quote-author">${author}</div><div class="quote-text">${snippet}</div></div>`;
    });
    return text;
  }

  // Render Optimistic Pending Message
  function appendPendingMessage(tempId, text, mediaPreviewUrl = null) {
    const bubbleWrapper = document.createElement('div');
    bubbleWrapper.className = 'msg-bubble-wrapper sent';
    bubbleWrapper.id = `pendingMsg_${tempId}`;

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble sent-pending';

    const date = new Date();
    const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    let contentHtml = '';
    const quoteEndIdx = text.indexOf('</div></div>');
    if (text.startsWith('<div class="msg-quote-box">') && quoteEndIdx !== -1) {
      const quoteHtml = text.substring(0, quoteEndIdx + 12);
      const userText = text.substring(quoteEndIdx + 12);
      contentHtml = quoteHtml + escapeHtml(userText).replace(/\n/g, '<br>');
    } else {
      contentHtml = escapeHtml(text).replace(/\n/g, '<br>');
    }

    if (mediaPreviewUrl) {
      contentHtml += `<br><img src="${mediaPreviewUrl}" style="max-width:100%; border-radius:12px; margin-top:4px; display:block;">`;
    }

    bubble.innerHTML = `
      <div class="msg-content">${contentHtml}</div>
      <div class="msg-footer-row">
        <span class="msg-time">${timeStr}</span>
        <span class="msg-status-tick" style="opacity:0.75;"><i class="fa-solid fa-spinner fa-spin"></i></span>
      </div>
    `;

    bubbleWrapper.appendChild(bubble);
    chatMessages.appendChild(bubbleWrapper);
    scrollToBottom(true);
    return bubbleWrapper;
  }

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // Form Submission
  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    let text = chatInput.value.trim();
    const hasMedia = mediaInput && mediaInput.files && mediaInput.files.length > 0;

    if (!text && !hasMedia) return;

    // Attach Quoted Reply HTML if active
    if (activeReplyPayload) {
      const quoteHtml = `<div class="msg-quote-box"><div class="quote-author">${escapeHtml(activeReplyPayload.senderName)}</div><div class="quote-text">"${escapeHtml(activeReplyPayload.snippet)}"</div></div>`;
      text = quoteHtml + text;
      cancelReplyMode();
    }

    const tempId = Date.now();
    let mediaPreviewUrl = null;
    if (hasMedia && mediaInput.files[0].type.startsWith('image/')) {
      mediaPreviewUrl = URL.createObjectURL(mediaInput.files[0]);
    }

    const pendingWrapper = appendPendingMessage(tempId, text, mediaPreviewUrl);

    const formData = new FormData();
    formData.append('match_id', matchId);
    formData.append('message_text', text);

    if (hasMedia) {
      formData.append('media', mediaInput.files[0]);
    }

    chatInput.value = '';
    if (mediaInput) mediaInput.value = '';

    fetch('api/chat.php?action=send', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.message) {
        pendingWrapper.remove();
        appendMessage(data.message);
        lastMessageId = Math.max(lastMessageId, parseInt(data.message.id, 10));
        scrollToBottom(true);
      } else {
        markPendingError(pendingWrapper);
      }
    })
    .catch(err => {
      console.error('[Chat Engine] Send error:', err);
      markPendingError(pendingWrapper);
    });
  });

  function markPendingError(wrapper) {
    if (!wrapper) return;
    const bubble = wrapper.querySelector('.msg-bubble');
    if (bubble) {
      bubble.classList.remove('sent-pending');
      bubble.classList.add('sent');
      const tick = bubble.querySelector('.msg-status-tick');
      if (tick) {
        tick.style.color = '#FF3B30';
        tick.innerHTML = '<i class="fa-solid fa-circle-exclamation" title="Failed to send. Tap to retry"></i>';
      }
    }
  }

  if (mediaInput) {
    mediaInput.addEventListener('change', () => {
      if (mediaInput.files.length > 0) {
        chatForm.dispatchEvent(new Event('submit'));
      }
    });
  }

  // Dynamic Polling
  function startPolling(intervalMs) {
    if (activePollInterval) clearInterval(activePollInterval);
    activePollInterval = setInterval(fetchMessages, intervalMs);
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      startPolling(8000);
    } else {
      fetchMessages();
      startPolling(1200);
    }
  });

  fetchMessages();
  startPolling(1200);
});
