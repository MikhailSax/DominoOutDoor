document.addEventListener('DOMContentLoaded', () => {
    // Выпадающие меню по клику
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            // Закрыть все открытые
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });

        menu.addEventListener('click', (e) => e.stopPropagation());
    });

    // Закрытие при клике вне меню
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });

    // Мобильное меню (бургер)
    const mobileBtn = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileBtn?.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
});
