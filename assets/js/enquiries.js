document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       REAL ESTATE ENQUIRIES JAVASCRIPT
    ===================================================== */


    /* =====================================================
       SEARCH ENQUIRIES
    ===================================================== */

    const searchInput =
        document.querySelector("#enquirySearch");

    const enquiryRows =
        document.querySelectorAll(".enquiry-row");


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                const search =
                    this.value
                        .toLowerCase()
                        .trim();

                enquiryRows.forEach(
                    function (row) {

                        const text =
                            row.textContent
                                .toLowerCase();

                        row.style.display =
                            !search ||
                            text.includes(search)
                                ? ""
                                : "none";

                    }
                );

                updateEmptyState();

            }
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    const statusFilter =
        document.querySelector(
            "#enquiryStatusFilter"
        );


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterEnquiries
        );

    }


    function filterEnquiries() {

        const selectedStatus =
            statusFilter
                ? statusFilter.value
                : "all";


        const search =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";


        enquiryRows.forEach(
            function (row) {

                const status =
                    row.dataset.status ||
                    "";


                const text =
                    row.textContent
                        .toLowerCase();


                const matchesStatus =
                    selectedStatus === "all" ||
                    status === selectedStatus;


                const matchesSearch =
                    !search ||
                    text.includes(search);


                row.style.display =
                    matchesStatus &&
                    matchesSearch
                        ? ""
                        : "none";

            }
        );


        updateEmptyState();

    }


    /* =====================================================
       EMPTY SEARCH STATE
    ===================================================== */

    function updateEmptyState() {

        const emptyMessage =
            document.querySelector(
                "#enquiryEmptyState"
            );


        if (!emptyMessage) {
            return;
        }


        const visibleRows =
            Array.from(
                enquiryRows
            ).filter(
                function (row) {

                    return row.style.display !==
                        "none";

                }
            );


        emptyMessage.style.display =
            visibleRows.length === 0
                ? "block"
                : "none";

    }


    /* =====================================================
       STATUS UPDATE
    ===================================================== */

    const statusSelects =
        document.querySelectorAll(
            ".enquiry-status"
        );


    statusSelects.forEach(
        function (select) {

            select.addEventListener(
                "change",
                function () {

                    const enquiryId =
                        this.dataset.enquiryId;


                    const newStatus =
                        this.value;


                    if (!enquiryId) {
                        return;
                    }


                    updateEnquiryStatus(
                        enquiryId,
                        newStatus,
                        this
                    );

                }
            );

        }
    );


    function updateEnquiryStatus(
        enquiryId,
        status,
        select
    ) {

        select.disabled = true;


        /*
         * If your PHP backend has an AJAX
         * endpoint, replace the simulated
         * section below with fetch().
         */

        fetch(
            "enquiry-status-update.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body:
                    "id=" +
                    encodeURIComponent(
                        enquiryId
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
                        "Request failed"
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

                    showToast(
                        "Enquiry status updated successfully.",
                        "success"
                    );


                    const row =
                        select.closest(
                            ".enquiry-row"
                        );


                    if (row) {

                        row.dataset.status =
                            status;

                    }

                }
                else {

                    throw new Error(
                        data.message ||
                        "Unable to update status."
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
                    "Unable to update enquiry status.",
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
       DELETE ENQUIRY
    ===================================================== */

    document
        .querySelectorAll(
            "[data-delete-enquiry]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();


                        const enquiryId =
                            this.dataset.deleteEnquiry;


                        if (!enquiryId) {
                            return;
                        }


                        const confirmed =
                            confirm(
                                "Are you sure you want to delete this enquiry?"
                            );


                        if (!confirmed) {
                            return;
                        }


                        const deleteUrl =
                            this.dataset.deleteUrl;


                        if (deleteUrl) {

                            window.location.href =
                                deleteUrl;

                        }

                    }
                );

            }
        );


    /* =====================================================
       SELECT ALL ENQUIRIES
    ===================================================== */

    const selectAll =
        document.querySelector(
            "#selectAllEnquiries"
        );


    const enquiryCheckboxes =
        document.querySelectorAll(
            ".enquiry-checkbox"
        );


    if (selectAll) {

        selectAll.addEventListener(
            "change",
            function () {

                enquiryCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            selectAll.checked;

                    }
                );


                updateBulkActions();

            }
        );

    }


    enquiryCheckboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                function () {

                    updateBulkActions();

                }
            );

        }
    );


    function updateBulkActions() {

        const selected =
            document.querySelectorAll(
                ".enquiry-checkbox:checked"
            );


        const bulkActions =
            document.querySelector(
                "#bulkEnquiryActions"
            );


        if (bulkActions) {

            bulkActions.style.display =
                selected.length > 0
                    ? "flex"
                    : "none";

        }


        if (
            selectAll &&
            enquiryCheckboxes.length > 0
        ) {

            selectAll.checked =
                selected.length ===
                enquiryCheckboxes.length;

        }

    }


    /* =====================================================
       BULK STATUS UPDATE
    ===================================================== */

    const bulkStatus =
        document.querySelector(
            "#bulkEnquiryStatus"
        );


    if (bulkStatus) {

        bulkStatus.addEventListener(
            "change",
            function () {

                const selected =
                    document.querySelectorAll(
                        ".enquiry-checkbox:checked"
                    );


                const status =
                    this.value;


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
                        " enquiries?"
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
                                ".enquiry-row"
                            );


                        const select =
                            row?.querySelector(
                                ".enquiry-status"
                            );


                        if (select) {

                            select.value =
                                status;

                        }

                    }
                );


                showToast(
                    "Selected enquiries updated.",
                    "success"
                );


                this.value =
                    "";

            }
        );

    }


    /* =====================================================
       VIEW ENQUIRY DETAILS
    ===================================================== */

    document
        .querySelectorAll(
            "[data-view-enquiry]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const url =
                            this.dataset.viewEnquiry;


                        if (url) {

                            window.location.href =
                                url;

                        }

                    }
                );

            }
        );


    /* =====================================================
       REPLY TO ENQUIRY
    ===================================================== */

    const replyForm =
        document.querySelector(
            "#enquiryReplyForm"
        );


    if (replyForm) {

        replyForm.addEventListener(
            "submit",
            function (event) {

                const message =
                    replyForm.querySelector(
                        '[name="message"]'
                    );


                if (
                    !message ||
                    !message.value.trim()
                ) {

                    event.preventDefault();


                    showToast(
                        "Please enter a reply message.",
                        "error"
                    );


                    message?.focus();

                    return;

                }


                const submitButton =
                    replyForm.querySelector(
                        'button[type="submit"]'
                    );


                if (submitButton) {

                    submitButton.disabled =
                        true;

                    submitButton.textContent =
                        "Sending...";

                }

            }
        );

    }


    /* =====================================================
       MESSAGE CHARACTER COUNTER
    ===================================================== */

    const messageInputs =
        document.querySelectorAll(
            "textarea[data-max-length]"
        );


    messageInputs.forEach(
        function (textarea) {

            const maxLength =
                parseInt(
                    textarea.dataset.maxLength,
                    10
                );


            if (
                isNaN(maxLength)
            ) {
                return;
            }


            let counter =
                textarea.parentElement.querySelector(
                    ".message-counter"
                );


            if (!counter) {

                counter =
                    document.createElement(
                        "small"
                    );

                counter.className =
                    "message-counter";

                textarea.parentElement.appendChild(
                    counter
                );

            }


            function updateCounter() {

                const length =
                    textarea.value.length;


                counter.textContent =
                    length +
                    " / " +
                    maxLength;


                if (
                    length >= maxLength
                ) {

                    counter.classList.add(
                        "limit-reached"
                    );

                }
                else {

                    counter.classList.remove(
                        "limit-reached"
                    );

                }

            }


            textarea.addEventListener(
                "input",
                updateCounter
            );


            updateCounter();

        }
    );


    /* =====================================================
       EMAIL ENQUIRY
    ===================================================== */

    document
        .querySelectorAll(
            "[data-email-enquiry]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const email =
                            this.dataset.emailEnquiry;


                        if (!email) {
                            return;
                        }


                        window.location.href =
                            "mailto:" +
                            email;

                    }
                );

            }
        );


    /* =====================================================
       CALL ENQUIRY
    ===================================================== */

    document
        .querySelectorAll(
            "[data-call-enquiry]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const phone =
                            this.dataset.callEnquiry;


                        if (!phone) {
                            return;
                        }


                        window.location.href =
                            "tel:" +
                            phone;

                    }
                );

            }
        );


    /* =====================================================
       WHATSAPP ENQUIRY
    ===================================================== */

    document
        .querySelectorAll(
            "[data-whatsapp-enquiry]"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const phone =
                            this.dataset.whatsappEnquiry;


                        if (!phone) {
                            return;
                        }


                        const message =
                            this.dataset.message ||
                            "Hello, I am interested in this property.";


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
       DATE FILTER
    ===================================================== */

    const dateFilter =
        document.querySelector(
            "#enquiryDateFilter"
        );


    if (dateFilter) {

        dateFilter.addEventListener(
            "change",
            function () {

                const selectedDate =
                    this.value;


                enquiryRows.forEach(
                    function (row) {

                        const rowDate =
                            row.dataset.date ||
                            "";


                        row.style.display =
                            !selectedDate ||
                            rowDate === selectedDate
                                ? ""
                                : "none";

                    }
                );


                updateEmptyState();

            }
        );

    }


    /* =====================================================
       EXPORT ENQUIRIES
    ===================================================== */

    const exportButton =
        document.querySelector(
            "#exportEnquiries"
        );


    if (exportButton) {

        exportButton.addEventListener(
            "click",
            function () {

                const rows =
                    document.querySelectorAll(
                        ".enquiry-row"
                    );


                if (
                    rows.length === 0
                ) {

                    showToast(
                        "No enquiries to export.",
                        "error"
                    );

                    return;

                }


                let csv =
                    "Name,Email,Phone,Status,Date\n";


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

                                let value =
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


                        if (
                            values.length
                        ) {

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
                    "realestate-enquiries.csv"
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
       TOAST NOTIFICATION
    ===================================================== */

    function showToast(
        message,
        type = "success"
    ) {

        const oldToast =
            document.querySelector(
                ".enquiry-toast"
            );


        if (oldToast) {

            oldToast.remove();

        }


        const toast =
            document.createElement(
                "div"
            );


        toast.className =
            "enquiry-toast " +
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

        .enquiry-toast {

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


        .enquiry-toast.show {

            opacity:1;

            transform:
                translateY(0);

        }


        .enquiry-toast.error {

            background:#b43843;

        }


        .message-counter {

            display:block;

            margin-top:5px;

            text-align:right;

            color:#777;

            font-size:11px;

        }


        .message-counter.limit-reached {

            color:#b43843;

            font-weight:700;

        }


        .enquiry-row {

            transition:
                background .2s ease;

        }


        .enquiry-row:hover {

            background:#fafafa;

        }

    `;


    document.head.appendChild(
        style
    );


    /* =====================================================
       KEYBOARD SHORTCUT
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            /*
             * Ctrl + K = focus enquiry search
             */

            if (
                event.ctrlKey &&
                event.key.toLowerCase() ===
                "k"
            ) {

                event.preventDefault();


                searchInput?.focus();

            }

        }
    );


    /* =====================================================
       INITIALIZE
    ===================================================== */

    updateBulkActions();

    updateEmptyState();


    console.log(
        "RealEstate Enquiries JS loaded successfully."
    );

});