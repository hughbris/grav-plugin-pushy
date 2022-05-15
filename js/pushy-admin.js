"use strict";
class PushyAdmin {
    constructor() {
        this.initEventHandlers();
    }
    initEventHandlers() {
        const publishButton = document.getElementById('publish');
        publishButton.addEventListener('click', (event) => {
            event.preventDefault();
            const publishingItems = this.getSelectedItems();
            if (publishingItems) {
                void this.publishItems(publishingItems);
            }
        });
        const selectAllCheckbox = document.getElementById('select-all');
        selectAllCheckbox.addEventListener('click', () => {
            const allCheckboxes = document.getElementsByClassName('selectbox');
            for (const checkbox of allCheckboxes) {
                checkbox.checked = selectAllCheckbox.checked;
            }
            this.enablePublishButton();
        });
        const summary = document.getElementById('summary');
        summary.addEventListener('input', () => {
            this.enablePublishButton();
        });
    }
    getSelectedItems() {
        const publishingData = {
            paths: [],
            summary: '',
            notes: '',
        };
        const summary = document.getElementById('summary');
        const summaryAlert = document.getElementById('summary-alert');
        if (!summary.value) {
            summary.classList.add('invalid');
            summaryAlert.classList.add('invalid');
            return;
        }
        summary.classList.remove('invalid');
        summaryAlert.classList.remove('invalid');
        publishingData.summary = summary.value;
        const notes = document.getElementById('notes');
        publishingData.notes = notes.value;
        const checkboxes = document.getElementsByClassName('selectbox');
        for (const checkbox of checkboxes) {
            if (checkbox.checked) {
                publishingData.paths.push(checkbox.value);
            }
        }
        return publishingData;
    }
    async fetchItems() {
        let answer;
        try {
            const response = await fetch(window.location.pathname + '/pushy:readItems', {
                method: 'POST',
            });
            if (response.ok) {
                answer = await response.json();
            }
            else {
                this.setBannerText('Read Items: No valid response from server.', 'error');
                return;
            }
        }
        catch (error) {
            this.setBannerText('Read Items: Unexpected error while accessing the server.', 'error');
            return;
        }
        if (answer) {
            const itemCount = Object.keys(answer).length;
            if (itemCount == 0) {
                this.setBannerText('Nothing to publish', 'info');
            }
            else {
                this.displayItems(answer);
            }
        }
    }
    clearInputs() {
        const inputs = document.querySelectorAll('input, textarea');
        for (const input of inputs) {
            input.value = '';
            input.checked = false;
        }
    }
    displayItems(items) {
        this.clearInputs();
        const tableRows = document.getElementById('itemlist');
        tableRows.innerHTML = '';
        Object.keys(items).forEach((path, i) => {
            const item = items[path];
            const itemRow = document.createElement('tr');
            itemRow.innerHTML = `
                <td class="select">
                    <input id="selectbox${i}" class="selectbox" type="checkbox" value="${item.path}">
                </td>
                <td class="path">${item.path}</td>
            `;
            tableRows.appendChild(itemRow);
        });
        const checkboxes = document.getElementsByClassName('selectbox');
        for (const box of checkboxes) {
            box.addEventListener('click', () => {
                this.enablePublishButton();
            });
        }
    }
    async publishItems(items) {
        let response;
        try {
            response = await fetch(window.location.pathname + '/pushy:publishItems', {
                method: 'POST',
                body: JSON.stringify(items),
            });
        }
        catch (error) {
            this.setBannerText('Publish items: Unexpected error while accessing the server.', 'error');
            return;
        }
        if (response.ok) {
            const answer = await response.json();
            if (answer.isSuccess) {
                this.setBannerText(answer.alert, 'info');
            }
            else {
                this.setBannerText(answer.alert, 'error');
            }
            void this.fetchItems();
        }
        else {
            this.setBannerText('No valid response from server.', 'error');
        }
    }
    setBannerText(message, type) {
        this.clearBannerText();
        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert publish`;
        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);
        const messages = document.getElementById('messages');
        messages === null || messages === void 0 ? void 0 : messages.appendChild(newMessage);
    }
    clearBannerText() {
        const alerts = document.getElementsByClassName('alert publish');
        const messages = document.getElementById('messages');
        for (const alert of alerts) {
            messages === null || messages === void 0 ? void 0 : messages.removeChild(alert);
        }
    }
    enablePublishButton() {
        const summary = document.getElementById('summary');
        const hasCheckedItems = document.querySelectorAll('.selectbox:checked').length > 0;
        const publishButton = document.getElementById('publish');
        if (summary.value && hasCheckedItems) {
            publishButton.classList.add('enabled');
        }
        else {
            publishButton.classList.remove('enabled');
        }
    }
}
const admin = new PushyAdmin();
void admin.fetchItems();
//# sourceMappingURL=pushy-admin.js.map