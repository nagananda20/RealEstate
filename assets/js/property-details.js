document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
       IMAGE GALLERY
    ========================================== */

    const mainImage =
        document.getElementById(
            "mainPropertyImage"
        );

    const thumbnails =
        document.querySelectorAll(
            ".thumbnail"
        );


    thumbnails.forEach(thumbnail => {

        thumbnail.addEventListener(
            "click",
            function () {

                const image =
                    this.dataset.image;

                mainImage.src = image;

            }
        );

    });


    /* ==========================================
       FAVORITE
    ========================================== */

    const favoriteButton =
        document.getElementById(
            "favoriteButton"
        );


    favoriteButton.addEventListener(
        "click",
        function () {

            this.classList.toggle(
                "favorite-active"
            );


            if (
                this.classList.contains(
                    "favorite-active"
                )
            ) {

                this.innerHTML =
                    "♥ Saved";

            } else {

                this.innerHTML =
                    "♡ Save";

            }

        }
    );


    /* ==========================================
       SHARE
    ========================================== */

    const shareButton =
        document.getElementById(
            "shareButton"
        );


    shareButton.addEventListener(
        "click",
        async function () {

            const shareData = {

                title: document.title,

                text:
                    "Check out this property on RealEstateHub",

                url:
                    window.location.href

            };


            if (
                navigator.share
            ) {

                try {

                    await navigator.share(
                        shareData
                    );

                } catch (error) {

                    console.log(
                        "Share cancelled"
                    );

                }

            } else {

                await navigator.clipboard.writeText(
                    window.location.href
                );

                alert(
                    "Property link copied!"
                );

            }

        }
    );


    /* ==========================================
       PRINT
    ========================================== */

    const printButton =
        document.getElementById(
            "printButton"
        );


    printButton.addEventListener(
        "click",
        function () {

            window.print();

        }
    );


    /* ==========================================
       EMI CALCULATOR
    ========================================== */

    const calculateButton =
        document.getElementById(
            "calculateEMI"
        );


    calculateButton.addEventListener(
        "click",
        calculateEMI
    );


    function calculateEMI() {

        const price =
            parseFloat(
                document.getElementById(
                    "propertyPrice"
                ).value
            );


        const downPayment =
            parseFloat(
                document.getElementById(
                    "downPayment"
                ).value
            );


        const interest =
            parseFloat(
                document.getElementById(
                    "interestRate"
                ).value
            );


        const years =
            parseFloat(
                document.getElementById(
                    "loanYears"
                ).value
            );


        if (
            isNaN(price) ||
            isNaN(downPayment) ||
            isNaN(interest) ||
            isNaN(years)
        ) {

            alert(
                "Please enter valid values."
            );

            return;

        }


        const principal =
            price - downPayment;


        const monthlyRate =
            interest / 12 / 100;


        const months =
            years * 12;


        let emi;


        if (monthlyRate === 0) {

            emi =
                principal / months;

        } else {

            emi =
                principal *
                monthlyRate *
                Math.pow(
                    1 + monthlyRate,
                    months
                ) /
                (
                    Math.pow(
                        1 + monthlyRate,
                        months
                    ) - 1
                );

        }


        document.getElementById(
            "emiResult"
        ).textContent =
            "₹" +
            Math.round(emi)
                .toLocaleString("en-IN");

    }


    /* Calculate initially */

    calculateEMI();


    /* ==========================================
       CONTACT AGENT
    ========================================== */

    const contactAgent =
        document.getElementById(
            "contactAgent"
        );


    contactAgent.addEventListener(
        "click",
        function () {

            const name =
                document.getElementById(
                    "visitorName"
                ).value.trim();


            const phone =
                document.getElementById(
                    "visitorPhone"
                ).value.trim();


            if (
                name === "" ||
                phone === ""
            ) {

                alert(
                    "Please enter your name and phone number."
                );

                return;

            }


            alert(
                "Your message has been sent to the agent."
            );

        }
    );


    /* ==========================================
       PROPERTY VISIT
    ========================================== */

    const scheduleVisit =
        document.getElementById(
            "scheduleVisit"
        );


    scheduleVisit.addEventListener(
        "click",
        function () {

            const date =
                document.getElementById(
                    "visitDate"
                ).value;


            const time =
                document.getElementById(
                    "visitTime"
                ).value;


            if (date === "") {

                alert(
                    "Please select a visit date."
                );

                return;

            }


            alert(
                "Visit requested for " +
                date +
                " at " +
                time
            );

        }
    );

});