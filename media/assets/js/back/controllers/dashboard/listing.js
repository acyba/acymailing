jQuery(function ($) {
    function Init() {
        setCommunitySlider();
        updateOrDisplayListsStats();
    }

    Init();

    function setCommunitySlider() {
        const $slidesWrapper = $('.slides-wrapper');
        const $slides = $('.slide-group');
        const $prevButton = $('#prevBtn');
        const $nextButton = $('#nextBtn');
        const totalSlides = $slides.length - 1;
        let slideWidth = $slides.outerWidth(true);
        let currentPosition = 0;

        function updateButtons() {
            $prevButton.toggleClass('disabled', currentPosition === 0);
            $nextButton.toggleClass('disabled', currentPosition === -totalSlides * slideWidth);
        }

        function moveSlider(direction) {
            if (direction === 'next' && currentPosition > -totalSlides * slideWidth) {
                currentPosition -= slideWidth;
            } else if (direction === 'prev' && currentPosition < 0) {
                currentPosition += slideWidth;
            }
            currentPosition = Math.round(currentPosition * 100) / 100;
            currentPosition = Math.max(-totalSlides * slideWidth, Math.min(0, currentPosition));
            $slidesWrapper.css('transform', `translateX(${currentPosition}px)`);

            updateButtons();
        }

        $prevButton.on('click', function () {
            moveSlider('prev');
        });

        $nextButton.on('click', function () {
            moveSlider('next');
        });

        $(window).on('resize', function () {
            slideWidth = $slides.outerWidth(true);
            currentPosition = 0;
            $slidesWrapper.css('transform', `translateX(0px)`);
            updateButtons();
        });
    }

    function updateOrDisplayListsStats() {
        const data = {
            ctrl: 'lists',
            task: 'loadMostUsedLists'
        };

        acym_helper.post(ACYM_AJAX_URL, data).then(response => {
            if (response.error) {
                console.log(response.error);
            } else {
                let totalItems = response.data.listAndSubscribersData.length;

                $.each(response.data.listAndSubscribersData, function (index, item) {
                    $('.name_' + (index + 1)).text(item.name);
                    $('.sub_' + (index + 1)).text(item.subscribers);
                    $('.unsub_' + (index + 1)).text(item.unsubscribed_users);
                    $('.new_sub_' + (index + 1)).text(`(+${item.new_sub})`);
                    $('.new_unsub_' + (index + 1)).text(`(+${item.new_unsub})`);
                });

                for (let i = totalItems + 1 ; i <= 5 ; i++) {
                    $('.main-list-row-' + i).remove();
                }

                $('.skeleton').removeClass('skeleton');
            }

        });
    }
});
