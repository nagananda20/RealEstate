document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       REAL ESTATE ADMIN DASHBOARD
    ===================================================== */


    /* =====================================================
       SIDEBAR TOGGLE
    ===================================================== */

    const menuButton =
        document.querySelector("#menuToggle");

    const sidebar =
        document.querySelector(".sidebar");

    const overlay =
        document.querySelector(".sidebar-overlay");


    if (menuButton && sidebar) {

        menuButton.addEventListener(
            "click",
            function () {

                sidebar.classList.toggle(
                    "open"
                );

                if (overlay) {

                    overlay.classList.toggle(
                        "active"
                    );

                }

            }
        );

    }


    if (overlay) {

        overlay.addEventListener(
            "click",
            function () {

                sidebar.classList.remove(
                    "open"
                );

                overlay.classList.remove(
                    "active"
                );

            }
        );

    }


    /* =====================================================
       CLOSE SIDEBAR ON MOBILE
    ===================================================== */

    document
        .querySelectorAll(
            ".sidebar a"
        )
        .forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function () {

                        if (
                            window.innerWidth <= 900
                        ) {

                            sidebar?.classList.remove(
                                "open"
                            );

                            overlay?.classList.remove(
                                "active"
                            );

                        }

                    }
                );

            }
        );


    /* =====================================================
       DASHBOARD DATE
    ===================================================== */

    const dateElement =
        document.querySelector(
            "#dashboardDate"
        );


    if (dateElement) {

        const today =
            new Date();


        const options = {

            weekday: "long",

            year: "numeric",

            month: "long",

            day: "numeric"

        };


        dateElement.textContent =
            today.toLocaleDateString(
                "en-IN",
                options
            );

    }


    /* =====================================================
       DIGITAL CLOCK
    ===================================================== */

    const clock =
        document.querySelector(
            "#dashboardClock"
        );


    function updateClock() {

        if (!clock) {
            return;
        }


        const now =
            new Date();


        clock.textContent =
            now.toLocaleTimeString(
                "en-IN",
                {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit"
                }
            );

    }


    updateClock();

    setInterval(
        updateClock,
        1000
    );


    /* =====================================================
       STAT COUNTER ANIMATION
    ===================================================== */

    const counters =
        document.querySelectorAll(
            ".stat-number[data-value]"
        );


    counters.forEach(
        function (counter) {

            const target =
                parseInt(
                    counter.dataset.value,
                    10
                );


            if (
                isNaN(target)
            ) {
                return;
            }


            let current = 0;


            const duration = 1200;


            const steps = 40;


            const increment =
                target / steps;


            const intervalTime =
                duration / steps;


            const timer =
                setInterval(
                    function () {

                        current +=
                            increment;


                        if (
                            current >= target
                        ) {

                            current =
                                target;

                            clearInterval(
                                timer
                            );

                        }


                        counter.textContent =
                            Math.floor(
                                current
                            ).toLocaleString(
                                "en-IN"
                            );

                    },
                    intervalTime
                );

        }
    );


    /* =====================================================
       PERCENTAGE COUNTER
    ===================================================== */

    const percentageCounters =
        document.querySelectorAll(
            "[data-percentage]"
        );


    percentageCounters.forEach(
        function (element) {

            const target =
                parseFloat(
                    element.dataset.percentage
                );


            if (
                isNaN(target)
            ) {
                return;
            }


            let current = 0;


            const timer =
                setInterval(
                    function () {

                        current +=
                            target / 30;


                        if (
                            current >= target
                        ) {

                            current =
                                target;

                            clearInterval(
                                timer
                            );

                        }


                        element.textContent =
                            Math.round(
                                current
                            ) + "%";

                    },
                    35
                );

        }
    );


    /* =====================================================
       PROGRESS BARS
    ===================================================== */

    const progressBars =
        document.querySelectorAll(
            ".progress-bar[data-progress]"
        );


    progressBars.forEach(
        function (bar) {

            const value =
                parseFloat(
                    bar.dataset.progress
                );


            if (
                isNaN(value)
            ) {
                return;
            }


            bar.style.width = "0%";


            setTimeout(
                function () {

                    bar.style.width =
                        Math.min(
                            value,
                            100
                        ) + "%";

                },
                200
            );

        }
    );


    /* =====================================================
       QUICK SEARCH
    ===================================================== */

    const searchInput =
        document.querySelector(
            "#dashboardSearch"
        );


    const searchableItems =
        document.querySelectorAll(
            "[data-searchable]"
        );


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                const query =
                    this.value
                        .toLowerCase()
                        .trim();


                searchableItems.forEach(
                    function (item) {

                        const text =
                            item.dataset.searchable
                                ?.toLowerCase()
                            ||
                            item.textContent
                                .toLowerCase();


                        if (
                            !query ||
                            text.includes(query)
                        ) {

                            item.style.display =
                                "";

                        }
                        else {

                            item.style.display =
                                "none";

                        }

                    }
                );

            }
        );

    }


    /* =====================================================
       NOTIFICATION DROPDOWN
    ===================================================== */

    const notificationButton =
        document.querySelector(
            "#notificationButton"
        );


    const notificationPanel =
        document.querySelector(
            "#notificationPanel"
        );


    if (
        notificationButton &&
        notificationPanel
    ) {

        notificationButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                notificationPanel.classList.toggle(
                    "show"
                );

            }
        );


        document.addEventListener(
            "click",
            function (event) {

                if (
                    !notificationPanel.contains(
                        event.target
                    ) &&
                    !notificationButton.contains(
                        event.target
                    )
                ) {

                    notificationPanel.classList.remove(
                        "show"
                    );

                }

            }
        );

    }


    /* =====================================================
       MARK NOTIFICATIONS READ
    ===================================================== */

    const markReadButton =
        document.querySelector(
            "#markNotificationsRead"
        );


    if (markReadButton) {

        markReadButton.addEventListener(
            "click",
            function () {

                const badge =
                    document.querySelector(
                        ".notification-count"
                    );


                if (badge) {

                    badge.textContent =
                        "0";

                    badge.style.display =
                        "none";

                }


                document
                    .querySelectorAll(
                        ".notification-item.unread"
                    )
                    .forEach(
                        function (item) {

                            item.classList.remove(
                                "unread"
                            );

                        }
                    );

            }
        );

    }


    /* =====================================================
       RECENT ACTIVITIES FILTER
    ===================================================== */

    const activityFilter =
        document.querySelector(
            "#activityFilter"
        );


    const activities =
        document.querySelectorAll(
            ".activity-item"
        );


    if (activityFilter) {

        activityFilter.addEventListener(
            "change",
            function () {

                const selected =
                    this.value;


                activities.forEach(
                    function (activity) {

                        const type =
                            activity.dataset.type;


                        if (
                            selected === "all" ||
                            selected === type
                        ) {

                            activity.style.display =
                                "";

                        }
                        else {

                            activity.style.display =
                                "none";

                        }

                    }
                );

            }
        );

    }


    /* =====================================================
       PROPERTY STATUS FILTER
    ===================================================== */

    const propertyFilter =
        document.querySelector(
            "#propertyStatusFilter"
        );


    const propertyRows =
        document.querySelectorAll(
            ".property-row"
        );


    if (propertyFilter) {

        propertyFilter.addEventListener(
            "change",
            function () {

                const selected =
                    this.value;


                propertyRows.forEach(
                    function (row) {

                        const status =
                            row.dataset.status;


                        if (
                            selected === "all" ||
                            selected === status
                        ) {

                            row.style.display =
                                "";

                        }
                        else {

                            row.style.display =
                                "none";

                        }

                    }
                );

            }
        );

    }


    /* =====================================================
       DELETE CONFIRMATION
    ===================================================== */

    document
        .querySelectorAll(
            "[data-confirm-delete]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        const message =
                            this.dataset.confirmDelete
                            ||
                            "Are you sure you want to delete this item?";


                        if (
                            !confirm(message)
                        ) {

                            event.preventDefault();

                        }

                    }
                );

            }
        );


    /* =====================================================
       LOGOUT CONFIRMATION
    ===================================================== */

    const logoutLinks =
        document.querySelectorAll(
            'a[href*="logout"]'
        );


    logoutLinks.forEach(
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
       AUTO REFRESH DASHBOARD
    ===================================================== */

    const refreshButton =
        document.querySelector(
            "#refreshDashboard"
        );


    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            function () {

                const originalText =
                    this.innerHTML;


                this.disabled =
                    true;


                this.innerHTML =
                    "⟳ Refreshing...";


                setTimeout(
                    function () {

                        window.location.reload();

                    },
                    500
                );

            }
        );

    }


    /* =====================================================
       LAST UPDATED TIME
    ===================================================== */

    const updatedElement =
        document.querySelector(
            "#lastUpdated"
        );


    if (updatedElement) {

        updatedElement.textContent =
            new Date().toLocaleTimeString(
                "en-IN",
                {
                    hour:"2-digit",
                    minute:"2-digit"
                }
            );

    }


    /* =====================================================
       CARD HOVER EFFECT
    ===================================================== */

    const cards =
        document.querySelectorAll(
            ".dashboard-card, " +
            ".stat-card, " +
            ".overview-card"
        );


    cards.forEach(
        function (card) {

            card.addEventListener(
                "mouseenter",
                function () {

                    this.classList.add(
                        "hovered"
                    );

                }
            );


            card.addEventListener(
                "mouseleave",
                function () {

                    this.classList.remove(
                        "hovered"
                    );

                }
            );

        }
    );


    /* =====================================================
       SCROLL REVEAL
    ===================================================== */

    const revealElements =
        document.querySelectorAll(
            ".dashboard-section, " +
            ".stat-card, " +
            ".dashboard-card"
        );


    if (
        "IntersectionObserver"
        in window
    ) {

        const observer =
            new IntersectionObserver(
                function (
                    entries,
                    observer
                ) {

                    entries.forEach(
                        function (entry) {

                            if (
                                entry.isIntersecting
                            ) {

                                entry.target.classList.add(
                                    "visible"
                                );


                                observer.unobserve(
                                    entry.target
                                );

                            }

                        }
                    );

                },
                {
                    threshold:0.1
                }
            );


        revealElements.forEach(
            function (element) {

                element.classList.add(
                    "dashboard-reveal"
                );


                observer.observe(
                    element
                );

            }
        );


        const style =
            document.createElement(
                "style"
            );


        style.textContent = `

            .dashboard-reveal {

                opacity:0;

                transform:
                    translateY(20px);

                transition:
                    opacity .5s ease,
                    transform .5s ease;

            }


            .dashboard-reveal.visible {

                opacity:1;

                transform:
                    translateY(0);

            }


            .stat-card,
            .dashboard-card,
            .overview-card {

                transition:
                    transform .2s ease,
                    box-shadow .2s ease;

            }


            .stat-card.hovered,
            .dashboard-card.hovered,
            .overview-card.hovered {

                transform:
                    translateY(-3px);

            }

        `;


        document.head.appendChild(
            style
        );

    }


    /* =====================================================
       TABLE ROW CLICK
    ===================================================== */

    document
        .querySelectorAll(
            "[data-href]"
        )
        .forEach(
            function (row) {

                row.addEventListener(
                    "click",
                    function (event) {

                        if (
                            event.target.closest(
                                "button"
                            ) ||
                            event.target.closest(
                                "a"
                            )
                        ) {

                            return;

                        }


                        const url =
                            this.dataset.href;


                        if (url) {

                            window.location.href =
                                url;

                        }

                    }
                );

            }
        );


    /* =====================================================
       RESPONSIVE WINDOW HANDLER
    ===================================================== */

    window.addEventListener(
        "resize",
        function () {

            if (
                window.innerWidth > 900
            ) {

                sidebar?.classList.remove(
                    "open"
                );

                overlay?.classList.remove(
                    "active"
                );

            }

        }
    );


    /* =====================================================
       LOCAL STORAGE DASHBOARD VISIT
    ===================================================== */

    const visitKey =
        "realestate_dashboard_visits";


    let visits =
        parseInt(
            localStorage.getItem(
                visitKey
            ) || "0",
            10
        );


    visits++;


    localStorage.setItem(
        visitKey,
        visits.toString()
    );


    const visitCounter =
        document.querySelector(
            "#dashboardVisits"
        );


    if (visitCounter) {

        visitCounter.textContent =
            visits.toLocaleString(
                "en-IN"
            );

    }


    /* =====================================================
       WELCOME MESSAGE
    ===================================================== */

    const welcomeMessage =
        document.querySelector(
            "#welcomeMessage"
        );


    if (welcomeMessage) {

        const hour =
            new Date().getHours();


        let greeting =
            "Welcome";


        if (hour < 12) {

            greeting =
                "Good Morning";

        }
        else if (hour < 17) {

            greeting =
                "Good Afternoon";

        }
        else {

            greeting =
                "Good Evening";

        }


        welcomeMessage.textContent =
            greeting;

    }


    /* =====================================================
       CONSOLE
    ===================================================== */

    console.log(
        "RealEstate Dashboard JS loaded successfully."
    );

});