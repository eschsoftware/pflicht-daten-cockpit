import warnIfGap from '../../../esch-pdc/gpsr-save-warning';

Shopware.Component.override('sw-product-detail', {
    methods: {
        onSave() {
            const warn = () => {
                Shopware.Utils.EventBus.off('sw-product-detail-save-success', warn);
                if (this.product && this.product.id) {
                    warnIfGap(this, { productId: this.product.id });
                }
            };
            Shopware.Utils.EventBus.on('sw-product-detail-save-success', warn);

            const result = this.$super('onSave');
            const cleanup = () => Shopware.Utils.EventBus.off('sw-product-detail-save-success', warn);

            if (!result || typeof result.finally !== 'function') {
                cleanup();
                return result;
            }

            return result.finally(cleanup);
        },
    },
});
