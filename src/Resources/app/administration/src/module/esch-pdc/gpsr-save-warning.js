export default function warnIfGap(vm, params) {
    const api = Shopware.Service('eschPdcApiService');

    return api.checkGap(params).then((result) => {
        if (!result || result.status !== 'open' || !Array.isArray(result.missing) || result.missing.length === 0) {
            return;
        }

        const missing = result.missing
            .map((code) => vm.$tc(`esch-pdc.missing.${code}`))
            .join(', ');

        vm.createNotificationWarning({
            title: vm.$tc('esch-pdc.saveWarning.title'),
            message: vm.$tc('esch-pdc.saveWarning.message', 0, { missing }),
        });
    }).catch(() => undefined);
}
