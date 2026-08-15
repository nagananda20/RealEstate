document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       REAL ESTATE PROPERTY VISITS
    ===================================================== */


    /* =====================================================
       SEARCH VISITS
    ===================================================== */

    const searchInput =
        document.querySelector("#visitSearch");

    const visitRows =
        document.querySelectorAll(".visit-row");


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                filterVisits();

            }
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    const statusFilter =
        document.querySelector("#visitStatusFilter");


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterVisits
        );

    }


    /* =====================================================
       DATE FILTER
    ===================================================== */

    const dateFilter =
        document.querySelector("#visitDateFilter");


    if (dateFilter) {

        dateFilter.addEventListener(
            "change",
            filterVisits
        );

    }


    /* =====================================================
       FILTER VISITS
    ===================================================== */

    function filterVisits() {

        const search =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";


        const selectedStatus =
            statusFilter
                ? statusFilter.value
                : "all";


        const selectedDate =
            dateFilter
                ? dateFilter.value
                : "";


        visitRows.forEach(
            function (row) {

                const text =
                    row.textContent
                        .toLowerCase();


                const status =
                    row.dataset.status ||
                    "";


                const date =
                    row.dataset.date ||
                    "";


                const matchesSearch =
                    !search ||
                    text.includes(search);


                const matchesStatus =
                    selectedStatus === "all" ||
                    status === selectedStatus;


                const matchesDate =
                    !selectedDate ||
                    date === selectedDate;


                if (
                    matchesSearch &&
                    matchesStatus &&
                    matchesDate
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


        updateEmptyState();

    }


    /* =====================================================
       EMPTY STATE
    ===================================================== */

    function updateEmptyState() {

        const emptyState =
            document.querySelector(
                "#visitEmptyState"
            );


        if (!emptyState) {
            return;
        }


        const visibleRows =
            Array.from(
                visitRows
            ).filter(
                function (row) {

                    return row.style.display !==
                        "none";

                }
            );


        emptyState.style.display =
            visibleRows.length === 0
                ? "block"
                : "none";

    }


    /* =====================================================
       STATUS UPDATE
    ===================================================== */

    const statusSelects =
        document.querySelectorAll(
            ".visit-status"
        );


    statusSelects.forEach(
        function (select) {

            select.addEventListener(
                "change",
                function () {

                    const visitId =
                        this.dataset.visitId;


                    const status =
                        this.value;


                    if (!visitId) {
                        return;
                    }


                    updateVisitStatus(
                        visitId,
                        status,
                        this
                    );

                }
            );

        }
    );


    function updateVisitStatus(
        visitId,
        status,
        select
    ) {

        select.disabled =
            true;


        fetch(
            "visit-status-update.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body:
                    "id=" +
                    encodeURIComponent(
                        visitId
                    ) +
                    "&status=" +
                    encodeURIComponent(
                        status
                    )
            }
        )
        .then(
            function (response) {

                if (!response.ok) {

                    throw new Error(
                        "Status update failed"
                    );

                }


                return response.json();

            }
        )
        .then(
            function (data) {

                if (
                    data.success
                ) {

                    const row =
                        select.closest(
                            ".visit-row"
                        );


                    if (row) {

                        row.dataset.status =
                            status;

                    }


                    showToast(
                        "Visit status updated.",
                        "success"
                    );

                }
                else {

                    throw new Error(
                        data.message ||
                        "Unable to update visit."
                    );

                }

            }
        )
        .catch(
            function (error) {

                console.error(
                    error
                );


                showToast(
                    "Unable to update visit status.",
                    "error"
                );

            }
        )
        .finally(
            function () {

                select.disabled =
                    false;

            }
        );

    }


    /* =====================================================
       ADD VISIT FORM
    ===================================================== */

    const visitForm =
        document.querySelector(
            "#visitForm"
        );


    if (visitForm) {

        visitForm.addEventListener(
            "submit",
            function (event) {

                clearErrors(
                    visitForm
                );


                const name =
                    visitForm.querySelector(
                        '[name="name"]'
                    );


                const email =
                    visitForm.querySelector(
                        '[name="email"]'
                    );


                const phone =
                    visitForm.querySelector(
                        '[name="phone"]'
                    );


                const property =
                    visitForm.querySelector(
                        '[name="property_id"]'
                    );


                const date =
                    visitForm.querySelector(
                        '[name="visit_date"]'
                    );


                const time =
                    visitForm.querySelector(
                        '[name="visit_time"]'
                    );


                let valid = true;


                if (
                    name &&
                    name.value.trim().length < 2
                ) {

                    showFieldError(
                        name,
                        "Please enter the visitor name."
                    );

                    valid = false;

                }


                if (
                    email &&
                    email.value.trim() &&
                    !validateEmail(
                        email.value.trim()
                    )
                ) {

                    showFieldError(
                        email,
                        "Please enter a valid email."
                    );

                    valid = false;

                }


                if (
                    phone &&
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
                    property &&
                    !property.value
                ) {

                    showFieldError(
                        property,
                        "Please select a property."
                    );

                    valid = false;

                }


                if (
                    date &&
                    !date.value
                ) {

                    showFieldError(
                        date,
                        "Please select a visit date."
                    );

                    valid = false;

                }


                if (
                    time &&
                    !time.value
                ) {

                    showFieldError(
                        time,
                        "Please select a visit time."
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
       PREVENT PAST VISIT DATE
    ===================================================== */

    const visitDateInputs =
        document.querySelectorAll(
            'input[name="visit_date"]'
        );


    visitDateInputs.forEach(
        function (input) {

            const today =
                new Date();


            const year =
                today.getFullYear();


            const month =
                String(
                    today.getMonth() + 1
                ).padStart(
                    2,
                    "0"
                );


            const day =
                String(
                    today.getDate()
                ).padStart(
                    2,
                    "0"
                );


            input.min =
                year +
                "-" +
                month +
                "-" +
                day;

        }
    );


    /* =====================================================
       DELETE VISIT
    ===================================================== */

    document
        .querySelectorAll(
            "[data-delete-visit]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();


                        const confirmed =
                            confirm(
                                "Are you sure you want to delete this visit?"
                            );


                        if (!confirmed) {
                            return;
                        }


                        const url =
                            this.dataset.deleteUrl;


                        if (url) {

                            window.location.href =
                                url;

                        }

                    }
                );

            }
        );


    /* =====================================================
       SELECT ALL VISITS
    ===================================================== */

    const selectAll =
        document.querySelector(
            "#selectAllVisits"
        );


    const visitCheckboxes =
        document.querySelectorAll(
            ".visit-checkbox"
        );


    if (selectAll) {

        selectAll.addEventListener(
            "change",
            function () {

                visitCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            selectAll.checked;

                    }
                );


                updateBulkActions();

            }
        );

    }


    visitCheckboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                updateBulkActions
            );

        }
    );


    function updateBulkActions() {

        const selected =
            document.querySelectorAll(
                ".visit-checkbox:checked"
            );


        const bulkActions =
            document.querySelector(
                "#bulkVisitActions"
            );


        if (bulkActions) {

            bulkActions.style.display =
                selected.length
                    ? "flex"
                    : "none";

        }


        if (
            selectAll &&
            visitCheckboxes.length
        ) {

            selectAll.checked =
                selected.length ===
                visitCheckboxes.length;

        }

    }


    /* =====================================================
       BULK STATUS
    ===================================================== */

    const bulkStatus =
        document.querySelector(
            "#bulkVisitStatus"
        );


    if (bulkStatus) {

        bulkStatus.addEventListener(
            "change",
            function () {

                const status =
                    this.value;


                const selected =
                    document.querySelectorAll(
                        ".visit-checkbox:checked"
                    );


                if (
                    !status ||
                    selected.length === 0
                ) {

                    return;

                }


                const confirmed =
                    confirm(
                        "Update " +
                        selected.length +
                        " selected visits?"
                    );


                if (!confirmed) {

                    this.value =
                        "";

                    return;

                }


                selected.forEach(
                    function (checkbox) {

                        const row =
                            checkbox.closest(
                                ".visit-row"
                            );


                        const select =
                            row?.querySelector(
                                ".visit-status"
                            );


                        if (select) {

                            select.value =
                                status;

                        }

                    }
                );


                showToast(
                    "Selected visits updated.",
                    "success"
                );


                this.value =
                    "";

            }
        );

    }


    /* =====================================================
       CALENDAR VIEW
    ===================================================== */

    const calendarButton =
        document.querySelector(
            "#calendarViewButton"
        );


    const calendar =
        document.querySelector(
            "#visitCalendar"
        );


    if (
        calendarButton &&
        calendar
    ) {

        calendarButton.addEventListener(
            "click",
            function () {

                calendar.classList.toggle(
                    "active"
                );

                document
                    .querySelector(
                        "#visitTable"
                    )
                    ?.classList.toggle(
                        "hidden"
                    );

            }
        );

    }


    /* =====================================================
       TODAY BUTTON
    ===================================================== */

    const todayButton =
        document.querySelector(
            "#todayVisits"
        );


    if (todayButton) {

        todayButton.addEventListener(
            "click",
            function () {

                const today =
                    new Date();


                const year =
                    today.getFullYear();


                const month =
                    String(
                        today.getMonth() + 1
                    ).padStart(
                        2,
                        "0"
                    );


                const day =
                    String(
                        today.getDate()
                    ).padStart(
                        2,
                        "0"
                    );


                const todayString =
                    year +
                    "-" +
                    month +
                    "-" +
                    day;


                if (dateFilter) {

                    dateFilter.value =
                        todayString;

                }


                filterVisits();

            }
        );

    }


    /* =====================================================
       EMAIL VISITOR
    ===================================================== */

    document
        .querySelectorAll(
            "[data-email-visitor]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const email =
                            this.dataset.emailVisitor;


                        if (email) {

                            window.location.href =
                                "mailto:" +
                                email;

                        }

                    }
                );

            }
        );


    /* =====================================================
       CALL VISITOR
    ===================================================== */

    document
        .querySelectorAll(
            "[data-call-visitor]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const phone =
                            this.dataset.callVisitor;


                        if (phone) {

                            window.location.href =
                                "tel:" +
                                phone;

                        }

                    }
                );

            }
        );


    /* =====================================================
       WHATSAPP VISITOR
    ===================================================== */

    document
        .querySelectorAll(
            "[data-whatsapp-visitor]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const phone =
                            this.dataset.whatsappVisitor;


                        if (!phone) {
                            return;
                        }


                        const message =
                            this.dataset.message ||
                            "Hello, regarding your property visit.";


                        const url =
                            "https://wa.me/" +
                            phone.replace(
                                /\D/g,
                                ""
                            ) +
                            "?text=" +
                            encodeURIComponent(
                                message
                            );


                        window.open(
                            url,
                            "_blank"
                        );

                    }
                );

            }
        );


    /* =====================================================
       VIEW VISIT DETAILS
    ===================================================== */

    document
        .querySelectorAll(
            "[data-view-visit]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const url =
                            this.dataset.viewVisit;


                        if (url) {

                            window.location.href =
                                url;

                        }

                    }
                );

            }
        );


    /* =====================================================
       EDIT VISIT
    ===================================================== */

    document
        .querySelectorAll(
            "[data-edit-visit]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const url =
                            this.dataset.editVisit;


                        if (url) {

                            window.location.href =
                                url;

                        }

                    }
                );

            }
        );


    /* =====================================================
       EXPORT VISITS
    ===================================================== */

    const exportButton =
        document.querySelector(
            "#exportVisits"
        );


    if (exportButton) {

        exportButton.addEventListener(
            "click",
            function () {

                const rows =
                    document.querySelectorAll(
                        ".visit-row"
                    );


                if (!rows.length) {

                    showToast(
                        "No visits to export.",
                        "error"
                    );

                    return;

                }


                let csv =
                    "Visitor,Email,Phone,Property,Date,Time,Status\n";


                rows.forEach(
                    function (row) {

                        if (
                            row.style.display ===
                            "none"
                        ) {

                            return;

                        }


                        const cells =
                            row.querySelectorAll(
                                "[data-column]"
                            );


                        const values = [];


                        cells.forEach(
                            function (cell) {

                                const value =
                                    cell.textContent
                                        .trim()
                                        .replace(
                                            /"/g,
                                            '""'
                                        );


                                values.push(
                                    '"' +
                                    value +
                                    '"'
                                );

                            }
                        );


                        if (values.length) {

                            csv +=
                                values.join(
                                    ","
                                ) +
                                "\n";

                        }

                    }
                );


                downloadCSV(
                    csv,
                    "realestate-visits.csv"
                );

            }
        );

    }


    function downloadCSV(
        content,
        filename
    ) {

        const blob =
            new Blob(
                [content],
                {
                    type:
                        "text/csv;charset=utf-8;"
                }
            );


        const url =
            URL.createObjectURL(
                blob
            );


        const link =
            document.createElement(
                "a"
            );


        link.href =
            url;


        link.download =
            filename;


        document.body.appendChild(
            link
        );


        link.click();


        link.remove();


        URL.revokeObjectURL(
            url
        );

    }


    /* =====================================================
       VALIDATION HELPERS
    ===================================================== */

    function validateEmail(
        email
    ) {

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            .test(email);

    }


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

    }


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
       TOAST
    ===================================================== */

    function showToast(
        message,
        type = "success"
    ) {

        const oldToast =
            document.querySelector(
                ".visit-toast"
            );


        if (oldToast) {

            oldToast.remove();

        }


        const toast =
            document.createElement(
                "div"
            );


        toast.className =
            "visit-toast " +
            type;


        toast.textContent =
            message;


        document.body.appendChild(
            toast
        );


        requestAnimationFrame(
            function () {

                toast.classList.add(
                    "show"
                );

            }
        );


        setTimeout(
            function () {

                toast.classList.remove(
                    "show"
                );


                setTimeout(
                    function () {

                        toast.remove();

                    },
                    300
                );

            },
            3000
        );

    }


    /* =====================================================
       TOAST CSS
    ===================================================== */

    const style =
        document.createElement(
            "style"
        );


    style.textContent = `

        .visit-toast {

            position:fixed;

            right:25px;

            bottom:25px;

            z-index:99999;

            padding:12px 18px;

            border-radius:7px;

            background:#174a3a;

            color:#fff;

            font-size:13px;

            font-weight:600;

            opacity:0;

            transform:
                translateY(15px);

            transition:
                opacity .3s ease,
                transform .3s ease;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.15);

        }


        .visit-toast.show {

            opacity:1;

            transform:
                translateY(0);

        }


        .visit-toast.error {

            background:#b43843;

        }


        .input-error {

            border-color:#c63d4a !important;

        }


        .field-error {

            display:block;

            margin-top:5px;

            color:#c63d4a;

            font-size:11px;

        }


        #visitTable.hidden {

            display:none;

        }


        #visitCalendar {

            display:none;

        }


        #visitCalendar.active {

            display:block;

        }

    `;


    document.head.appendChild(
        style
    );


    /* =====================================================
       INITIALIZE
    ===================================================== */

    updateBulkActions();

    updateEmptyState();


    console.log(
        "RealEstate Visits JS loaded successfully."
    );

});