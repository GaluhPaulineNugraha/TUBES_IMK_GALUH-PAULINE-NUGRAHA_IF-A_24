function formatDate() {
    const options = { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' };
    return new Date().toLocaleDateString('id-ID', options);
}

function updateDateTime() {
    document.querySelectorAll('#currentDate').forEach(el => { if(el) el.textContent = formatDate(); });
}

updateDateTime();