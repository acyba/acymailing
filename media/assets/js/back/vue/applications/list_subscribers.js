jQuery(document).ready(function ($) {
    if ($('#requireConfirmation').length > 0) {
        let perScroll = 20, start = perScroll;
        let subPerCalls = 500;
        const columns = [
            'email',
            'name'
        ];

        const search = (array, search, columns) => array.filter((subscriber) => columns.map((column) => subscriber[column].toLowerCase()
                                                                                                                          .indexOf(search.toLowerCase()) !== -1)
                                                                                       .indexOf(true) !== -1);

        let subscriberVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__list__settings__subscribers',
            data: {
                subscribed: [],
                displayedSubscribers: [],
                busy: false,
                listid: 0,
                total: 0,
                loading: true,
                searchSubscribers: '',
                requireConfirmation: 0,
                users_ordering: 'id',
                users_ordering_sort_order: 'desc'
            },
            mounted: function () {
                document.querySelector('.acym__list__settings__subscribers__search input[type="text"]').addEventListener('keydown', function (event) {
                    if (event.keyCode == 13) {
                        event.preventDefault();
                        return false;
                    }
                });
                this.requireConfirmation = document.getElementById('requireConfirmation').value;
                let subscriberListingContainer = document.getElementById('acym__list__settings__subscribers__listing');
                if (null !== subscriberListingContainer) subscriberListingContainer.style.display = 'flex';
                this.subscribed = acym_helper.parseJson(document.getElementById('subscribers_subscribed').value);
                this.listid = document.querySelector('[name="id"]').value;
                this.total = this.subscribed.length;
                this.displayedSubscribers = this.subscribed.slice(0, start);
                if (this.total < subPerCalls) {
                    this.loading = false;
                } else {
                    this.getAllSubscribers();
                }
            },
            methods: {
                loadMoreSubscriber() {
                    start += perScroll;
                    this.displayedSubscribers = '' === this.searchSubscribers ? this.subscribed.slice(0, start) : search(this.subscribed,
                        this.searchSubscribers,
                        columns
                    );
                },
                getAllSubscribers() {
                    let ctrl = document.querySelector('[name="ctrl"]').value;
                    $.get(ACYM_AJAX_URL
                          + '&ctrl='
                          + ctrl
                          + '&task=loadMoreSubscribers&offset='
                          + this.total
                          + '&perCalls='
                          + subPerCalls
                          + '&listid='
                          + this.listid
                          + '&orderBy='
                          + this.users_ordering
                          + '&orderByOrdering='
                          + this.users_ordering_sort_order, (res) => {
                        res = acym_helper.parseJson(res);

                        if (0 === res.data.subscribers.length) {
                            this.loading = false;
                            return true;
                        }

                        let nbLoaded = res.data.subscribers.length;

                        this.subscribed = this.subscribed.concat(res.data.subscribers);
                        this.total += nbLoaded;
                        this.displayedSubscribers = this.subscribed.slice(0, perScroll);

                        if (nbLoaded < subPerCalls) {
                            this.loading = false;
                            return true;
                        }
                        this.getAllSubscribers();
                        return true;
                    });
                },
                doSearch() {
                    if ('' === this.searchSubscribers) {
                        start = perScroll;
                        this.displayedSubscribers = this.subscribed.slice(0, start);
                    } else {
                        this.displayedSubscribers = search(this.subscribed, this.searchSubscribers, columns);
                    }
                },
                unsubscribeUser(subscriberId) {
                    let form = $('#acym_form');
                    $('[name="acym__entity_select__selected"]').val('');
                    $('[name="acym__entity_select__unselected"]').val('[' + subscriberId + ']');
                    form.find('[name="task"]').attr('value', 'saveSubscribers');
                    form.submit();
                },
                sortOrdering(event) {
                    let inputSortOrder = $('[name="users_ordering_sort_order"]');
                    inputSortOrder.val(inputSortOrder.val() == 'asc' ? 'desc' : 'asc');
                    $(event.target).toggleClass('acymicon-sort-amount-desc acymicon-sort-amount-asc');
                    this.users_ordering_sort_order = inputSortOrder.val();
                    this.getAgainSubscribers();
                },
                getAgainSubscribers() {
                    this.subscribed = [];
                    this.displayedSubscribers = [];
                    this.total = 0;
                    this.loading = true;
                    this.getAllSubscribers();
                }
            },
            watch: {
                searchSubscribers() {
                    this.doSearch();
                },
                users_ordering() {
                    this.getAgainSubscribers();
                }
            }
        });
    }
});
