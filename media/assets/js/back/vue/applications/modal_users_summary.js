const acym_userSummaryModal = {
    applications: [],
    init: function () {
        jQuery(document).ready(() => {
            this.getOpenModalEvent();
        });
        jQuery(document).on('acym__modal__users__summary__ready', () => {
            this.getOpenModalEvent();
        });
    },
    getOpenModalEvent: function () {
        jQuery('.acym__modal__users__summary__container').off('open.zf.reveal').on('open.zf.reveal', function () {
            const id = jQuery(this).attr('id');
            const data = acym_helper.parseJson(jQuery(this).find('[acym-data-query]').attr('acym-data-query'));
            acym_userSummaryModal.setModalUsersSummary(`#${id}`, data);
        }).on('closed.zf.reveal', function () {
            let id = jQuery(this).attr('id');
            acym_userSummaryModal.applications[`#${id}`].listingLoading = true;
        });
    },
    setModalUsersSummary: function (element, params) {
        if (this.applications[element] !== undefined) {
            this.applications[element].reload();
            return;
        }

        let linkAjaxEnd = '';

        for (let [index, param] of Object.entries(params)) {
            linkAjaxEnd += `&${index}=${param}`;
        }

        let linkAjax = `${ACYM_AJAX_URL}${linkAjaxEnd}`;

        this.applications[element] = new Vue({
            directives: {infiniteScroll},
            el: element + ' .acym__modal__users__summary',
            data: {
                users: {},
                limit: 50,
                scroll: 0,
                busy: false,
                listingError: false,
                listingLoading: true,
                errorMessage: '',
                search: '',
                typingTimer: '',
                queryUsers: null
            },
            mounted: function () {
                this.getUsers();
            },
            methods: {
                getUsers() {
                    if (this.queryUsers) {
                        this.queryUsers.abort();
                    }

                    const dataAjax = {
                        offset: this.limit * this.scroll,
                        limit: this.limit,
                        modal_search: encodeURIComponent(this.search)
                    };

                    let serializeData = jQuery('#acym_form').serialize();

                    for (let [index, param] of Object.entries(dataAjax)) {
                        serializeData += `&${index}=${param}`;
                    }

                    serializeData += linkAjaxEnd;


                    this.queryUsers = acym_helper.post(linkAjax, serializeData, true);
                    this.queryUsers.then(response => {
                        response = acym_helper.parseJson(response);
                        if (response.error) {
                            this.errorMessage = response.message;
                            this.listingError = true;

                            return;
                        }

                        this.users = {...this.users, ...response.data.users};

                        this.listingError = false;
                        this.listingLoading = false;
                    }).always(response => {
                        this.listingLoading = false;
                        this.busy = false;
                    });
                },
                loadMoreUsers() {
                    this.busy = true;
                    this.scroll++;
                    this.getUsers();
                },
                reload() {
                    this.scroll = 0;
                    this.users = {};
                    this.getUsers();
                }
            },
            computed: {
                displayListing() {
                    return !this.listingError && !this.listingLoading && !this.emptyListing;
                },
                emptyListing() {
                    return Object.keys(this.users).length === 0 && !this.listingLoading && !this.listingError;
                }
            },
            watch: {
                search(newValue) {
                    clearTimeout(this.typingTimer);
                    this.scroll = 0;
                    this.users = {};
                    this.listingLoading = true;
                    if ('' === newValue) {
                        this.getUsers();
                    } else {
                        this.typingTimer = setTimeout(() => {
                            this.getUsers();
                        }, 1000);
                    }
                }
            }
        });
    }
};

acym_userSummaryModal.init();
