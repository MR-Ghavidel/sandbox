import Sortable from 'sortablejs';
import { onPageLoad } from './support/page';

onPageLoad(() => {
    const board = document.querySelector('[data-task-board]');

    if (!board) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const editDialog = document.getElementById('edit-task-dialog');
    const editForm = editDialog.querySelector('form');
    const showDialog = document.getElementById('show-task-dialog');
    let selectedItem = null;

    const request = (url, method, body) =>
        fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        }).then((response) => {
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            return response;
        });

    const refreshList = (list) => {
        const items = list.querySelectorAll('[data-task-id]');

        items.forEach((item, index) => {
            item.querySelector('[data-task-number]').textContent = (index + 1).toLocaleString('fa-IR');
        });

        list.closest('[data-day]').querySelector('[data-empty]').classList.toggle('hidden', items.length > 0);
        list.closest('[data-day]').querySelector('[data-count]').textContent = items.length.toLocaleString('fa-IR');
    };

    const refreshTodaySummary = () => {
        const summary = board.querySelector('[data-today-summary]');
        const items = [...summary.querySelectorAll('[data-task-id]')];
        const completedCount = items.filter((item) => item.dataset.status === 'completed').length;

        summary.querySelector('[data-today-completed]').textContent = completedCount.toLocaleString('fa-IR');
        summary.querySelector('[data-today-progress]').style.width =
            `${items.length ? Math.round((completedCount / items.length) * 100) : 0}%`;
    };

    const failAndReload = () => {
        alert('ذخیره تغییرات با خطا مواجه شد. صفحه دوباره بارگذاری می‌شود.');
        window.location.reload();
    };

    board.querySelectorAll('[data-task-list]').forEach((list) => {
        Sortable.create(list, {
            group: 'tasks',
            handle: '[data-drag-handle]',
            animation: 150,
            ghostClass: 'opacity-40',
            chosenClass: 'shadow-lg',
            onEnd: (event) => {
                const lists = [...new Set([event.from, event.to])];

                lists.forEach(refreshList);

                request(board.dataset.orderUrl, 'PATCH', {
                    days: lists.map((changedList) => ({
                        date: changedList.dataset.date,
                        task_ids: [...changedList.querySelectorAll('[data-task-id]')].map((item) =>
                            Number(item.dataset.taskId),
                        ),
                    })),
                }).catch(failAndReload);
            },
        });
    });

    board.addEventListener('change', (event) => {
        const select = event.target.closest('[data-status-select]');

        if (!select) {
            return;
        }

        const item = select.closest('[data-task-id]');

        request(item.dataset.updateUrl, 'PATCH', { status: select.value })
            .then((response) => response.json())
            .then((task) => {
                // The same task can appear both in its day and in the "today" summary.
                board.querySelectorAll(`[data-task-id="${task.id}"]`).forEach((sameTask) => {
                    sameTask.dataset.status = task.status;
                    sameTask.dataset.task = JSON.stringify({
                        ...JSON.parse(sameTask.dataset.task),
                        status: task.status,
                        status_label: task.status_label,
                    });

                    const badge = sameTask.querySelector('[data-status-badge]');

                    if (badge) {
                        badge.textContent = task.status_label;
                    }
                });

                refreshTodaySummary();
            })
            .catch(failAndReload);
    });

    const openEditDialog = (item) => {
        const task = JSON.parse(item.dataset.task);

        editForm.action = item.dataset.updateUrl;
        editForm.elements.title.value = task.title;
        editForm.elements.description.value = task.description ?? '';
        editForm.elements.status.value = task.status;
        editDialog.showModal();
    };

    const openShowDialog = (item) => {
        const task = JSON.parse(item.dataset.task);

        showDialog.querySelectorAll('[data-field]').forEach((field) => {
            field.textContent = task[field.dataset.field] ?? '—';
        });

        if (!task.description) {
            showDialog.querySelector('[data-field="description"]').textContent = 'توضیحاتی ثبت نشده است.';
        }

        selectedItem = item;
        showDialog.showModal();
    };

    board.addEventListener('click', (event) => {
        const item = event.target.closest('[data-task-id]');

        if (!item) {
            return;
        }

        if (event.target.closest('[data-edit-task]')) {
            openEditDialog(item);
        } else if (!event.target.closest('button, select, form, a, input')) {
            openShowDialog(item);
        }
    });

    board.addEventListener('change', (event) => {
        const toggle = event.target.closest('[data-description-toggle]');

        if (!toggle) {
            return;
        }

        const description = toggle.form.elements.description;

        description.classList.toggle('hidden', !toggle.checked);
        description.disabled = !toggle.checked;

        if (toggle.checked) {
            description.focus();
        }
    });

    board.addEventListener('submit', (event) => {
        if (event.target.matches('[data-confirm]') && !confirm(event.target.dataset.confirm)) {
            event.preventDefault();
        }
    });

    showDialog.querySelector('[data-edit-from-details]').addEventListener('click', () => {
        showDialog.close();
        openEditDialog(selectedItem);
    });

    [showDialog, editDialog].forEach((dialog) => {
        dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
    });

    // The read-only details dialog also closes when clicking on the dark backdrop around it.
    showDialog.addEventListener('click', (event) => {
        if (event.target === showDialog) {
            showDialog.close();
        }
    });
});
