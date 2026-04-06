/**
 * ElectroHub - User Orders Logic
 * Handles Proof Upload Preview and Complaint Chatbox
 */

document.addEventListener('DOMContentLoaded', () => {
    if (window.Logger) Logger.info('User Orders: Logic Initialized');
    
    // Biến toàn cục lưu trạng thái modal
    window.currentUploadOrderId = null;
    window.selectedReceiptFile = null;

    window.openUploadModal = function(orderId) {
        window.currentUploadOrderId = orderId;
        window.selectedReceiptFile = null;
        document.getElementById('uploadModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        resetUploadArea(document.querySelector('#uploadModal .border-dashed'));
    };

    // 1. UPLOAD PROOF PREVIEW LOGIC
    const uploadArea = document.querySelector('#uploadModal .border-dashed');
    const uploadInput = document.createElement('input');
    uploadInput.type = 'file';
    uploadInput.accept = 'image/*';
    uploadInput.className = 'hidden';
    document.body.appendChild(uploadInput);

    if (uploadArea) {
        uploadArea.addEventListener('click', () => uploadInput.click());

        uploadInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            window.selectedReceiptFile = file;
            if (file) {
                if (window.Logger) Logger.debug('User Orders: File selected for proof upload', { filename: file.name, size: file.size });
                if (typeof showToast === 'function') showToast(`Đã đính kèm tệp: ${file.name}`, 'success');
                
                const previewUrl = URL.createObjectURL(file);
                
                // Replace icon with image preview
                uploadArea.innerHTML = `
                    <div class="relative w-full h-48 rounded-2xl overflow-hidden shadow-inner bg-slate-100">
                        <img src="${previewUrl}" class="w-full h-full object-contain p-2">
                        <button class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600 transition-all" onclick="event.stopPropagation(); resetUploadArea(this)">
                            <i data-lucide="trash-2" size="16"></i>
                        </button>
                    </div>
                    <p class="text-xs font-bold text-blue-600 mt-4">Sẵn sàng tải lên: ${file.name}</p>
                `;
                lucide.createIcons();
            }
        });
    }

    // 1.1 SUBMIT UPLOAD LOGIC
    const submitUploadBtn = document.querySelector('#uploadModal button.bg-blue-600');
    if (submitUploadBtn) {
        submitUploadBtn.addEventListener('click', async () => {
            if (!window.selectedReceiptFile) {
                showToast('Vui lòng chọn ảnh biên lai trước', 'warning');
                return;
            }
            if (!window.currentUploadOrderId) return;

            const formData = new FormData();
            formData.append('receipt', window.selectedReceiptFile);

            try {
                const user = AuthManager.getUser();
                submitUploadBtn.innerText = 'Đang tải lên...';
                submitUploadBtn.disabled = true;

                const response = await fetch(`${window.API_BASE}/api/user/orders/${window.currentUploadOrderId}/upload-receipt`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${user.token}` },
                    // Fetch tự động set boundary cho Content-Type khi body là FormData
                    body: formData
                });

                const result = await response.json();
                if (response.ok) {
                    showToast(result.message, 'success');
                    closeModal('uploadModal');
                    if (typeof fetchUserOrders === 'function') fetchUserOrders(); // Làm mới danh sách
                } else { throw new Error(result.message); }
            } catch (error) {
                showToast(error.message || 'Lỗi khi tải lên', 'error');
            } finally {
                submitUploadBtn.innerText = 'Xác nhận tải lên';
                submitUploadBtn.disabled = false;
            }
        });
    }

    // 2. COMPLAINT CHAT LOGIC
    const chatInput = document.querySelector('#complaintModal input');
    const chatBtn = document.querySelector('#complaintModal button.bg-blue-600');
    const chatContainer = document.querySelector('#complaintModal .overflow-y-auto');

    if (chatBtn && chatInput && chatContainer) {
        const sendMessage = () => {
            const text = chatInput.value.trim();
            if (text) {
                if (window.Logger) Logger.info('User Orders: Sending complaint message', { messageText: text });

                // Create User Message Element
                const msgDiv = document.createElement('div');
                msgDiv.className = 'flex gap-3 flex-row-reverse animate-fade-in';
                msgDiv.innerHTML = `
                    <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white shrink-0"><i data-lucide="user" size="14"></i></div>
                    <div class="bg-blue-600 p-4 rounded-2xl rounded-tr-none shadow-sm text-white max-w-[80%]">
                        <p class="text-sm font-medium leading-relaxed">${text}</p>
                    </div>
                `;
                
                chatContainer.appendChild(msgDiv);
                chatInput.value = '';
                lucide.createIcons();
                
                // Auto-scroll to bottom
                chatContainer.scrollTo({
                    top: chatContainer.scrollHeight,
                    behavior: 'smooth'
                });

                // Simulate Admin Reply (Optional but adds to UX)
                setTimeout(() => {
                    const replyDiv = document.createElement('div');
                    replyDiv.className = 'flex gap-3 animate-fade-in';
                    replyDiv.innerHTML = `
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white shrink-0"><i data-lucide="user" size="14"></i></div>
                        <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-slate-100 max-w-[80%]">
                            <p class="text-sm text-slate-800 font-medium leading-relaxed">Cảm ơn bạn đã phản hồi. Chúng tôi đang kiểm tra lại giao dịch này và sẽ phản hồi sớm nhất có thể.</p>
                        </div>
                    `;
                    chatContainer.appendChild(replyDiv);
                    lucide.createIcons();
                    chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
                }, 1500);
            }
        };

        chatBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }
});

// Helper to reset upload area
window.resetUploadArea = (btn) => {
    const area = btn.closest('.border-dashed');
    area.innerHTML = `
        <i data-lucide="image-plus" class="mx-auto text-slate-400 group-hover:text-blue-600 mb-4" size="48"></i>
        <p class="text-sm font-bold text-slate-600 group-hover:text-blue-600">Nhấp để chọn ảnh hoặc kéo thả</p>
        <p class="text-xs text-slate-400 mt-2">Định dạng: JPG, PNG (Tối đa 5MB)</p>
    `;
    lucide.createIcons();
};
