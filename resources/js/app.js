// Application scripts. Bootstrap 5 and AdminLTE are loaded by the layout.

// Ask for confirmation on forms flagged with data-confirm.
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
