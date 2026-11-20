Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'catalogues',
    key: 'esch_pflicht_daten_cockpit',
    roles: {
        viewer: {
            privileges: [
                'esch_pflicht_daten_cockpit:read',
                'product:read',
                'product_manufacturer:read',
            ],
            dependencies: [],
        },
    },
});
