document.getElementById('tambahMenuBtn')?.addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'TAMBAH MENU';
    document.getElementById('formAction').value = 'add';
    document.getElementById('editId').value = '';
    document.getElementById('menuName').value = '';
    document.getElementById('menuCategory').value = '1';
    document.getElementById('menuPrice').value = '';
    document.getElementById('menuStock').value = '';
    document.getElementById('menuModal').style.display = 'flex';
});

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'EDIT MENU';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('menuName').value = btn.dataset.name;
        document.getElementById('menuCategory').value = btn.dataset.category;
        document.getElementById('menuPrice').value = btn.dataset.price;
        document.getElementById('menuStock').value = btn.dataset.stock;
        document.getElementById('menuModal').style.display = 'flex';
    });
});

document.querySelectorAll('.btn-delete-stok').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteMenuName').textContent = btn.dataset.name;
        document.getElementById('deleteMenuModal').style.display = 'flex';
        document.getElementById('confirmDeleteBtn').onclick = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + btn.dataset.id + '">';
            document.body.appendChild(form);
            form.submit();
        };
    });
});

document.querySelectorAll('.modal .close, #cancelMenuBtn, #cancelDeleteBtn').forEach(el => {
    el.addEventListener('click', () => {
        document.getElementById('menuModal').style.display = 'none';
        document.getElementById('deleteMenuModal').style.display = 'none';
    });
});