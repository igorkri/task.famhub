<div class="flex gap-2">
    <button 
        wire:click="syncCommentsFromAsana" 
        class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-transparent rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3 3-3M12 12v9"></path>
        </svg>
        Отримати коментарі з Asana
    </button>
    
    <button 
        wire:click="syncCommentsToAsana" 
        class="inline-flex items-center px-3 py-2 text-sm font-medium text-orange-700 bg-orange-100 border border-transparent rounded-md hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
    >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l3-3-3-3M12 12v9"></path>
        </svg>
        Відправити нові коментарі в Asana
    </button>
</div>