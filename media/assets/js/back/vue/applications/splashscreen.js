jQuery(function ($) {
    if ($('#acym__splashscreen').length > 0) {

        const appVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__splashscreen',
            data: {
                menus: null,
                activeMenu: null
            },
            mounted: function () {
                this.menus = JSON.parse($('#splashScreenJsonInfos').val());
                this.menus = this.menus.splash_content;
                this.toggleMenu(this.menus[0]);
            },
            methods: {
                toggleMenu(menu) {
                    this.activeMenu = menu;
                },
                toggleNextMenu() {
                    if (this.menus.indexOf(this.activeMenu) < this.menus.length - 1) {
                        this.toggleMenu(this.menus[this.menus.indexOf(this.activeMenu) + 1]);
                    }
                },
                skipButton() {
                    window.location.reload();
                }
            }
        });
    }
});
