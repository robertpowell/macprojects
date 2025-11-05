/**
 * Admin Panel JavaScript Utilities
 */

// Confirmation dialogs
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation to delete buttons
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.getAttribute('onsubmit').match(/'([^']+)'/)[1])) {
                e.preventDefault();
                return false;
            }
        });
    });
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Table row highlighting
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        row.addEventListener('click', function() {
            this.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
                this.style.backgroundColor = '';
            }, 1000);
        });
    });
});

// Form validation
function validateQuizUpload() {
    const topicId = document.getElementById('topic_id').value;
    const quizName = document.getElementById('quiz_name').value;
    const quizFile = document.getElementById('quiz_file').value;

    if (!topicId) {
        alert('Please select a topic');
        return false;
    }

    if (!quizName.trim()) {
        alert('Please enter a quiz name');
        return false;
    }

    if (!quizFile) {
        alert('Please select a quiz file');
        return false;
    }

    return true;
}

// Character counter for text inputs
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        if (textarea.maxLength > 0) {
            const counter = document.createElement('div');
            counter.style.fontSize = '0.875rem';
            counter.style.color = '#666';
            counter.style.marginTop = '0.25rem';
            textarea.parentNode.appendChild(counter);

            const updateCounter = () => {
                const remaining = textarea.maxLength - textarea.value.length;
                counter.textContent = `${remaining} characters remaining`;
            };

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
});

// Copy to clipboard utility
function copyToClipboard(text) {
    const temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert('Copied to clipboard!');
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Loading spinner utility
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'loading-spinner';
    element.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) spinner.remove();
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '1rem';
    notification.style.right = '1rem';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Print functionality
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link rel="stylesheet" href="/assets/css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(element.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
