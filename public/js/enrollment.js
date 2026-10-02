(() => {
    const curriculum = document.querySelector('#curriculum-form');
    const application = document.querySelector('#enrollment-submit');
    if (!curriculum || !application) return;

    const filters = [...curriculum.querySelectorAll('select[name]')].filter(field => field.form === curriculum);
    const initialValues = filters.map(field => field.value);
    const subjects = [...application.querySelectorAll('.subject-select-checkbox')];
    const picker = document.querySelector('#subject-picker');
    const add = document.querySelector('#add-subject');
    const addAll = document.querySelector('#add-all-subjects');
    const empty = document.querySelector('#subjects-empty');
    const total = document.querySelector('#selection-total');
    const forward = document.querySelector('#forward-enrollment');
    const notice = document.querySelector('#curriculum-notice');
    const isStale = () => filters.some((field, index) => field.value !== initialValues[index]);

    const update = () => {
        const stale = isStale();
        const selected = subjects.filter(field => field.checked);
        subjects.forEach(field => {
            field.disabled = stale;
            const row = field.closest('tr');
            row.hidden = !field.checked;
            const status = row.querySelector('[data-subject-status]');
            status.textContent = 'Added';
            status.classList.add('status-approved');
            status.classList.remove('subject-available');
            row.querySelector('.remove-subject').disabled = stale;
            const option = [...picker.options].find(option => option.value === field.value);
            option.disabled = field.checked;
        });
        if (picker) {
            if (picker.selectedOptions[0]?.disabled) picker.value = '';
            picker.disabled = stale || selected.length === subjects.length;
            add.disabled = stale || !picker.value || picker.selectedOptions[0]?.disabled;
            addAll.disabled = stale || selected.length === subjects.length;
        }
        if (empty) empty.hidden = selected.length > 0;
        notice.hidden = !stale;
        forward.disabled = stale || selected.length === 0;
        const units = selected.reduce((sum, field) => sum + Number(field.dataset.units), 0);
        total.textContent = stale ? 'Reload subjects to continue' : `${selected.length} ${selected.length === 1 ? 'subject' : 'subjects'} added · ${Number(units.toFixed(2))} units`;
    };

    if (subjects.length) {
        total.hidden = false;
        picker.closest('.subject-picker-row').hidden = false;
        addAll.hidden = false;
        picker.addEventListener('change', update);
        add.addEventListener('click', () => {
            if (isStale()) return;
            const subject = subjects.find(field => field.value === picker.value);
            if (!subject || subject.checked) return;
            subject.checked = true;
            picker.value = '';
            update();
            if (!picker.disabled) picker.focus();
            else forward.focus();
        });
        addAll.addEventListener('click', () => {
            if (isStale()) return;
            subjects.forEach(field => { field.checked = true; });
            update();
            forward.focus();
        });
        subjects.forEach(field => {
            // Keep native checkboxes as a functional fallback when JavaScript is unavailable.
            field.closest('label').hidden = true;
            const remove = field.closest('tr').querySelector('.remove-subject');
            remove.hidden = false;
            remove.addEventListener('click', () => {
                if (isStale()) return;
                field.checked = false;
                update();
                picker.focus();
            });
        });
    }
    filters.forEach(field => field.addEventListener('change', update));

    // Load the new course's majors without submitting a major from the previous course.
    document.querySelector('#filter-program').addEventListener('change', (event) => {
        if (!event.target.value) return;
        document.querySelector('#filter-major').disabled = true;
        curriculum.requestSubmit();
    });
    curriculum.addEventListener('submit', () => {
        document.querySelector('#filter-school-year').value = document.querySelector('#school_year').value;
    });
    application.addEventListener('submit', (event) => {
        if (isStale() || !subjects.some(field => field.checked)) {
            event.preventDefault();
            update();
        }
    });
    update();
})();
