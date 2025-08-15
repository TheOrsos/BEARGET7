</main>
    </div>

    <script>
        // --- LOGICA SIDEBAR RESPONSIVE ---
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const menuButton = document.getElementById('menu-button');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');

            const toggleSidebar = () => {
                if (sidebar) {
                    sidebar.classList.toggle('-translate-x-full');
                }
                if (sidebarBackdrop) {
                    sidebarBackdrop.classList.toggle('hidden');
                }
            };

            if (menuButton) {
                menuButton.addEventListener('click', toggleSidebar);
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', toggleSidebar);
            }
        });

        // --- FUNZIONI GLOBALI PER MODALI ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                if (backdrop) backdrop.classList.remove('opacity-0');
                if (content) content.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            if (backdrop) backdrop.classList.add('opacity-0');
            if (content) content.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // --- FUNZIONE TOAST GLOBALE ---
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            if (!toast) {
                console.warn('Toast notification element not found.');
                return;
            }
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            if (toastMessage) toastMessage.textContent = message;

            toast.classList.remove('bg-success', 'bg-danger', 'hidden', 'opacity-0');

            if (type === 'success') {
                toast.classList.add('bg-success');
                if (toastIcon) toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>`;
            } else {
                toast.classList.add('bg-danger');
                if (toastIcon) toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path></svg>`;
            }

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 5000);
        }

        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>