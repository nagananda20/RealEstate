document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       REAL ESTATE AUTHENTICATION JAVASCRIPT
    ===================================================== */


    /* =====================================================
       PASSWORD SHOW / HIDE
    ===================================================== */

    const passwordToggles =
        document.querySelectorAll(
            "[data-password-toggle]"
        );


    passwordToggles.forEach(
        function (toggle) {

            toggle.addEventListener(
                "click",
                function () {

                    const targetId =
                        this.dataset.passwordToggle;


                    const passwordInput =
                        document.getElementById(
                            targetId
                        );


                    if (!passwordInput) {
                        return;
                    }


                    if (
                        passwordInput.type ===
                        "password"
                    ) {

                        passwordInput.type =
                            "text";

                        this.textContent =
                            "🙈";

                        this.setAttribute(
                            "aria-label",
                            "Hide password"
                        );

                    }
                    else {

                        passwordInput.type =
                            "password";

                        this.textContent =
                            "👁";

                        this.setAttribute(
                            "aria-label",
                            "Show password"
                        );

                    }

                }
            );

        }
    );


    /* =====================================================
       PASSWORD STRENGTH
    ===================================================== */

    const passwordInput =
        document.querySelector(
            "#password"
        );


    const strengthBar =
        document.querySelector(
            "#passwordStrength"
        );


    const strengthText =
        document.querySelector(
            "#passwordStrengthText"
        );


    if (passwordInput) {

        passwordInput.addEventListener(
            "input",
            function () {

                const password =
                    this.value;


                const result =
                    calculatePasswordStrength(
                        password
                    );


                if (strengthBar) {

                    strengthBar.style.width =
                        result.percent + "%";


                    strengthBar.className =
                        "password-strength-bar " +
                        result.className;

                }


                if (strengthText) {

                    strengthText.textContent =
                        result.text;

                }

            }
        );

    }


    function calculatePasswordStrength(
        password
    ) {

        if (!password) {

            return {

                percent: 0,

                text: "",

                className: ""

            };

        }


        let score = 0;


        if (
            password.length >= 8
        ) {

            score++;

        }


        if (
            password.length >= 12
        ) {

            score++;

        }


        if (
            /[A-Z]/.test(password)
        ) {

            score++;

        }


        if (
            /[a-z]/.test(password)
        ) {

            score++;

        }


        if (
            /[0-9]/.test(password)
        ) {

            score++;

        }


        if (
            /[^A-Za-z0-9]/.test(password)
        ) {

            score++;

        }


        if (score <= 2) {

            return {

                percent: 30,

                text: "Weak password",

                className: "weak"

            };

        }


        if (score <= 4) {

            return {

                percent: 65,

                text: "Medium password",

                className: "medium"

            };

        }


        return {

            percent: 100,

            text: "Strong password",

            className: "strong"

        };

    }


    /* =====================================================
       REGISTER FORM
    ===================================================== */

    const registerForm =
        document.querySelector(
            "#registerForm"
        );


    if (registerForm) {

        registerForm.addEventListener(
            "submit",
            function (event) {

                const name =
                    registerForm.querySelector(
                        '[name="name"]'
                    );


                const email =
                    registerForm.querySelector(
                        '[name="email"]'
                    );


                const phone =
                    registerForm.querySelector(
                        '[name="phone"]'
                    );


                const password =
                    registerForm.querySelector(
                        '[name="password"]'
                    );


                const confirmPassword =
                    registerForm.querySelector(
                        '[name="confirm_password"]'
                    );


                const terms =
                    registerForm.querySelector(
                        '[name="terms"]'
                    );


                clearErrors(
                    registerForm
                );


                let valid = true;


                if (
                    name &&
                    name.value.trim().length < 2
                ) {

                    showFieldError(
                        name,
                        "Please enter your full name."
                    );

                    valid = false;

                }


                if (
                    email &&
                    !validateEmail(
                        email.value.trim()
                    )
                ) {

                    showFieldError(
                        email,
                        "Please enter a valid email address."
                    );

                    valid = false;

                }


                if (
                    phone &&
                    phone.value.trim() &&
                    !validatePhone(
                        phone.value.trim()
                    )
                ) {

                    showFieldError(
                        phone,
                        "Please enter a valid phone number."
                    );

                    valid = false;

                }


                if (
                    password &&
                    password.value.length < 8
                ) {

                    showFieldError(
                        password,
                        "Password must contain at least 8 characters."
                    );

                    valid = false;

                }


                if (
                    password &&
                    confirmPassword &&
                    password.value !==
                    confirmPassword.value
                ) {

                    showFieldError(
                        confirmPassword,
                        "Passwords do not match."
                    );

                    valid = false;

                }


                if (
                    terms &&
                    !terms.checked
                ) {

                    showFormMessage(
                        registerForm,
                        "Please accept the terms and conditions.",
                        "error"
                    );

                    valid = false;

                }


                if (!valid) {

                    event.preventDefault();

                }

            }
        );

    }


    /* =====================================================
       LOGIN FORM
    ===================================================== */

    const loginForm =
        document.querySelector(
            "#loginForm"
        );


    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            function (event) {

                const email =
                    loginForm.querySelector(
                        '[name="email"]'
                    );


                const password =
                    loginForm.querySelector(
                        '[name="password"]'
                    );


                clearErrors(
                    loginForm
                );


                let valid = true;


                if (
                    email &&
                    !validateEmail(
                        email.value.trim()
                    )
                ) {

                    showFieldError(
                        email,
                        "Please enter a valid email address."
                    );

                    valid = false;

                }


                if (
                    password &&
                    password.value.trim() === ""
                ) {

                    showFieldError(
                        password,
                        "Please enter your password."
                    );

                    valid = false;

                }


                if (!valid) {

                    event.preventDefault();

                    return;

                }


                const submitButton =
                    loginForm.querySelector(
                        'button[type="submit"]'
                    );


                if (submitButton) {

                    submitButton.disabled =
                        true;

                    submitButton.dataset.originalText =
                        submitButton.innerHTML;

                    submitButton.innerHTML =
                        "Signing in...";

                }

            }
        );

    }


    /* =====================================================
       FORGOT PASSWORD FORM
    ===================================================== */

    const forgotForm =
        document.querySelector(
            "#forgotPasswordForm"
        );


    if (forgotForm) {

        forgotForm.addEventListener(
            "submit",
            function (event) {

                const email =
                    forgotForm.querySelector(
                        '[name="email"]'
                    );


                clearErrors(
                    forgotForm
                );


                if (
                    email &&
                    !validateEmail(
                        email.value.trim()
                    )
                ) {

                    event.preventDefault();


                    showFieldError(
                        email,
                        "Please enter a valid email address."
                    );

                }

            }
        );

    }


    /* =====================================================
       RESET PASSWORD FORM
    ===================================================== */

    const resetForm =
        document.querySelector(
            "#resetPasswordForm"
        );


    if (resetForm) {

        resetForm.addEventListener(
            "submit",
            function (event) {

                const password =
                    resetForm.querySelector(
                        '[name="password"]'
                    );


                const confirmPassword =
                    resetForm.querySelector(
                        '[name="confirm_password"]'
                    );


                clearErrors(
                    resetForm
                );


                let valid = true;


                if (
                    password &&
                    password.value.length < 8
                ) {

                    showFieldError(
                        password,
                        "Password must contain at least 8 characters."
                    );

                    valid = false;

                }


                if (
                    password &&
                    confirmPassword &&
                    password.value !==
                    confirmPassword.value
                ) {

                    showFieldError(
                        confirmPassword,
                        "Passwords do not match."
                    );

                    valid = false;

                }


                if (!valid) {

                    event.preventDefault();

                }

            }
        );

    }


    /* =====================================================
       EMAIL VALIDATION
    ===================================================== */

    function validateEmail(
        email
    ) {

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            .test(email);

    }


    /* =====================================================
       PHONE VALIDATION
    ===================================================== */

    function validatePhone(
        phone
    ) {

        const cleaned =
            phone.replace(
                /[\s\-()+]/g,
                ""
            );


        return /^[0-9]{10,15}$/
            .test(cleaned);

    }


    /* =====================================================
       FORM FIELD ERROR
    ===================================================== */

    function showFieldError(
        input,
        message
    ) {

        input.classList.add(
            "input-error"
        );


        let error =
            input.parentElement.querySelector(
                ".field-error"
            );


        if (!error) {

            error =
                document.createElement(
                    "small"
                );

            error.className =
                "field-error";

            input.parentElement.appendChild(
                error
            );

        }


        error.textContent =
            message;


        input.addEventListener(
            "input",
            function removeError() {

                input.classList.remove(
                    "input-error"
                );

                error.remove();


                input.removeEventListener(
                    "input",
                    removeError
                );

            }
        );

    }


    /* =====================================================
       CLEAR FORM ERRORS
    ===================================================== */

    function clearErrors(
        form
    ) {

        form
            .querySelectorAll(
                ".field-error"
            )
            .forEach(
                function (error) {

                    error.remove();

                }
            );


        form
            .querySelectorAll(
                ".input-error"
            )
            .forEach(
                function (input) {

                    input.classList.remove(
                        "input-error"
                    );

                }
            );

    }


    /* =====================================================
       FORM MESSAGE
    ===================================================== */

    function showFormMessage(
        form,
        message,
        type
    ) {

        let box =
            form.querySelector(
                ".auth-form-message"
            );


        if (!box) {

            box =
                document.createElement(
                    "div"
                );

            box.className =
                "auth-form-message";

            form.prepend(
                box
            );

        }


        box.textContent =
            message;


        box.className =
            "auth-form-message " +
            type;

    }


    /* =====================================================
       REMEMBER EMAIL
    ===================================================== */

    const rememberCheckbox =
        document.querySelector(
            "#remember"
        );


    if (rememberCheckbox) {

        const savedEmail =
            localStorage.getItem(
                "realestate_remember_email"
            );


        const emailInput =
            document.querySelector(
                'input[name="email"]'
            );


        if (
            savedEmail &&
            emailInput
        ) {

            emailInput.value =
                savedEmail;

            rememberCheckbox.checked =
                true;

        }


        if (loginForm) {

            loginForm.addEventListener(
                "submit",
                function () {

                    if (
                        rememberCheckbox.checked &&
                        emailInput
                    ) {

                        localStorage.setItem(
                            "realestate_remember_email",
                            emailInput.value.trim()
                        );

                    }
                    else {

                        localStorage.removeItem(
                            "realestate_remember_email"
                        );

                    }

                }
            );

        }

    }


    /* =====================================================
       PASSWORD CONFIRMATION LIVE CHECK
    ===================================================== */

    const confirmPasswordInput =
        document.querySelector(
            'input[name="confirm_password"]'
        );


    if (
        passwordInput &&
        confirmPasswordInput
    ) {

        function checkPasswords() {

            if (
                !confirmPasswordInput.value
            ) {
                return;
            }


            if (
                passwordInput.value ===
                confirmPasswordInput.value
            ) {

                confirmPasswordInput.classList.remove(
                    "input-error"
                );

                confirmPasswordInput.classList.add(
                    "input-success"
                );

            }
            else {

                confirmPasswordInput.classList.remove(
                    "input-success"
                );

                confirmPasswordInput.classList.add(
                    "input-error"
                );

            }

        }


        passwordInput.addEventListener(
            "input",
            checkPasswords
        );


        confirmPasswordInput.addEventListener(
            "input",
            checkPasswords
        );

    }


    /* =====================================================
       OTP INPUT
    ===================================================== */

    const otpInputs =
        document.querySelectorAll(
            ".otp-input"
        );


    otpInputs.forEach(
        function (input, index) {

            input.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value.replace(
                            /\D/g,
                            ""
                        );


                    if (
                        this.value &&
                        index <
                        otpInputs.length - 1
                    ) {

                        otpInputs[
                            index + 1
                        ].focus();

                    }

                }
            );


            input.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key ===
                        "Backspace" &&
                        !this.value &&
                        index > 0
                    ) {

                        otpInputs[
                            index - 1
                        ].focus();

                    }

                }
            );

        }
    );


    /* =====================================================
       AUTH LOADING BUTTON
    ===================================================== */

    document
        .querySelectorAll(
            ".auth-submit"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const form =
                            this.closest(
                                "form"
                            );


                        if (
                            form &&
                            form.checkValidity()
                        ) {

                            this.classList.add(
                                "loading"
                            );

                        }

                    }
                );

            }
        );


    /* =====================================================
       SOCIAL LOGIN BUTTONS
    ===================================================== */

    document
        .querySelectorAll(
            "[data-social-login]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const provider =
                            this.dataset.socialLogin;


                        console.log(
                            "Social login selected:",
                            provider
                        );


                        /*
                         * Connect your OAuth provider
                         * here when backend integration
                         * is added.
                         */

                    }
                );

            }
        );


    /* =====================================================
       LOGOUT CONFIRMATION
    ===================================================== */

    document
        .querySelectorAll(
            'a[href*="logout.php"]'
        )
        .forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function (event) {

                        const confirmed =
                            confirm(
                                "Are you sure you want to logout?"
                            );


                        if (!confirmed) {

                            event.preventDefault();

                        }

                    }
                );

            }
        );


    /* =====================================================
       AUTO HIDE ALERTS
    ===================================================== */

    document
        .querySelectorAll(
            ".auth-alert, .alert"
        )
        .forEach(
            function (alert) {

                setTimeout(
                    function () {

                        alert.style.opacity =
                            "0";


                        alert.style.transform =
                            "translateY(-5px)";


                        setTimeout(
                            function () {

                                alert.remove();

                            },
                            300
                        );

                    },
                    5000
                );

            }
        );


    /* =====================================================
       AUTH PAGE ANIMATION
    ===================================================== */

    const authCard =
        document.querySelector(
            ".auth-card"
        );


    if (authCard) {

        authCard.classList.add(
            "auth-loaded"
        );


        const style =
            document.createElement(
                "style"
            );


        style.textContent = `

            .auth-card {

                opacity:0;

                transform:
                    translateY(20px);

                transition:
                    opacity .5s ease,
                    transform .5s ease;

            }


            .auth-card.auth-loaded {

                opacity:1;

                transform:
                    translateY(0);

            }


            .input-error {

                border-color:#c63d4a !important;

                background:#fff8f8 !important;

            }


            .input-success {

                border-color:#278254 !important;

                background:#f7fff9 !important;

            }


            .field-error {

                display:block;

                margin-top:5px;

                color:#c63d4a;

                font-size:11px;

            }


            .auth-form-message {

                padding:10px 12px;

                margin-bottom:15px;

                border-radius:6px;

                font-size:12px;

            }


            .auth-form-message.error {

                color:#a52d38;

                background:#fdecee;

            }


            .auth-form-message.success {

                color:#216c45;

                background:#eaf7ef;

            }


            .password-strength-bar {

                width:0;

                height:4px;

                border-radius:5px;

                transition:.3s;

            }


            .password-strength-bar.weak {

                background:#d44753;

            }


            .password-strength-bar.medium {

                background:#d99a2b;

            }


            .password-strength-bar.strong {

                background:#278254;

            }


            .auth-submit.loading {

                opacity:.7;

                pointer-events:none;

            }

        `;


        document.head.appendChild(
            style
        );

    }


    /* =====================================================
       PREVENT DOUBLE SUBMISSION
    ===================================================== */

    document
        .querySelectorAll(
            "form"
        )
        .forEach(
            function (form) {

                form.addEventListener(
                    "submit",
                    function () {

                        const button =
                            form.querySelector(
                                'button[type="submit"]'
                            );


                        if (
                            button &&
                            !button.classList.contains(
                                "allow-double-submit"
                            )
                        ) {

                            setTimeout(
                                function () {

                                    button.disabled =
                                        true;

                                },
                                10
                            );

                        }

                    }
                );

            }
        );


    console.log(
        "RealEstate Auth JS loaded successfully."
    );

});