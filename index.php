<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bearget - Login & Registrazione</title>
    <!-- Tailwind CSS per uno stile rapido e moderno -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Inter di Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-2xl shadow-lg">

        <div class="text-center">
            <svg class="mx-auto h-12 w-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m12-3h.008v.008H15V6z" />
            </svg>
            <h2 id="form-title" class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                Accedi al tuo account
            </h2>
            <p id="form-subtitle" class="mt-2 text-sm text-gray-600">
                Oppure <button type="button" id="switch-to-register" class="font-medium text-indigo-600 hover:text-indigo-500">crea un nuovo account</button>
            </p>
        </div>

        <!-- Messaggi di Errore/Successo -->
        <div id="message-container" class="hidden p-4 text-sm rounded-lg"></div>

        <!-- NUOVO: Contenitore per il link di verifica manuale -->
        <div id="verification-link-container" class="hidden text-center"></div>

        <!-- Modulo di Login -->
        <!-- Modulo di Login -->
        <form id="login-form" class="mt-8 space-y-6" action="login.php" method="POST">
            <input type="hidden" name="remember" value="true">
            <div class="space-y-4 rounded-md shadow-sm">
                <div>
                    <label for="login-email-address" class="sr-only">Indirizzo email</label>
                    <input id="login-email-address" name="email" type="email" autocomplete="email" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Indirizzo email">
                </div>
                <div>
                    <label for="login-password" class="sr-only">Password</label>
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-3 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Accedi
                </button>
            </div>
        </form>

        <!-- Modulo di Registrazione -->
        <form id="register-form" class="mt-8 space-y-6 hidden" action="register.php" method="POST">
            <div class="space-y-4 rounded-md shadow-sm">
                <div>
                    <label for="register-username" class="sr-only">Username</label>
                    <input id="register-username" name="username" type="text" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Username">
                </div>
                <div>
                    <label for="register-email-address" class="sr-only">Indirizzo email</label>
                    <input id="register-email-address" name="email" type="email" autocomplete="email" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Indirizzo email">
                </div>
                <div>
                    <label for="register-password" class="sr-only">Password</label>
                    <input id="register-password" name="password" type="password" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Password">
                </div>
                 <div>
                    <label for="confirm-password" class="sr-only">Conferma Password</label>
                    <input id="confirm-password" name="confirm_password" type="password" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Conferma Password">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-3 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Registrati
                </button>
            </div>
        </form>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const switchToRegisterBtn = document.getElementById('switch-to-register');
        const formTitle = document.getElementById('form-title');
        const formSubtitle = document.getElementById('form-subtitle');
        const messageContainer = document.getElementById('message-container');
        // NUOVO: Selettore per il contenitore del link
        const verificationContainer = document.getElementById('verification-link-container');

        function switchToRegister() {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            formTitle.textContent = 'Crea un nuovo account';
            formSubtitle.innerHTML = 'Sei già dei nostri? <button type="button" id="switch-to-login" class="font-medium text-indigo-600 hover:text-indigo-500">Accedi qui</button>';
            document.getElementById('switch-to-login').addEventListener('click', switchToLogin);
        }

        function switchToLogin() {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            formTitle.textContent = 'Accedi al tuo account';
            formSubtitle.innerHTML = 'Oppure <button type="button" id="switch-to-register" class="font-medium text-indigo-600 hover:text-indigo-500">crea un nuovo account</button>';
            document.getElementById('switch-to-register').addEventListener('click', switchToRegister);
        }

        switchToRegisterBtn.addEventListener('click', switchToRegister);

        registerForm.addEventListener('submit', function(event) {
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            if (password !== confirmPassword) {
                event.preventDefault();
                displayMessage('Le password non coincidono.', 'error');
            }
        });

        function displayMessage(message, type) {
            messageContainer.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
            if (type === 'success') {
                messageContainer.classList.add('bg-green-100', 'text-green-800');
            } else {
                messageContainer.classList.add('bg-red-100', 'text-red-800');
            }
            messageContainer.textContent = message;
        }

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            
            if (params.has('message')) {
                const message = decodeURIComponent(params.get('message'));
                const type = params.get('type') || 'error';
                displayMessage(message, type);
            }

            // AGGIORNATO: Controlla se c'è un token nell'URL
            if (params.has('token')) {
                const token = params.get('token');
                const verificationUrl = `verify.php?token=${token}`;
                
                // Crea il link/pulsante e lo mostra
                verificationContainer.innerHTML = `
                    <p class="text-sm text-gray-600 mb-2">L'invio dell'email potrebbe non funzionare sul server attuale.</p>
                    <a href="${verificationUrl}" class="inline-block w-full justify-center rounded-md border border-transparent bg-green-600 py-3 px-4 text-sm font-semibold text-white hover:bg-green-700">
                        Clicca qui per attivare il tuo account
                    </a>
                `;
                verificationContainer.classList.remove('hidden');
                
                // Nascondi i form per evitare confusione
                loginForm.classList.add('hidden');
                registerForm.classList.add('hidden');
            }

            if(params.has('action') && params.get('action') === 'register' && !params.has('token')) {
                switchToRegister();
            }
        };
    </script>
</body>
</html>