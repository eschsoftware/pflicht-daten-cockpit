import './page/overview';
import './acl';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('esch-pdc', {
    type: 'plugin',
    name: 'PflichtDatenCockpit',
    title: 'esch-pdc.general.menuItem',
    description: 'esch-pdc.general.description',
    color: '#0f3d32',
    icon: 'regular-shield',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        overview: {
            component: 'esch-pdc-overview',
            path: 'overview',
            meta: {
                privilege: 'esch_pflicht_daten_cockpit:read',
            },
        },
    },

    navigation: [{
        id: 'esch-pdc-overview',
        label: 'esch-pdc.general.menuItem',
        color: '#0f3d32',
        path: 'esch.pdc.overview',
        icon: 'regular-shield',
        parent: 'sw-catalogue',
        position: 80,
        privilege: 'esch_pflicht_daten_cockpit:read',
    }],
});
