interface ChangedPage {
    working: string;
    index: string;
    path: string;
}

interface ChangedPages {
    [url: string]: ChangedPage;
}

interface Response {
    isSuccess: boolean;
    alert: string;
}

interface PageData {
    path: string;
    message: string;
}

class PushyAdmin {

    constructor() {
        this.initButtonHandlers();
    }

    public initButtonHandlers() {
        const commitButton = document.getElementById('commit-selected') as HTMLElement;

        commitButton.addEventListener('click', (event) => {
            event.preventDefault();

            const pages: PageData[] = this.getSelectedPages();
            void this.commitPages(pages);
        });

        const revertButton = document.getElementById('revert-selected') as HTMLElement;

        revertButton.addEventListener('click', (event) => {
            event.preventDefault();

            const pages: PageData[] = this.getSelectedPages();
            void this.revertPages(pages);
        });

        const selectAll = document.getElementById('select-all') as HTMLInputElement;

        selectAll.addEventListener('click', () => {
            const allBoxes = document.getElementsByClassName('selectbox');
            const isSelectAllChecked = selectAll.checked;

            for (let i = 0; i < allBoxes.length; i++) {
                const select = allBoxes[i] as HTMLInputElement;
                select.checked = isSelectAllChecked;
            }
        });

        const bulkSelect = document.getElementById('bulk-select') as HTMLInputElement;
        const bulkMessage = document.getElementById('bulk-message') as HTMLInputElement;

        bulkSelect.addEventListener('click', () => {
            bulkMessage.style.visibility = bulkSelect.checked ? 'visible' : 'hidden';
        });
    }

    private getSelectedPages() {
        const selected = document.getElementsByClassName('selectbox');
        const pages: PageData[] = [];

        for (let i = 0; i < selected.length; i++) {
            const select = selected[i] as HTMLInputElement;
            const message = document.getElementById(`message${i}`) as HTMLInputElement;

            if (select.checked) {
                pages.push({
                    path: select.value,
                    message: message.value,
                });
            }
        }
        return pages;
    }

    public async readPages(): Promise<void> {
        if (!window.location.pathname.endsWith('/admin/publish')) {
            return;
        }

        let answer: ChangedPages;

        try {
            const response = await fetch(
                window.location.pathname + '/pushy:readPages',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                }
            );

            if (response.ok) {
                answer = await response.json() as ChangedPages;
            } else {
                this.notify('ReadPages: No valid response from server.', 'error');

                return;
            }
        } catch (error) {
            this.notify('ReadPages: Unexpected error while accessing the server.', 'error');

            return;
        }

        if (answer) {
            this.clearAlerts();

            const pageCount = Object.keys(answer).length;

            if (pageCount > 0) {
                this.notify(`Found ${pageCount} changed pages`, 'info');
            } else {
                this.notify('No changes pages found', 'info');
            }

            this.displayPages(answer);
        }
    }

    private displayPages(pages: ChangedPages) {
        const tableRows = document.getElementById('pagelist') as HTMLElement;
        tableRows.innerHTML = '';

        Object.keys(pages).forEach((path: string, i: number) => {
            const page: ChangedPage = pages[path];

            const pageRow = document.createElement('tr');

            pageRow.innerHTML = `
            <td class="select">
                <input id="selectbox${i}" class="selectbox" type="checkbox" value="${page.path}">
            </td>
             <td class="path">${page.path}</td>
           <td class="message">
                <input id="message${i}" class="message" type="text">
            </td>
            `;

            tableRows.appendChild(pageRow);
        });
    }

    public async commitPages(pages: PageData[]) {
        let response: Response;

        try {
            response = await fetch(
                window.location.pathname + '/pushy:commitPages',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(pages),
                }
            );
        } catch (error) {
            this.notify('CommitPage: Unexpected error while accessing the server.', 'error');
            return;
        }

        if (response.ok) {
            const answer = await response.json() as Response;

            if (answer.isSuccess) {
                this.notify(answer.alert, 'info');
            } else {
                this.notify(answer.alert, 'error');
            }

            // void this.readPages();
        } else {
            this.notify('No valid response from server.', 'error');
        }
    }


    public async revertPages(pages: PageData[]) {
        let response: Response;

        try {
            response = await fetch(
                window.location.pathname + '/pushy:revertPages',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(pages),
                }
            );
        } catch (error) {
            this.notify('RevertPage: Unexpected error while accessing the server.', 'error');
            return;
        }

        if (response.ok) {
            const answer = await response.json() as Response;

            if (answer.isSuccess) {
                this.notify(answer.alert, 'info');
            } else {
                this.notify(answer.alert, 'error');
            }
        } else {
            this.notify('No valid response from server.', 'error');
        }
    }

    public notify(message: string, type: 'info' | 'error') {
        this.clearAlerts();

        const newMessage = document.createElement('div');
        newMessage.className = `${type} alert publish`;

        const newContent = document.createTextNode(message);
        newMessage.appendChild(newContent);

        const messages = document.getElementById('messages');
        messages?.appendChild(newMessage);
    }

    public clearAlerts() {
        const alerts = document.getElementsByClassName('alert publish');

        const messages = document.getElementById('messages');

        for (const alert of alerts) {
            messages?.removeChild(alert);
        }
    }
}

const admin = new PushyAdmin();
void admin.readPages();
