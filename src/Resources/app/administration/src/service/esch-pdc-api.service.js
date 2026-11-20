const ApiService = Shopware.Classes.ApiService;

class EschPdcApiService extends ApiService {
    constructor(httpClient, loginService) {
        super(httpClient, loginService, 'esch-pflicht-daten-cockpit');
        this.name = 'eschPdcApiService';
    }

    getOverview({ page, limit, status, term }) {
        return this.httpClient
            .get('/_action/esch-pflicht-daten-cockpit/gpsr-manufacturer', {
                params: {
                    page,
                    limit,
                    status,
                    q: term || '',
                },
                headers: this.getBasicHeaders(),
            })
            .then((response) => ApiService.handleResponse(response));
    }
}

Shopware.Application.addServiceProvider('eschPdcApiService', (container) => {
    const init = Shopware.Application.getContainer('init');

    return new EschPdcApiService(init.httpClient, container.loginService);
});
