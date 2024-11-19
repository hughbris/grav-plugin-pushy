"use strict";
var GitItemType;
(function (GitItemType) {
    GitItemType["Page"] = "page";
    GitItemType["Module"] = "module";
    GitItemType["Config"] = "config";
    GitItemType["Other"] = "other";
})(GitItemType || (GitItemType = {}));
var BannerStyle;
(function (BannerStyle) {
    BannerStyle[BannerStyle["info"] = 0] = "info";
    BannerStyle[BannerStyle["notice"] = 1] = "notice";
    BannerStyle[BannerStyle["error"] = 2] = "error";
})(BannerStyle || (BannerStyle = {}));
class PushyAdmin {
    constructor() {
        this.changedItems = [];
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
        const summary = document.getElementById('summary');
        summary.addEventListener('input', () => {
            this.enablePublishButton();
        });
    }
    getSelectedItems() {
        const publishingData = {
            items: [],
            message: '',
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
        publishingData.message = summary.value;
        const checkboxes = document.getElementsByClassName('selectbox');
        for (let i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                publishingData.items.push(this.changedItems[i]);
            }
        }
        return publishingData;
    }
    async fetchItems() {
        try {
            const response = await fetch(window.location.pathname + '/pushy:readItems', {
                method: 'POST',
            });
            if (response.ok) {
                this.changedItems = await response.json();
            }
            else {
                this.setBannerText(pushy.translations.fetchInvalidResponse, BannerStyle.error);
                return;
            }
        }
        catch (error) {
            this.setBannerText(pushy.translations.fetchException, BannerStyle.error);
            return;
        }
        if (this.changedItems) {
            const text = pushy.translations.fetchItemsFound.replace('{count}', this.changedItems.length.toString());
            this.setBannerText(text, BannerStyle.info);
            this.updateMenuBadge();
            this.displayItems();
        }
    }
    clearInputs() {
        const inputs = document.querySelectorAll('input, textarea');
        for (const input of inputs) {
            input.value = '';
            input.checked = false;
        }
    }
    updateMenuBadge() {
        // Find badge for Publish menuitem
        const allMenuItems = document.querySelectorAll('#admin-menu li');
        const index = Array.from(allMenuItems).findIndex(node => { var _a; return ((_a = node.querySelector('em')) === null || _a === void 0 ? void 0 : _a.innerHTML) == pushy.translations.menuLabel; });
        const badge = allMenuItems[index].querySelector('#admin-menu li a .badge.count');
        // If badge is found, update badge
        if (badge) {
            const changedItemCount = Object.keys(this.changedItems).length;
            badge.innerHTML = changedItemCount > 0 ? changedItemCount.toString() : '';
        }
    }
    displayItems() {
        this.clearInputs();
        const list = document.getElementById('list');
        list.innerHTML = '';
        const summaryWrapper = document.getElementById('summary-wrapper');
        if (this.changedItems.length == 0) {
            summaryWrapper.style.display = 'none';
            this.showNoItemsFoundMessage();
            return;
        }
        this.addHeaderToList(list);
        summaryWrapper.style.display = 'default';
        for (let i = 0; i < this.changedItems.length; i++) {
            const item = this.changedItems[i];
            let innerHTML = '';
            let status = '';
            let pathTitle = '';
            switch (item.index) {
                case 'A':
                    status = pushy.translations.statusNew;
                    pathTitle = item.title;
                    break;
                case 'M':
                    status = pushy.translations.statusModified;
                    pathTitle = item.title;
                    break;
                case 'D':
                    status = pushy.translations.statusDeleted;
                    pathTitle = encodeURI(item.path);
                    break;
                case 'R':
                    status = pushy.translations.statusRenamed;
                    pathTitle = `${encodeURI(item.orig_path)} <i class="fa fa-long-arrow-right"></i> ${encodeURI(item.path)}`;
                    break;
                default:
                    throw new Error(`Invalid status "${item.index}"`);
            }
            innerHTML = `
                <div class="select"><input class="selectbox" type="checkbox"></div>           
                <div class="status">${status}</div>
                `;
            if (item.type == GitItemType.Page) {
                if (item.index == 'D') {
                    innerHTML += `<div class="path">${pathTitle}</div>`;
                }
                else {
                    innerHTML +=
                        `
                        <div class="path">
                            <a href="${item.siteUrl}" target="_blank">
                                ${pathTitle}
                                <i class="fa fa-external-link"></i>
                            </a>
                        </div>
                        `;
                }
            }
            else if (item.type == GitItemType.Module) {
                innerHTML +=
                    `
                    <div class="path">
                        <a href="${item.siteUrl}" target="_blank">
                            ${pathTitle}
                            <i class="fa fa-external-link"></i>
                        </a>
                    </div>
                    `;
            }
            else if (item.type == GitItemType.Config) {
                innerHTML += `<div class="path">${pathTitle}</div>`;
            }
            else if (item.type == GitItemType.Other && item.siteUrl) {
                innerHTML +=
                    `
                    <a href="${item.siteUrl}" target="_blank">
                        ${item.path}
                        <i class="fa fa-external-link"></i>
                    </a>
                    `;
            }
            else {
                innerHTML += `<div class="path">${item.path}</div>`;
            }
            // Set icon for editing item
            if (item.adminUrl) {
                innerHTML +=
                    `
                    <div class="edit">
                        <a href="${item.adminUrl}"><i class="fa fa-fw fa-pencil"></i></a>
                    </div>
                    `;
            }
            else {
                innerHTML += `<div class="edit"></div>`;
            }
            this.addItemToList(list, innerHTML);
        }
        ;
        const checkboxes = document.getElementsByClassName('selectbox');
        for (const box of checkboxes) {
            box.addEventListener('click', () => {
                this.enablePublishButton();
            });
        }
    }
    addHeaderToList(list) {
        const innerHTML = `
        <div class="header select-all"><input id="select-all" type="checkbox"></div>
        <div class="header status">${pushy.translations.listHeaderStatus}</div>
        <div class="header path">${pushy.translations.listHeaderPath}</div>
        <div class="header edit">${pushy.translations.listHeaderEdit}</div>
        `;
        const header = document.createElement('template');
        header.innerHTML = innerHTML;
        list.append(...header.content.children);
        this.addEventHandlerToSelectAll();
    }
    showNoItemsFoundMessage() {
        const template = document.createElement('template');
        template.innerHTML = `<p id="noitmsfound">${pushy.translations.fetchNoItemsFound}</p>`;
        const noItemsFound = document.getElementById('no-items-found');
        noItemsFound.append(template.content.firstChild);
    }
    addEventHandlerToSelectAll() {
        const selectAllCheckbox = document.getElementById('select-all');
        selectAllCheckbox.addEventListener('click', () => {
            const allCheckboxes = document.getElementsByClassName('selectbox');
            for (const checkbox of allCheckboxes) {
                checkbox.checked = selectAllCheckbox.checked;
            }
            this.enablePublishButton();
        });
    }
    addItemToList(list, innerHTML) {
        const itemRow = document.createElement('template');
        itemRow.innerHTML = innerHTML;
        list.append(...itemRow.content.children);
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
            this.setBannerText(pushy.translations.publishException, BannerStyle.error);
            return;
        }
        if (response.ok) {
            const answer = await response.json();
            if (answer.isSuccess) {
                this.setBannerText(answer.alert, BannerStyle.info);
                void this.fetchItems();
            }
            else {
                this.setBannerText(answer.alert, BannerStyle.error);
            }
        }
        else {
            this.setBannerText(pushy.translations.publishInvalidResponse, BannerStyle.error);
        }
    }
    setBannerText(message, type) {
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