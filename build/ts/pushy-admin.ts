interface ChangedItem {
    working: string;
    index: string;
    path: string;
}

interface ChangedItems {
    [url: string]: ChangedItem;
}

interface Response {
    isSuccess: boolean;
    alert: string;
}

interface PublishingData {
    paths: string[];
    summary: string;
    notes: string;
}

class PushyAdmin {

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
            paths: [],
            summary: '',
            notes: '',
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

        publishingData.summary = summary.value;

        const notes = document.getElementById('notes') as HTMLInputElement;
        publishingData.notes = notes.value;

        const checkboxes = document.getElementsByClassName('selectbox') as HTMLCollectionOf<HTMLInputElement>;
        for (const checkbox of checkboxes) {
            if (checkbox.checked) {
                publishingData.paths.push(checkbox.value);
            }
        }

        return publishingData;
    }

    public async fetchItems(): Promise<void> {
        let answer: ChangedItems;

        try {
            const response = await fetch(
                window.location.pathname + '/pushy:readItems',
                {
                    method: 'POST',
                }
            );

            if (response.ok) {
                answer = await response.json() as ChangedItems;
            } else {
                this.setBannerText('Read Items: No valid response from server.', 'error');

                return;
            }
        } catch (error) {
            this.setBannerText('Read Items: Unexpected error while accessing the server.', 'error');

            return;
        }

        if (answer) {
            this.displayItems(answer);
        }
    }

    private clearInputs() {
        const inputs = document.querySelectorAll<HTMLInputElement>('input, textarea');

        for(const input of inputs) {
            input.value = '';
            input.checked = false;
        }
    }

    private displayItems(items: ChangedItems) {
        this.clearInputs();

        const tableRows = document.getElementById('itemlist') as HTMLElement;
        tableRows.innerHTML = '';

        Object.keys(items).forEach((path: string, i: number) => {
            const item: ChangedItem = items[path];

            const itemRow = document.createElement('tr');
            itemRow.innerHTML = '';

            itemRow.innerHTML = `
                <td class="select">
                    <input id="selectbox${i}" class="selectbox" type="checkbox" value="${item.path}">
                </td>
                <td class="path"><label for="selectbox${i}">${item.path}</td>
            `;

            tableRows.appendChild(itemRow);
        });


        const checkboxes = document.getElementsByClassName('selectbox');
        for (const box of checkboxes) {
            box.addEventListener('click', () => {
                this.enablePublishButton()
            });
        }
    }

    public async publishItems(items: PublishingData) {
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
            this.setBannerText('Publish items: Unexpected error while accessing the server.', 'error');
            return;
        }

        if (response.ok) {
            const answer = await response.json() as Response;

            if (answer.isSuccess) {
                this.setBannerText(answer.alert, 'info');
            } else {
                this.setBannerText(answer.alert, 'error');
            }

            void this.fetchItems();
        } else {
            this.setBannerText('No valid response from server.', 'error');
        }
    }

    public setBannerText(message: string, type: 'info' | 'error') {
        this.clearBannerText();

        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert publish`;

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
