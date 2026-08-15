document.addEventListener("DOMContentLoaded", function () {

    const cards =
        document.querySelectorAll(".listing-card");

    const filterButton =
        document.getElementById("filterButton");

    const locationFilter =
        document.getElementById("locationFilter");

    const typeFilter =
        document.getElementById("typeFilter");

    const bedroomFilter =
        document.getElementById("bedroomFilter");

    const priceFilter =
        document.getElementById("priceFilter");

    const resultCount =
        document.getElementById("resultCount");


    /* ================= FAVORITES ================= */

    document
        .querySelectorAll(".listing-favorite")
        .forEach(button => {

            button.addEventListener("click", function () {

                this.classList.toggle("active");

                if (this.classList.contains("active")) {
                    this.innerHTML = "♥";
                } else {
                    this.innerHTML = "♡";
                }

            });

        });


    /* ================= FILTER ================= */

    function filterProperties() {

        const location =
            locationFilter.value
                .trim()
                .toLowerCase();

        const type =
            typeFilter.value;

        const bedrooms =
            bedroomFilter.value;

        const price =
            priceFilter.value;


        let visible = 0;


        cards.forEach(card => {

            const cardLocation =
                card.dataset.location;

            const cardType =
                card.dataset.type;

            const cardBedrooms =
                parseInt(card.dataset.bedrooms);

            const cardPrice =
                parseFloat(card.dataset.price);


            let show = true;


            /* Location */

            if (
                location !== "" &&
                !cardLocation.includes(location)
            ) {

                show = false;

            }


            /* Property type */

            if (
                type !== "all" &&
                cardType !== type
            ) {

                show = false;

            }


            /* Bedrooms */

            if (bedrooms !== "all") {

                const required =
                    parseInt(bedrooms);

                if (
                    required < 4 &&
                    cardBedrooms !== required
                ) {

                    show = false;

                }

                if (
                    required === 4 &&
                    cardBedrooms < 4
                ) {

                    show = false;

                }

            }


            /* Price */

            if (price === "25" && cardPrice >= 25) {
                show = false;
            }

            if (
                price === "50" &&
                (cardPrice < 25 || cardPrice > 50)
            ) {
                show = false;
            }

            if (
                price === "100" &&
                (cardPrice < 50 || cardPrice > 100)
            ) {
                show = false;
            }

            if (
                price === "above" &&
                cardPrice <= 100
            ) {
                show = false;
            }


            /* Display */

            if (show) {

                card.style.display = "block";
                visible++;

            } else {

                card.style.display = "none";

            }

        });


        resultCount.textContent =
            visible;

    }


    filterButton.addEventListener(
        "click",
        filterProperties
    );


    /* ================= SORT ================= */

    const sortProperty =
        document.getElementById(
            "sortProperty"
        );

    const grid =
        document.getElementById(
            "propertyGrid"
        );


    sortProperty.addEventListener(
        "change",
        function () {

            const cardsArray =
                Array.from(cards);

            if (this.value === "low") {

                cardsArray.sort(
                    (a, b) =>
                        parseFloat(a.dataset.price) -
                        parseFloat(b.dataset.price)
                );

            }


            if (this.value === "high") {

                cardsArray.sort(
                    (a, b) =>
                        parseFloat(b.dataset.price) -
                        parseFloat(a.dataset.price)
                );

            }


            cardsArray.forEach(card => {

                grid.appendChild(card);

            });

        }
    );

});