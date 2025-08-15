<header class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div class="flex items-center gap-4">
        <button id="menu-button" type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
            <span class="sr-only">Apri menu principale</span>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div>
            <h1 class="text-3xl font-bold text-white"><?php echo $header_title; ?></h1>
            <p class="text-gray-400 mt-1"><?php echo $header_description; ?></p>
        </div>
    </div>
</header>