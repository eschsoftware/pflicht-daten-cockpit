import warnIfGap from '../../../esch-pdc/gpsr-save-warning';

Shopware.Component.override('sw-manufacturer-detail', {
    computed: {
        eschPdcAddress: {
            get() {
                return this.manufacturer?.customFields?.esch_pdc_manufacturer_address || '';
            },
            set(value) {
                this.eschPdcWriteField('esch_pdc_manufacturer_address', value);
            },
        },

        eschPdcContact: {
            get() {
                return this.manufacturer?.customFields?.esch_pdc_manufacturer_contact || '';
            },
            set(value) {
                this.eschPdcWriteField('esch_pdc_manufacturer_contact', value);
            },
        },
    },

    watch: {
        manufacturer() {
            this.$nextTick(() => this.eschPdcMountFields());
        },

        isSaveSuccessful(saved) {
            if (saved && this.manufacturer && this.manufacturer.id) {
                warnIfGap(this, { manufacturerId: this.manufacturer.id });
            }
        },
    },

    methods: {
        eschPdcWriteField(name, value) {
            if (!this.manufacturer) {
                return;
            }

            const current = this.manufacturer.customFields ? { ...this.manufacturer.customFields } : {};
            current[name] = value;
            this.manufacturer.customFields = current;
        },

        eschPdcMountFields() {
            if (!this.$el || !this.manufacturer) {
                return;
            }

            if (Array.isArray(this.customFieldSets)) {
                const visible = this.customFieldSets.filter((set) => set.name !== 'esch_pdc_gpsr_manufacturer');
                if (visible.length !== this.customFieldSets.length) {
                    this.customFieldSets = visible;
                }
            }

            const host = this.$el.querySelector('.sw-manufacturer-detail__container');
            if (!host || host.parentElement.querySelector('[data-pdc-fields]')) {
                return;
            }

            const box = document.createElement('div');
            box.dataset.pdcFields = '1';
            box.style.marginTop = '16px';
            box.innerHTML = [
                '<label style="display:block;font-weight:600;margin:0 0 12px">Postanschrift',
                '<textarea data-pdc-address rows="4" style="display:block;width:100%;margin-top:6px;padding:8px;border:1px solid #c3ccd4;border-radius:4px;background:#fff"></textarea>',
                '</label>',
                '<label style="display:block;font-weight:600;margin:0 0 8px">Elektronischer Kontakt (E-Mail)',
                '<input data-pdc-contact type="email" style="display:block;width:100%;margin-top:6px;padding:8px;border:1px solid #c3ccd4;border-radius:4px;background:#fff">',
                '</label>',
                '<p style="margin:0;color:#3b4b57">Diese Angaben gehören zur GPSR-Pflicht des Händlers. Der Endkunde sieht sie nicht.</p>',
            ].join('');
            host.insertAdjacentElement('afterend', box);

            const address = box.querySelector('[data-pdc-address]');
            const contact = box.querySelector('[data-pdc-contact]');
            address.value = this.eschPdcAddress;
            contact.value = this.eschPdcContact;
            address.addEventListener('input', (event) => {
                this.eschPdcAddress = event.target.value;
            });
            contact.addEventListener('input', (event) => {
                this.eschPdcContact = event.target.value;
            });
        },
    },
});
