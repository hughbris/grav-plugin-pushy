enum GitItemType {
    Page = 'page',
    Module = 'module',
    Config = 'config',
    Other = 'other',
}

interface ChangedItem {
    working: string;
    index: string;
    path: string;
    orig_path: string;
    type: GitItemType;
    title: string;
    adminUrl: string;
    siteUrl: string;
}

interface Response {
    isSuccess: boolean;
    alert: string;
}

interface PublishingData {
    items: ChangedItem[];
    message: string;
}

enum BannerStyle {
    info,
    notice,
    error,
}

class PushyAdmin {
    changedItems: ChangedItem[] = [];

    constructor() {
        this.initEventHandlers();
    }

    public initEventHandlers() {
        const publishButton = document.getElementById('publish') as HTMLElement;

        publishButton.addEventListener('click', (event) => {
            event.preventDefault();

            const publishingItems: PublishingData | undefined = this.getSelectedItems();

            if (publishingItems) {
                this.publishItems(publishingItems);
            }
        });

        const selectAllCheckbox = document.getElementById('select-all') as HTMLInputElement;

        selectAllCheckbox.addEventListener('click', () => {
            const allCheckboxes = document.getElementsByClassName('selectbox') as HTMLCollectionOf<HTMLInputElement>;

            for (const checkbox of allCheckboxes) {
                checkbox.checked = selectAllCheckbox.checked;
            }

            this.enablePublishButton();
        });

        const summary = document.getElementById('summary') as HTMLInputElement;

        summary.addEventListener('input', () => {
            this.enablePublishButton()
        });
    }

    private getSelectedItems(): PublishingData | undefined {
        const publishingData: PublishingData = {
            items: [],
            message: '',
        };

        const summary = document.getElementById('summary') as HTMLInputElement;
        const summaryAlert = document.getElementById('summary-alert') as HTMLInputElement;

        if (!summary.value) {
            summary.classList.add('invalid');
            summaryAlert.classList.add('invalid');

            return;
        }

        summary.classList.remove('invalid');
        summaryAlert.classList.remove('invalid');

        publishingData.message = summary.value;

        const checkboxes = document.getElementsByClassName('selectbox') as HTMLCollectionOf<HTMLInputElement>;

        for (let i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                publishingData.items.push(this.changedItems[i]);
            }
        }

        return publishingData;
    }

    public async fetchItems(): Promise<void> {
        try {
            const response = await fetch(
                window.location.pathname + '/pushy:readItems',
                {
                    method: 'POST',
                }
            );

            if (response.ok) {
                this.changedItems = await response.json() as ChangedItem[];
            } else {
                this.setBannerText('Read Items: No valid response from server.', BannerStyle.error);

                return;
            }
        } catch (error) {
            this.setBannerText('Read Items: Unexpected error while accessing the server.', BannerStyle.error);

            return;
        }

        if (this.changedItems) {
            this.setBannerText(`Found ${Object.keys(this.changedItems).length} changed items.`, BannerStyle.info);
            this.updateMenuBadge();
            this.displayItems();
        }
    }

    private clearInputs() {
        const inputs = document.querySelectorAll<HTMLInputElement>('input, textarea');

        for (const input of inputs) {
            input.value = '';
            input.checked = false;
        }
    }

    private updateMenuBadge() {
        // Find badge for Publish menuitem
        const allMenuItems = document.querySelectorAll('#admin-menu li');
        const index = Array.from(allMenuItems).findIndex(node => node.querySelector('em')?.innerHTML == 'Publish');
        const badge = allMenuItems[index].querySelector('#admin-menu li a .badge.count');

        // If badge is found, update badge
        if (badge) {
            const changedItemCount = Object.keys(this.changedItems).length;
            badge.innerHTML = changedItemCount > 0 ? changedItemCount.toString() : '';
        }
    }

    private displayItems() {
        this.clearInputs();

        const newBody = document.createElement('body')

        for(let i = 0; i < this.changedItems.length; i++) {
            const item: ChangedItem = this.changedItems[i];

            let innerHTML = '';
            let status = '';
            let pathTitle = '';

            switch(item.index) {
                case 'A':
                    status = 'Added';
                    pathTitle = item.title;
                    break;
                case 'M':
                    status = 'Modified';
                    pathTitle = item.title;
                    break;
                case 'D':
                    status = 'Deleted';
                    pathTitle = item.path;
                    break;
                case 'R':
                    status = 'Renamed';
                    pathTitle = `${item.orig_path} <i class="fa fa-long-arrow-right"></i> ${item.path}`;
                    break;
                default:
                    throw new Error(`Invalid status "${item.index}"`);
            }

            innerHTML = `
                <td class="select">
                    <input class="selectbox" type="checkbox">
                </td>
                <td class="status">
                    ${status}
                </td>
                `;

            if (item.type == GitItemType.Page) {
                if (item.index == 'D') {
                    innerHTML += `<td class="path">${pathTitle}</td>`;
                } else {
                    innerHTML +=
                        `
                        <td class="path">
                            <a href="${item.siteUrl}" target="_blank">
                                ${pathTitle}
                                <i class="fa fa-external-link"></i>
                            </a>
                        </td>
                        `;
                }
            } else if (item.type == GitItemType.Module) {
                innerHTML += item.title;
            } else if (item.type == GitItemType.Config) {
                innerHTML += item.path;
            } else if (item.type == GitItemType.Other && item.siteUrl) {
                innerHTML +=
                    `
                    <a href="${item.siteUrl}" target="_blank">
                        ${item.path}
                        <i class="fa fa-external-link"></i>
                    </a>
                    `;
            } else {
                innerHTML += item.path;
            }

            // Set icon for editing item
            if (item.adminUrl) {
                innerHTML +=
                    `
                    <td class="edit">
                        <a href="${item.adminUrl}"><i class="fa fa-fw fa-pencil"></i></a>
                    </td>
                    `;
            }

            const itemRow = document.createElement('tr');
            itemRow.innerHTML = innerHTML;

            newBody.appendChild(itemRow);
        };

        const tableRows = document.getElementById('itemlist') as HTMLElement;
        tableRows.innerHTML = newBody.innerHTML;

        const checkboxes = document.getElementsByClassName('selectbox');
        for (const box of checkboxes) {
            box.addEventListener('click', () => {
                this.enablePublishButton()
            });
        }
    }

    public async publishItems(items: PublishingData) {
        this.clearBannerText();

        let response: Response;

        try {
            response = await fetch(
                window.location.pathname + '/pushy:publishItems',
                {
                    method: 'POST',
                    body: JSON.stringify(items),
                }
            );
        } catch (error) {
            this.setBannerText('Publish items: Unexpected error while accessing the server.', BannerStyle.error);
            return;
        }

        if (response.ok) {
            const answer = await response.json() as Response;

            if (answer.isSuccess) {
                this.setBannerText(answer.alert, BannerStyle.info);
            } else {
                this.setBannerText(answer.alert, BannerStyle.error);
            }

            void this.fetchItems();
        } else {
            this.setBannerText('No valid response from server.', BannerStyle.error);
        }
    }

    public setBannerText(message: string, type: BannerStyle) {
        // this.clearBannerText();

        const newMessage = document.createElement('div');
        newMessage.className = `${BannerStyle[type]} alert publish`;

        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);

        const messages = document.getElementById('messages');
        messages?.appendChild(newMessage);
    }

    public clearBannerText() {
        const alerts = document.getElementsByClassName('alert publish');

        const messages = document.getElementById('messages');

        for (const alert of alerts) {
            messages?.removeChild(alert);
        }
    }

    public enablePublishButton() {
        const summary = document.getElementById('summary') as HTMLInputElement;
        const hasCheckedItems = document.querySelectorAll('.selectbox:checked').length > 0;
        const publishButton = document.getElementById('publish') as HTMLAnchorElement;

        if (summary.value && hasCheckedItems) {
            publishButton.classList.add('enabled');
        } else {
            publishButton.classList.remove('enabled');
        }
    }
}

const admin = new PushyAdmin();
void admin.fetchItems();
