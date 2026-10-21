// Mantém o envio nativo e usa multipart apenas quando existe um ficheiro.
// Sem JavaScript, os formulários continuam a suportar uploads normalmente.
document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(form => {
    form.addEventListener('submit', () => {
        const hasFile = Array.from(form.querySelectorAll('input[type="file"]'))
            .some(input => input.files && input.files.length > 0);
        form.enctype = hasFile ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
    });
});

const menuButton = document.querySelector('.menu-toggle');
const sidebar = document.getElementById('sidebar');
menuButton?.addEventListener('click', () => {
    menuButton.setAttribute('aria-expanded', String(sidebar.classList.toggle('is-open')));
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
        sidebar.classList.remove('is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.focus();
    }
});
document.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', () => {
    document.querySelectorAll('.nav-link').forEach(item => item.classList.toggle('active', item === link));
    sidebar.classList.remove('is-open');
    menuButton.setAttribute('aria-expanded', 'false');
}));
document.querySelectorAll('[data-new-project]').forEach(link => link.addEventListener('click', () => {
    const editor = document.getElementById('new-project');
    editor.open = true;
    setTimeout(() => editor.querySelector('input[name="title"]').focus({preventScroll: true}), 100);
}));
document.getElementById('project-search')?.addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase();
    const editors = Array.from(document.querySelectorAll('.project-editor:not(.new-project)'));
    let visible = 0;
    editors.forEach(editor => {
        editor.hidden = !editor.dataset.projectTitle.includes(query);
        if (!editor.hidden) visible++;
    });
    document.getElementById('project-count').textContent = `${visible} de ${editors.length} projectos`;
    document.getElementById('empty-search').hidden = visible > 0;
});
document.querySelectorAll('[data-tags-for]').forEach(preview => {
    const input = document.querySelector(`input[name="${preview.dataset.tagsFor}"]`);
    input.addEventListener('input', () => {
        preview.replaceChildren(...input.value.split(',').map(tag => tag.trim()).filter(Boolean).map(tag => {
            const span = document.createElement('span');
            span.textContent = tag;
            return span;
        }));
    });
});
document.querySelectorAll('[data-image-input]').forEach(input => {
    let previewUrl;
    input.addEventListener('change', () => {
        const wrapper = input.closest('.image-upload');
        const feedback = wrapper.querySelector('.file-feedback');
        const file = input.files[0];
        input.setCustomValidity('');
        feedback.textContent = '';
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            input.setCustomValidity('Selecciona uma imagem JPG, PNG ou WebP até 2 MB.');
            feedback.textContent = input.validationMessage;
            input.reportValidity();
            return;
        }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        const image = document.createElement('img');
        image.src = previewUrl;
        image.alt = 'Pré-visualização da imagem seleccionada';
        wrapper.querySelector('.image-preview').replaceChildren(image);
        feedback.textContent = `${file.name} · ${(file.size / 1024).toFixed(0)} KB · Guarda para publicar`;
    });
});
const dirtyForms = new Set();
document.querySelectorAll('[data-edit-form]').forEach(form => {
    form.addEventListener('input', () => {
        dirtyForms.add(form);
        if (form.id === 'profile-form') {
            document.querySelector('.save-bar').classList.add('dirty');
            document.querySelector('[data-save-state]').textContent = 'Tens alterações por guardar';
        }
    });
    form.addEventListener('submit', () => dirtyForms.delete(form));
});
window.addEventListener('beforeunload', event => {
    if (dirtyForms.size) { event.preventDefault(); event.returnValue = ''; }
});
