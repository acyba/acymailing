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
                requireConfirmation: 0
            },
            mounted: function () {
                document.querySelector('.acym__list__settings__subscribers__search input.acym__light__input').addEventListener('keydown', function (event) {
                    if (event.keyCode == 13) {
                        event.preventDefault();
                        return false;
                    }
                });
                this.requireConfirmation = document.getElementById('requireConfirmation').value;
                let subscriberListingContainer = document.getElementById('acym__list__settings__subscribers__listing');
                if (null !== subscriberListingContainer) subscriberListingContainer.style.display = 'flex';
                this.subscribed = JSON.parse(document.getElementById('subscribers_subscribed').value);
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
                    this.displayedSubscribers = '' === this.searchSubscribers ? this.subscribed.slice(0, start) : search(
                        this.subscribed,
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
                          + this.listid, (res) => {
                        res = JSON.parse(res);

                        if (undefined !== res.error) {
                            console.log(res.error);
                            return false;
                        }

                        if (0 === res.data.length) {
                            this.loading = false;
                            return true;
                        }

                        let nbLoaded = res.data.length;

                        this.subscribed = this.subscribed.concat(res.data);
                        this.total += nbLoaded;

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
                }
            },
            watch: {
                searchSubscribers() {
                    this.doSearch();
                }
            }
        });
    }
});
