window.onload = () => {
    const toDisable = [
        '.user-first-name-wrap input',
        '.user-last-name-wrap input',
        '.user-nickname-wrap input',
        '.user-display-name-wrap select',
        '.user-url-wrap input'
    ];
    toDisable.forEach(e => document.querySelector(e).readOnly = true);
};