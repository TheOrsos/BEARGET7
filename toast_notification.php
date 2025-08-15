<!-- 
================================================================================
File: toast_notification.php
Descrizione: Componente per mostrare notifiche pop-up (toast) con effetto fade.
            Questo file contiene solo la struttura HTML. La logica JS è gestita
            dalla pagina principale che lo include.
================================================================================
-->
<div id="toast-notification" class="fixed bottom-5 right-5 w-full max-w-xs p-4 rounded-lg shadow-lg text-white transition-opacity duration-300 ease-in-out opacity-0 hidden" role="alert">
    <div class="flex items-center">
        <div id="toast-icon" class="mr-3">
            <!-- L'icona viene inserita qui da JavaScript -->
        </div>
        <div id="toast-message" class="text-sm font-semibold">
            <!-- Il messaggio viene inserito qui da JavaScript -->
        </div>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 hover:bg-white/20 inline-flex h-8 w-8" onclick="document.getElementById('toast-notification').classList.add('hidden')">
            <span class="sr-only">Chiudi</span>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
        </button>
    </div>
</div>