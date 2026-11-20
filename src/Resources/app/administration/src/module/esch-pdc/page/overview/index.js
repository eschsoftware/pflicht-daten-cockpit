import template from './esch-pdc-overview.html.twig';
import './esch-pdc-overview.scss';

Shopware.Component.register('esch-pdc-overview', {
    template,

    inject: ['eschPdcApiService'],

    data() {
        return {
            isLoading: false,
            error: null,
            items: [],
            counts: { checked: 0, open: 0, ok: 0 },
            status: 'open',
            page: 1,
            limit: 25,
            total: 0,
            term: '',
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'productNumber',
                    label: this.$tc('esch-pdc.overview.columnNumber'),
                    primary: true,
                    rawData: true,
                },
                {
                    property: 'productName',
                    label: this.$tc('esch-pdc.overview.columnName'),
                    rawData: true,
                },
                {
                    property: 'manufacturerName',
                    label: this.$tc('esch-pdc.overview.columnManufacturer'),
                },
                {
                    property: 'missingLabel',
                    label: this.$tc('esch-pdc.overview.columnMissing'),
                },
                {
                    property: 'status',
                    label: this.$tc('esch-pdc.overview.columnStatus'),
                    rawData: true,
                },
            ];
        },
    },

    created() {
        this.load();
    },

    methods: {
        load() {
            this.isLoading = true;
            this.error = null;

            this.eschPdcApiService.getOverview({
                page: this.page,
                limit: this.limit,
                status: this.status,
                term: this.term,
            }).then((response) => {
                this.counts = response.counts || { checked: 0, open: 0, ok: 0 };
                this.total = response.total || 0;
                this.page = response.page || 1;
                this.items = (response.items || []).map((item) => ({
                    ...item,
                    id: item.productId,
                    manufacturerName: item.manufacturerName || '',
                }));
            }).catch(() => {
                this.error = this.$tc('esch-pdc.overview.loadError');
                this.items = [];
            }).finally(() => {
                this.isLoading = false;
            });
        },

        missingLabel(codes) {
            if (!codes || codes.length === 0) {
                return this.$tc('esch-pdc.overview.missingNone');
            }

            return codes.map((code) => this.$tc(`esch-pdc.missing.${code}`)).join(', ');
        },

        setStatus(status) {
            this.status = status;
            this.page = 1;
            this.load();
        },

        setPage(page) {
            this.page = page;
            this.load();
        },

        search() {
            this.page = 1;
            this.load();
        },

        openProduct(productId) {
            this.$router.push({
                name: 'sw.product.detail',
                params: { id: productId },
            });
        },
    },
});
