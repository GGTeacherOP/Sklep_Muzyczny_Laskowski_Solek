document.addEventListener('DOMContentLoaded', () => {
  const loginTabs = document.querySelectorAll('.login-tab');
  const forms = {
    login: document.getElementById('loginForm'),
    register: document.getElementById('registerForm'),
    employee: document.getElementById('employeeForm'),
  };

  /**
   * Czyści pola formularza z wartości i błędów.
   *
   * @param {HTMLFormElement} form - Formularz, który ma zostać wyczyszczony.
   */
  function clearForm(form) {
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
      input.value = '';
      input.classList.remove('error');
    });
    form.querySelectorAll('.form-error').forEach(error => error.remove());
  }

  /**
   * Przełącza aktywny formularz i zakładkę.
   *
   * @param {string} activeTabId - Identyfikator aktywnej zakładki i formularza (np. "login", "register", "employee").
   */
  function switchForm(activeTabId) {
    Object.values(forms).forEach(form => {
      if (form) {
        clearForm(form);
        form.style.display = 'none';
      }
    });

    loginTabs.forEach(tab => {
      tab.classList.remove('active');
    });

    const selectedForm = forms[activeTabId];
    const selectedTab = document.querySelector(`[data-tab="${activeTabId}"]`);

    if (selectedForm) {
      selectedForm.style.display = 'block';
      selectedForm.classList.add('fade-in');
    }
    if (selectedTab) {
      selectedTab.classList.add('active');
    }
  }

  loginTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const tabId = tab.getAttribute('data-tab');
      switchForm(tabId);
    });
  });
});
