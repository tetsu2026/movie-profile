<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-all duration-150 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 cursor-pointer']) }}
        style="background-color: #DC2626;">
    {{ $slot }}
</button>
