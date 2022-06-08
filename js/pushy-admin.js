"use strict";
var BannerStyle;
(function (BannerStyle) {
    BannerStyle[BannerStyle["info"] = 0] = "info";
    BannerStyle[BannerStyle["notice"] = 1] = "notice";
    BannerStyle[BannerStyle["error"] = 2] = "error";
})(BannerStyle || (BannerStyle = {}));
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
                this.publishItems(publishingItems);
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
            message: '',
        };
        const summary = document.getElementById('summary');
        const summaryAlert = document.getElementById('summary-alert');
        const description = document.getElementById('description');
        if (!summary.value) {
            summary.classList.add('invalid');
            summaryAlert.classList.add('invalid');
            return;
        }
        summary.classList.remove('invalid');
        summaryAlert.classList.remove('invalid');
        publishingData.message = summary.value;
        if (description.value) {
            publishingData.message += `\n\n${description.value}`;
        }
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
                this.setBannerText('Read Items: No valid response from server.', BannerStyle.error);
                return;
            }
        }
        catch (error) {
            this.setBannerText('Read Items: Unexpected error while accessing the server.', BannerStyle.error);
            return;
        }
        if (answer) {
            this.setBannerText(`Found ${Object.keys(answer).length} changed items.`, BannerStyle.info);
            this.updateMenuBadge(answer);
            this.displayItems(answer);
        }
    }
    clearInputs() {
        const inputs = document.querySelectorAll('input, textarea');
        for (const input of inputs) {
            input.value = '';
            input.checked = false;
        }
    }
    updateMenuBadge(changedItems) {
        // Find badge for Publish menuitem
        const allMenuItems = document.querySelectorAll('#admin-menu li');
        const index = Array.from(allMenuItems).findIndex(node => { var _a; return ((_a = node.querySelector('em')) === null || _a === void 0 ? void 0 : _a.innerHTML) == 'Publish'; });
        const badge = allMenuItems[index].querySelector('#admin-menu li a .badge.count');
        // If badge is found, update badge
        if (badge) {
            const changedItemCount = Object.keys(changedItems).length;
            badge.innerHTML = changedItemCount > 0 ? changedItemCount.toString() : '';
        }
    }
    displayItems(items) {
        this.clearInputs();
        const newBody = document.createElement('body');
        Object.keys(items).forEach((path, i) => {
            const item = items[path];
            let innerHTML = '';
            innerHTML = `
                <td class="select">
                    <input id="selectbox${i}" class="selectbox" type="checkbox" value="${item.path}">
                </td>
                <td class="path"><label for="selectbox${i}">
                `;
            if (item.isPage) {
                innerHTML +=
                    `
                    <a href="${item.siteUrl}" target="_blank">
                        ${item.title}
                        <i class="fa fa-external-link"></i>
                    </a>
                    `;
            }
            else {
                innerHTML += item.path;
            }
            innerHTML +=
                `
                </td>
                <td>
                    <a href="${item.adminUrl}"><i class="fa fa-fw fa-pencil"></i></a>
                </td>
                `;
            const itemRow = document.createElement('tr');
            itemRow.innerHTML = innerHTML;
            newBody.appendChild(itemRow);
        });
        const tableRows = document.getElementById('itemlist');
        tableRows.innerHTML = newBody.innerHTML;
        const checkboxes = document.getElementsByClassName('selectbox');
        for (const box of checkboxes) {
            box.addEventListener('click', () => {
                this.enablePublishButton();
            });
        }
    }
    async publishItems(items) {
        this.clearBannerText();
        let response;
        try {
            response = await fetch(window.location.pathname + '/pushy:publishItems', {
                method: 'POST',
                body: JSON.stringify(items),
            });
        }
        catch (error) {
            this.setBannerText('Publish items: Unexpected error while accessing the server.', BannerStyle.error);
            return;
        }
        if (response.ok) {
            const answer = await response.json();
            if (answer.isSuccess) {
                this.setBannerText(answer.alert, BannerStyle.info);
            }
            else {
                this.setBannerText(answer.alert, BannerStyle.error);
            }
            void this.fetchItems();
        }
        else {
            this.setBannerText('No valid response from server.', BannerStyle.error);
        }
    }
    setBannerText(message, type) {
        // this.clearBannerText();
        const newMessage = document.createElement('div');
        newMessage.className = `${BannerStyle[type]} alert publish`;
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