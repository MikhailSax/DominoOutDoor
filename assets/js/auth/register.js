import Inputmask from "inputmask";

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const nameInput = document.querySelector('#registration_form_first_name');
    const emailInput = document.querySelector('#registration_form_email');
    const phoneInput = document.querySelector('#registration_form_phone');
    const passwordInput = document.querySelector('#registration_form_plainPassword');
    const agreeCheckbox = document.querySelector('#registration_form_agreeTerms');

    if (phoneInput) {
        Inputmask("+7 (999) 999-99-99").mask(phoneInput);
    }

    if (!form || !nameInput || !emailInput || !phoneInput || !passwordInput || !agreeCheckbox) {
        console.warn('Не все элементы формы найдены');
        return;
    }

    const showError = (input, message) => {
        const errorElem = document.createElement('div');
        errorElem.className = 'text-sm text-red-600 mt-1';
        errorElem.textContent = message;
        input.classList.add('border-red-500');
        input.parentNode.appendChild(errorElem);
    };

    const clearErrors = () => {
        form.querySelectorAll('.text-red-600').forEach(e => e.remove());
        form.querySelectorAll('input').forEach(input => input.classList.remove('border-red-500'));
    };

    form.addEventListener('submit', (e) => {
        clearErrors();
        let hasError = false;

        if (nameInput.value.trim().length < 2) {
            showError(nameInput, 'Имя должно быть не короче 2 символов');
            hasError = true;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
            showError(emailInput, 'Введите корректный email');
            hasError = true;
        }

        const phoneDigits = phoneInput.value.replace(/\D/g, '');
        if (phoneDigits.length < 10) {
            showError(phoneInput, 'Введите корректный номер телефона');
            hasError = true;
        }

        const pwd = passwordInput.value;
        if (pwd.length < 6 || !/\d/.test(pwd) || !/[a-zA-Z]/.test(pwd)) {
            showError(passwordInput, 'Пароль должен быть от 6 символов и содержать буквы и цифры');
            hasError = true;
        }

        if (!agreeCheckbox.checked) {
            showError(agreeCheckbox, 'Необходимо принять условия');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });
});
