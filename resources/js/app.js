(() => {
    window.AiSecurityGuardian = window.AiSecurityGuardian || {};

    document.addEventListener('click', async (event) => {
        const copyButton = event.target.closest('[data-copy]');
        if (!copyButton) {
            return;
        }

        const target = document.querySelector(copyButton.dataset.copy);
        if (!target) {
            return;
        }

        try {
            await navigator.clipboard.writeText(target.value ?? target.textContent ?? '');
            copyButton.textContent = 'Copied';
            window.setTimeout(() => {
                copyButton.textContent = 'Copy summary';
            }, 1200);
        } catch (error) {
            copyButton.textContent = 'Copy failed';
            window.setTimeout(() => {
                copyButton.textContent = 'Copy summary';
            }, 1200);
        }
    });
})();
