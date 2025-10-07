// js/pending_coaches.js

document.addEventListener('DOMContentLoaded', () => {
    // Select all the relevant elements
    const registrationsList = document.getElementById('registrations-list');
    const pendingCountSpan = document.getElementById('pending-count');
    const noPendingMessageContainer = document.getElementById('no-pending-message-container');
    const alertContainer = document.getElementById('alert-container');

    // Modals
    const approveModalEl = document.getElementById('approveModal');
    const rejectModalEl = document.getElementById('rejectModal');
    const approveModal = new bootstrap.Modal(approveModalEl);
    const rejectModal = new bootstrap.Modal(rejectModalEl);

    let currentRegistrationId = null;

    // Show a dynamic alert message
    const showAlert = (message, type) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible fade show" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        alertContainer.append(wrapper);
        setTimeout(() => wrapper.remove(), 5000);
    };

    // Update the UI state based on the number of pending registrations
    const updateUI = () => {
        const pendingCards = registrationsList.querySelectorAll('.card.mb-3');
        const count = pendingCards.length;
        pendingCountSpan.textContent = count;
        if (count === 0) {
            registrationsList.style.display = 'none';
            noPendingMessageContainer.style.display = 'block';
        }
    };

    // Use event delegation for approve/reject buttons
    registrationsList.addEventListener('click', (e) => {
        const approveButton = e.target.closest('.approve-btn');
        const rejectButton = e.target.closest('.reject-btn');

        if (approveButton) {
            currentRegistrationId = approveButton.dataset.id;
            const name = approveButton.dataset.name;
            const teamName = approveButton.dataset.teamName;
            
            document.getElementById('approve-name').textContent = name;
            document.getElementById('approve-team-name').textContent = teamName;
            approveModal.show();
        } else if (rejectButton) {
            currentRegistrationId = rejectButton.dataset.id;
            const name = rejectButton.dataset.name;

            document.getElementById('reject-name').textContent = name;
            document.getElementById('reject-id').value = currentRegistrationId;
            document.getElementById('rejection-reason').value = ''; // Clear previous input
            document.getElementById('rejection-reason').classList.remove('is-invalid'); // Clear validation
            rejectModal.show();
        }
    });

    // Clear currentRegistrationId when modals are hidden to prevent stale data
    approveModalEl.addEventListener('hidden.bs.modal', () => {
        currentRegistrationId = null;
    });

    rejectModalEl.addEventListener('hidden.bs.modal', () => {
        currentRegistrationId = null;
    });

    // Handler for confirming the approval
    document.getElementById('confirmApproveBtn').addEventListener('click', () => {
        if (!currentRegistrationId) return;

        // Simulate a loading state
        const originalText = document.getElementById('confirmApproveBtn').textContent;
        const button = document.getElementById('confirmApproveBtn');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Approving...';

        fetch('pending_coaches.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
            },
            body: `action=approve&registration_id=${currentRegistrationId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                document.getElementById(`registration-card-${data.registration_id}`).remove();
                showAlert(data.message, 'success');
                updateUI();
            } else {
                showAlert(data.message || 'An unknown error occurred.', 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred while processing the request. Please try again.', 'danger');
            console.error('Error:', error);
        })
        .finally(() => {
            approveModal.hide();
            button.disabled = false;
            button.innerHTML = originalText;
        });
    });

    // Handler for confirming the rejection
    document.getElementById('confirmRejectBtn').addEventListener('click', () => {
        if (!currentRegistrationId) return;

        const rejectionReasonInput = document.getElementById('rejection-reason');
        const rejectionReason = rejectionReasonInput.value.trim();

        if (rejectionReason === '') {
            rejectionReasonInput.classList.add('is-invalid');
            return;
        }

        const originalText = document.getElementById('confirmRejectBtn').textContent;
        const button = document.getElementById('confirmRejectBtn');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Rejecting...';

        fetch('pending_coaches.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=reject&registration_id=${currentRegistrationId}&rejection_reason=${encodeURIComponent(rejectionReason)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                document.getElementById(`registration-card-${data.registration_id}`).remove();
                showAlert(data.message, 'success');
                updateUI();
            } else {
                showAlert(data.message || 'An unknown error occurred.', 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred while processing the request. Please try again.', 'danger');
            console.error('Error:', error);
        })
        .finally(() => {
            rejectModal.hide();
            button.disabled = false;
            button.innerHTML = originalText;
        });
    });

    // Initial check on page load
    updateUI();
});