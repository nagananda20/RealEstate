document.addEventListener("DOMContentLoaded", () => {

    /* ================= FAVORITES ================= */

    const favoriteButtons =
        document.querySelectorAll(".favorite");

    favoriteButtons.forEach(button => {

        button.addEventListener("click", () => {

            button.classList.toggle("active");

            if (button.classList.contains("active")) {
                button.innerHTML = "♥";
            } else {
                button.innerHTML = "♡";
            }

        });

    });


    /* ================= SEARCH TABS ================= */

    const tabs =
        document.querySelectorAll(".tab");

    tabs.forEach(tab => {

        tab.addEventListener("click", () => {

            tabs.forEach(item => {
                item.classList.remove("active");
            });

            tab.classList.add("active");

        });

    });


    /* ================= SEARCH ================= */

    const searchButton =
        document.querySelector(".search-btn");

    if (searchButton) {

        searchButton.addEventListener("click", () => {

            const location =
                document.querySelector(
                    ".search-field input"
                ).value;

            if (location.trim() === "") {

                alert(
                    "Please enter a location to search."
                );

                return;

            }

            window.location.href =
                "pages/properties.php?location=" +
                encodeURIComponent(location);

        });

    }


    /* ================= SCROLL ANIMATION ================= */

    const cards =
        document.querySelectorAll(
            ".property-card, .category-card"
        );

    const observer =
        new IntersectionObserver(
            entries => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        entry.target.style.opacity = "1";
                        entry.target.style.transform =
                            "translateY(0)";

                    }

                });

            },
            {
                threshold: 0.15
            }
        );


    cards.forEach(card => {

        card.style.opacity = "0";
        card.style.transform = "translateY(30px)";
        card.style.transition = "all .6s ease";

        observer.observe(card);

    });

});