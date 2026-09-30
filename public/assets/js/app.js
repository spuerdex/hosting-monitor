'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar =
        document.querySelector('[data-sidebar]');

    const sidebarToggle =
        document.querySelector('[data-sidebar-toggle]');

    const backdrop =
        document.querySelector('[data-sidebar-backdrop]');

    let previousBodyOverflow = '';

    const openSidebar = () => {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-visible');

        previousBodyOverflow = document.body.style.overflow;
        document.body.classList.add('sidebar-open');

        sidebarToggle?.setAttribute(
            'aria-expanded',
            'true'
        );
    };

    const closeSidebar = () => {
        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.classList.remove('sidebar-open');
        document.body.style.overflow = previousBodyOverflow;
        previousBodyOverflow = '';

        sidebarToggle?.setAttribute(
            'aria-expanded',
            'false'
        );
    };

    sidebarToggle?.addEventListener(
        'click',
        () => {
            if (
                sidebar?.classList.contains(
                    'is-open'
                )
            ) {
                closeSidebar();
                return;
            }

            openSidebar();
        }
    );

    backdrop?.addEventListener(
        'click',
        closeSidebar
    );

    document.addEventListener(
        'keydown',
        event => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        }
    );

    sidebar
        ?.querySelectorAll('a')
        .forEach(link => {
            link.addEventListener(
                'click',
                () => {
                    if (
                        window.innerWidth
                        <= 768
                    ) {
                        closeSidebar();
                    }
                }
            );
        });


    const studentSearch =
        document.getElementById(
            'student-search'
        );

    const studentStatusFilter =
        document.getElementById(
            'student-status-filter'
        );

    const studentNoMatch =
        document.querySelector(
            '[data-student-no-match]'
        );

    const filterStudents = () => {
        const query =
            studentSearch
                ?.value
                .trim()
                .toLowerCase()
            ?? '';

        const status =
            studentStatusFilter
                ?.value
                .toLowerCase()
            ?? 'all';

        const rows = document.querySelectorAll(
            '[data-student-row]'
        );

        let visibleRows = 0;

        rows.forEach(row => {
            const rowText =
                row.textContent
                    .toLowerCase();

            const rowStatus =
                (
                    row.getAttribute(
                        'data-status'
                    )
                    ?? ''
                ).toLowerCase();

            const matchesQuery =
                query === ''
                || rowText.includes(
                    query
                );

            const matchesStatus =
                status === 'all'
                || rowStatus === status;

            row.hidden =
                !(
                    matchesQuery
                    && matchesStatus
                );

            if (!row.hidden) {
                visibleRows++;
            }
        });

        if (studentNoMatch) {
            studentNoMatch.hidden =
                rows.length === 0
                || visibleRows > 0;
        }
    };

    studentSearch?.addEventListener(
        'input',
        filterStudents
    );

    studentStatusFilter?.addEventListener(
        'change',
        filterStudents
    );


    const statusFeedback =
        document.querySelector('[data-status-feedback]');

    statusFeedback?.setAttribute(
        'aria-live',
        'polite'
    );

    const statusCopy =
        statusFeedback?.querySelector('[data-status-copy]');

    const defaultStatusText =
        statusCopy?.textContent ?? '';

    let statusFeedbackTimer = null;

    document
        .querySelectorAll('[data-refresh-status]')
        .forEach(button => {
            button.addEventListener(
                'click',
                () => {
                    if (!statusFeedback || !statusCopy) {
                        return;
                    }

                    if (statusFeedbackTimer !== null) {
                        window.clearTimeout(
                            statusFeedbackTimer
                        );
                    }

                    statusCopy.textContent =
                        'ตรวจสอบสถานะล่าสุดแล้ว';

                    statusFeedbackTimer = window.setTimeout(
                        () => {
                            statusCopy.textContent =
                                defaultStatusText;

                            statusFeedbackTimer = null;
                        },
                        1600
                    );
                }
            );
        });


    document
        .querySelectorAll(
            '[data-copy-command]'
        )
        .forEach(button => {
            button.addEventListener(
                'click',
                async () => {
                    const command =
                        button.getAttribute(
                            'data-copy-command'
                        )
                        ?? '';

                    if (!command) {
                        return;
                    }

                    const originalText =
                        button.textContent;

                    try {
                        await navigator
                            .clipboard
                            .writeText(
                                command
                            );

                        button.textContent =
                            'คัดลอกแล้ว ✓';
                    } catch (error) {
                        const textarea =
                            document.createElement(
                                'textarea'
                            );

                        textarea.value =
                            command;

                        textarea.style.position =
                            'fixed';

                        textarea.style.opacity =
                            '0';

                        document.body.appendChild(
                            textarea
                        );

                        textarea.select();

                        document.execCommand(
                            'copy'
                        );

                        textarea.remove();

                        button.textContent =
                            'คัดลอกแล้ว ✓';
                    }

                    window.setTimeout(
                        () => {
                            button.textContent =
                                originalText;
                        },
                        1600
                    );
                }
            );
        });
});
