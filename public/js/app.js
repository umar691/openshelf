(() => {
    const state = { filter: 'all', topic: '', query: '', contentId: null, recipientId: null, recipientName: '' };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const cards = [...document.querySelectorAll('.content-card')];
    const errorToast = document.getElementById('error-toast');
    let toastTimer;

    const showError = (message) => {
        errorToast.textContent = message;
        errorToast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { errorToast.hidden = true; }, 5000);
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...options.headers,
            },
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            throw new Error(payload?.message || Object.values(payload?.errors || {}).flat()[0] || 'That action could not be completed. Please try again.');
        }
        return response.json();
    };

    const openDialog = (id) => {
        const dialog = document.getElementById(id);
        if (dialog && !dialog.open) dialog.showModal();
    };

    const closeDialog = (element) => {
        const dialog = element.closest('dialog');
        if (dialog?.open) dialog.close();
    };

    const updateCards = () => {
        let visible = 0;
        cards.forEach((card) => {
            const isDraft = card.dataset.status === 'draft';
            const statusMatches = state.filter === 'draft' ? isDraft : !isDraft;
            const typeMatches = state.filter === 'all' || state.filter === 'draft' || card.dataset.type === state.filter;
            const topicMatches = !state.topic || card.dataset.topic.toLowerCase() === state.topic.toLowerCase();
            const queryMatches = !state.query || card.dataset.search.includes(state.query);
            card.hidden = !(statusMatches && typeMatches && topicMatches && queryMatches);
            if (!card.hidden) visible += 1;
        });
        const noResults = document.getElementById('no-results');
        noResults.textContent = visible
            ? ''
            : state.filter === 'draft'
                ? 'No private drafts yet. Save a draft while composing to come back to it later.'
                : 'No matches yet. Try another search or topic.';
        noResults.hidden = visible > 0;
    };

    updateCards();

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        if (button.hasAttribute('data-open-dialog')) {
            openDialog(button.dataset.openDialog);
            return;
        }
        if (button.hasAttribute('data-close-dialog')) {
            closeDialog(button);
            return;
        }
        if (button.hasAttribute('data-switch-dialog')) {
            closeDialog(button);
            openDialog(button.dataset.switchDialog);
            return;
        }
        if (button.hasAttribute('data-open-panel')) {
            const panel = button.dataset.openPanel;
            if (panel === 'profile') openDialog('profile-dialog');
            else if (!window.OpenShelf.authenticated) openDialog('login-dialog');
            else openDialog('chat-dialog');
            return;
        }
        if (button.hasAttribute('data-view') || button.hasAttribute('data-filter')) {
            const view = button.dataset.view || button.dataset.filter;
            state.filter = ['home', 'all'].includes(view) ? 'all' : view;
            state.topic = '';
            document.querySelectorAll('.nav-link').forEach((link) => link.classList.toggle('is-active', link.dataset.view === view || (view === 'all' && link.dataset.view === 'home')));
            document.querySelectorAll('.filter-pill').forEach((pill) => pill.classList.toggle('is-selected', pill.dataset.filter === state.filter));
            const titles = { all: 'Fresh from the community', novel: 'Stories & novels', research: 'Research & discoveries', resource: 'The study library', post: 'Community conversations', draft: 'Your private drafts' };
            document.getElementById('feed-heading').textContent = titles[state.filter] || titles.all;
            updateCards();
            document.getElementById('discover').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (button.hasAttribute('data-topic')) {
            state.topic = button.dataset.topic;
            state.filter = 'all';
            document.querySelectorAll('.filter-pill').forEach((pill) => pill.classList.toggle('is-selected', pill.dataset.filter === 'all'));
            document.getElementById('feed-heading').textContent = `Ideas about ${state.topic}`;
            updateCards();
            document.getElementById('discover').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (button.hasAttribute('data-read')) {
            const payload = JSON.parse(document.getElementById(`content-body-${button.dataset.read}`).textContent);
            document.getElementById('reader-title').textContent = payload.title;
            document.getElementById('reader-topic').textContent = payload.topic || payload.type;
            document.getElementById('reader-type').textContent = payload.type.toUpperCase();
            document.getElementById('reader-byline').textContent = `Shared by ${payload.author}`;
            document.getElementById('reader-summary').textContent = payload.summary || '';
            document.getElementById('reader-body').textContent = payload.body;
            openDialog('reader-dialog');
            return;
        }
        if (button.hasAttribute('data-publish')) {
            try {
                await request(`/content/${button.dataset.publish}/publish`, { method: 'POST' });
                window.location.reload();
            } catch (error) {
                showError(error.message);
            }
            return;
        }
        if (button.hasAttribute('data-save') || button.hasAttribute('data-react')) {
            if (!window.OpenShelf.authenticated) {
                openDialog('login-dialog');
                return;
            }
            const action = button.hasAttribute('data-save') ? 'bookmark' : 'reaction';
            try {
                const result = await request(`/content/${button.dataset[action === 'bookmark' ? 'save' : 'react']}/${action}`, { method: 'POST' });
                if (action === 'bookmark') {
                    button.classList.toggle('is-saved', result.saved);
                    button.textContent = result.saved ? '▣' : '▢';
                } else {
                    button.classList.toggle('is-liked', result.reacted);
                    button.querySelector('b').textContent = result.count;
                }
            } catch (error) {
                showError(error.message);
            }
            return;
        }
        if (button.hasAttribute('data-comments')) {
            state.contentId = button.dataset.comments;
            document.getElementById('comments-title').textContent = button.dataset.title;
            document.getElementById('comment-form').hidden = !window.OpenShelf.authenticated;
            document.getElementById('comment-auth').hidden = window.OpenShelf.authenticated;
            openDialog('comments-dialog');
            await loadComments();
            return;
        }
        if (button.hasAttribute('data-chat')) {
            if (!window.OpenShelf.authenticated) {
                openDialog('login-dialog');
                return;
            }
            state.recipientId = button.dataset.chat;
            state.recipientName = button.dataset.name || button.querySelector('strong')?.textContent || 'Community member';
            document.getElementById('chat-placeholder').hidden = true;
            document.getElementById('chat-active').hidden = false;
            document.getElementById('chat-recipient').textContent = state.recipientName;
            document.querySelectorAll('.contact-option').forEach((item) => item.classList.toggle('is-active', item.dataset.chat === state.recipientId));
            openDialog('chat-dialog');
            await loadMessages();
        }
    });

    document.getElementById('global-search').addEventListener('input', (event) => {
        state.query = event.target.value.trim().toLowerCase();
        updateCards();
    });

    const renderComment = (comment) => {
        const item = document.createElement('article');
        item.className = 'comment-item';
        const meta = document.createElement('div');
        meta.className = 'comment-meta';
        const name = document.createElement('span');
        name.textContent = comment.author.name;
        const date = document.createElement('time');
        date.textContent = new Date(comment.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        meta.append(name, date);
        const body = document.createElement('p');
        body.textContent = comment.body;
        item.append(meta, body);
        return item;
    };

    const loadComments = async () => {
        const list = document.getElementById('comments-list');
        list.replaceChildren();
        try {
            const comments = await request(`/api/content/${state.contentId}/comments`);
            if (!comments.length) {
                const empty = document.createElement('p');
                empty.className = 'comments-empty';
                empty.textContent = 'No responses yet. You could start a thoughtful conversation.';
                list.append(empty);
            } else {
                comments.forEach((comment) => list.append(renderComment(comment)));
            }
        } catch (error) {
            showError(error.message);
        }
    };

    document.getElementById('comment-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const body = new FormData(form).get('body').toString().trim();
        try {
            const comment = await request(`/api/content/${state.contentId}/comments`, { method: 'POST', body: JSON.stringify({ body }) });
            const list = document.getElementById('comments-list');
            list.querySelector('.comments-empty')?.remove();
            list.append(renderComment(comment));
            const count = document.querySelector(`[data-comments="${state.contentId}"] b`);
            count.textContent = Number(count.textContent) + 1;
            form.reset();
        } catch (error) {
            showError(error.message);
        }
    });

    const renderMessage = (message) => {
        const bubble = document.createElement('div');
        bubble.className = `message-bubble${Number(message.sender_id) === Number(window.OpenShelf.userId) ? ' is-mine' : ''}`;
        bubble.append(document.createTextNode(message.body));
        const time = document.createElement('time');
        time.className = 'message-time';
        time.textContent = new Date(message.created_at).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        bubble.append(time);
        return bubble;
    };

    const loadMessages = async () => {
        if (!state.recipientId) return;
        try {
            const result = await request(`/api/chat/${state.recipientId}`);
            const messages = document.getElementById('chat-messages');
            messages.replaceChildren(...result.messages.map(renderMessage));
            messages.scrollTop = messages.scrollHeight;
        } catch (error) {
            showError(error.message);
        }
    };

    document.getElementById('chat-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!state.recipientId) return;
        const form = event.currentTarget;
        const body = new FormData(form).get('body').toString().trim();
        try {
            const message = await request(`/api/chat/${state.recipientId}`, { method: 'POST', body: JSON.stringify({ body }) });
            document.getElementById('chat-messages').append(renderMessage(message));
            document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
            form.reset();
        } catch (error) {
            showError(error.message);
        }
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    setInterval(() => {
        if (document.getElementById('chat-dialog').open && state.recipientId) loadMessages();
    }, 8000);

    const firstError = document.querySelector('.validation-errors');
    if (firstError) openDialog(firstError.closest('dialog')?.id || 'login-dialog');
    else if (window.OpenShelf.dialog) openDialog(window.OpenShelf.dialog);
})();
