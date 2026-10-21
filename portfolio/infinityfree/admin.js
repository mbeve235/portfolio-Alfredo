// Mantém o envio nativo e usa multipart apenas quando existe um ficheiro.
// Sem JavaScript, os formulários continuam a suportar uploads normalmente.
document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(form => {
    form.addEventListener('submit', () => {
        const hasFile = Array.from(form.querySelectorAll('input[type="file"]'))
            .some(input => input.files && input.files.length > 0);
        form.enctype = hasFile ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
    });
});
